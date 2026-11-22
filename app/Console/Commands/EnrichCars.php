<?php

namespace App\Console\Commands;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Support\Marktplaats;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Sleep;
use Illuminate\Support\Str;

/**
 * Vult de specs van onze auto's aan uit de Marktplaats-advertentie van dezelfde auto.
 * De eigen dealersite geeft alleen bouwjaar, km, brandstof, carrosserie, kleur en deuren.
 *
 * - Koppelen: zelfde merk, model, bouwjaar en prijs (±PRICE_TOLERANCE), kilometerstand
 *   hooguit MILEAGE_TOLERANCE verschil; twee even goede kandidaten → overslaan (liever
 *   niets dan de specs van een andere auto).
 * - Eén keer per auto: een gekoppelde advertentie wordt niet opnieuw gelezen (behalve met
 *   --force). Zo gaat de beheerder altijd voor, ook als hij een veld leegmaakt.
 */
class EnrichCars extends Command
{
    protected $signature = 'cars:enrich {--force : ook al gekoppelde auto\'s opnieuw aanvullen (vult ook velden die de beheerder leegmaakte)} {--delay=1000 : pauze tussen verzoeken in ms}';

    protected $description = 'Specs aanvullen uit de Marktplaats-advertenties van de dealer';

    private const MILEAGE_TOLERANCE = 500;

    /**
     * De dealer vraagt op Marktplaats dezelfde prijs als op zijn site. Een andere auto
     * van hetzelfde merk/model/jaar met bijna dezelfde km (bv. een verkochte die nog even
     * op Marktplaats staat) valt hierop af. 10%: ruimte voor een prijswijziging die nog
     * niet op beide kanalen staat.
     */
    private const PRICE_TOLERANCE = 0.10;

    /** Pauze vóór de tweede ronde: een afremming van Marktplaats duurt meestal kort. */
    private const RETRY_PAUSE_SECONDS = 60;

    public function handle(): int
    {
        $seller = (string) config('brand.marktplaats_seller');
        if ($seller === '') {
            $this->info('Geen Marktplaats-verkoper ingesteld (MARKTPLAATS_SELLER); niets te doen.');

            return self::SUCCESS;
        }

        $cars = Car::where('status', '!=', CarStatus::Sold->value)->get();
        $paths = Marktplaats::listingPaths($seller);
        if ($paths === []) {
            $this->error('Geen advertenties gevonden op het verkopersprofiel; Marktplaats onbereikbaar of veranderd.');

            return self::FAILURE;
        }

        $stats = ['gelezen' => 0, 'verrijkt' => 0, 'overgeslagen' => 0, 'geen match' => 0, 'onleesbaar' => 0];
        $delay = max(0, (int) $this->option('delay'));
        $todo = array_values(array_filter($paths, function (string $path) use ($cars, &$stats) {
            $linked = $cars->firstWhere('marktplaats_id', Marktplaats::idFromPath($path));
            if ($linked && ! $this->option('force')) {
                $stats['overgeslagen']++;

                return false;
            }

            return true;
        }));

        $unread = $this->pass($todo, $cars, $stats, $delay);
        // Afgeremd of even een blokkadepagina: na een pauze nog één keer, drie keer zo traag,
        // zodat die auto's niet een dag zonder specs blijven.
        if ($unread !== []) {
            $this->line(count($unread) . ' advertenties onleesbaar; na een pauze nog een keer, langzamer.');
            Sleep::for(self::RETRY_PAUSE_SECONDS)->seconds();
            $unread = $this->pass($unread, $cars, $stats, max($delay, 1000) * 3);
        }
        $stats['onleesbaar'] = count($unread);

        $this->info(collect($stats)->map(fn ($n, $k) => "{$k}: {$n}")->join(' · ') . ' (' . count($paths) . ' advertenties)');

        // Meer dan de helft onleesbaar: Marktplaats blokkeert ons of veranderde zijn pagina's.
        // Falen, zodat de geplande run de foutmail stuurt i.p.v. stil "gelukt".
        if ($todo !== [] && count($unread) / count($todo) > 0.5) {
            $this->error(count($unread) . ' van de ' . count($todo) . ' advertenties bleven onleesbaar: Marktplaats blokkeert of veranderde zijn pagina-opbouw.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * Leest de advertenties en verrijkt wat past.
     *
     * @param  list<string>  $paths
     * @return list<string> de advertenties die onleesbaar bleven
     */
    private function pass(array $paths, Collection $cars, array &$stats, int $delayMs): array
    {
        $unread = [];
        foreach ($paths as $path) {
            Sleep::usleep($delayMs * 1000);
            $listing = Marktplaats::listing($path);
            $stats['gelezen']++;
            if ($listing === null) {
                $unread[] = $path;
                continue;
            }

            $id = Marktplaats::idFromPath($path);
            $car = $cars->firstWhere('marktplaats_id', $id) ?? $this->match($cars->whereNull('marktplaats_id'), $listing);
            if (! $car) {
                $stats['geen match']++;
                continue;
            }

            $this->apply($car, $id, Marktplaats::specs($listing['attributes']));
            $stats['verrijkt']++;
        }

        return $unread;
    }

    /** De enige auto die past op merk, bouwjaar en kilometerstand; anders null. */
    private function match(Collection $cars, array $listing): ?Car
    {
        $brand = Str::lower(Str::ascii($listing['brand']));
        $candidates = $cars
            ->filter(fn (Car $c) => $c->year === $listing['year']
                && abs($c->mileage - $listing['mileage']) <= self::MILEAGE_TOLERANCE
                && Str::startsWith($brand, Str::lower(Str::ascii($c->brand)))
                && self::sameModel($c, $listing['brand'])
                && abs((float) $c->price - $listing['price']) <= (float) $c->price * self::PRICE_TOLERANCE)
            ->sortBy(fn (Car $c) => abs($c->mileage - $listing['mileage']))
            ->values();

        if ($candidates->count() > 1
            && abs($candidates[0]->mileage - $listing['mileage']) === abs($candidates[1]->mileage - $listing['mileage'])) {
            return null;
        }

        return $candidates->first();
    }

    /**
     * Eerste woord van het model gelijk ("Golf" ≠ "Tiguan"). Marktplaats schrijft
     * "<Merk> <Model>, <uitvoering>" met wisselende hoofdletters ("GOLF", "T-ROC",
     * "GLE-klasse Coupe"); zonder deze check kregen twee bijna-nieuwe auto's van
     * hetzelfde merk en jaar elkaars specs.
     */
    private static function sameModel(Car $car, string $marktplaatsName): bool
    {
        $first = fn (string $s) => preg_split('/[^a-z0-9]+/', Str::lower(Str::ascii($s)), -1, PREG_SPLIT_NO_EMPTY)[0] ?? null;
        $model = trim(Str::substr(Str::before($marktplaatsName, ','), Str::length($car->brand)));

        return $first((string) $car->model) !== null && $first((string) $car->model) === $first($model);
    }

    private function apply(Car $car, ?string $id, array $fresh): void
    {
        // Verse stand: de run duurt minuten, een bewerking van de beheerder intussen blijft staan.
        $car->refresh();
        // Bestaande waarden (o.a. van de beheerder) gaan altijd voor.
        $specs = array_merge($fresh, (array) $car->specs);

        $car->forceFill([
            'specs' => $specs ?: null,
            'marktplaats_id' => $id,
            'enriched_at' => now(),
        ])->save();
        $car->marktplaats_id = $id; // ook in de collectie, zodat hij niet nog eens gematcht wordt
    }
}
