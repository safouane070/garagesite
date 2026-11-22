<?php

namespace App\Console\Commands;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Support\Marktplaats;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Vult de specs van onze auto's aan uit de Marktplaats-advertentie van dezelfde auto.
 * De eigen dealersite geeft alleen bouwjaar, km, brandstof, carrosserie, kleur en deuren.
 *
 * - Koppelen: zelfde merk + bouwjaar, kilometerstand hooguit MILEAGE_TOLERANCE verschil;
 *   twee even goede kandidaten → overslaan (liever niets dan de specs van een andere auto).
 * - Wat de beheerder invulde blijft staan; alleen wat met de tijd verandert (APK, aantal
 *   eigenaren) wordt ververst.
 * - Een gekoppelde advertentie wordt hoogstens eens per REFRESH_DAYS opnieuw gelezen.
 */
class EnrichCars extends Command
{
    protected $signature = 'cars:enrich {--force : ook recent verrijkte advertenties opnieuw lezen} {--delay=1000 : pauze tussen verzoeken in ms}';

    protected $description = 'Specs aanvullen uit de Marktplaats-advertenties van de dealer';

    private const MILEAGE_TOLERANCE = 500;

    private const REFRESH_DAYS = 7;

    /** Waarden die met de tijd veranderen en dus altijd de nieuwste stand krijgen. */
    private const VOLATILE = ['apk_tot', 'eigenaren'];

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

        $stats = ['gelezen' => 0, 'verrijkt' => 0, 'overgeslagen' => 0, 'geen match' => 0];
        foreach ($paths as $path) {
            $id = Marktplaats::idFromPath($path);
            $linked = $cars->firstWhere('marktplaats_id', $id);
            if ($linked && ! $this->option('force') && $linked->enriched_at?->gt(now()->subDays(self::REFRESH_DAYS))) {
                $stats['overgeslagen']++;
                continue;
            }

            usleep(max(0, (int) $this->option('delay')) * 1000);
            $listing = Marktplaats::listing($path);
            $stats['gelezen']++;
            if ($listing === null) {
                continue;
            }

            $car = $linked ?? $this->match($cars->whereNull('marktplaats_id'), $listing);
            if (! $car) {
                $stats['geen match']++;
                continue;
            }

            $this->apply($car, $id, Marktplaats::specs($listing['attributes']));
            $stats['verrijkt']++;
        }

        $this->info(collect($stats)->map(fn ($n, $k) => "{$k}: {$n}")->join(' · ') . ' (' . count($paths) . ' advertenties)');

        return self::SUCCESS;
    }

    /** De enige auto die past op merk, bouwjaar en kilometerstand; anders null. */
    private function match(Collection $cars, array $listing): ?Car
    {
        $brand = Str::lower(Str::ascii($listing['brand']));
        $candidates = $cars
            ->filter(fn (Car $c) => $c->year === $listing['year']
                && abs($c->mileage - $listing['mileage']) <= self::MILEAGE_TOLERANCE
                && Str::startsWith($brand, Str::lower(Str::ascii($c->brand)))
                && self::sameModel($c, $listing['brand']))
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
        $current = (array) $car->specs;
        // Bestaande waarden (o.a. van de beheerder) gaan voor; alleen tijdgevoelige velden verversen.
        $specs = array_merge($fresh, $current, array_intersect_key($fresh, array_flip(self::VOLATILE)));

        $car->forceFill([
            'specs' => $specs ?: null,
            'marktplaats_id' => $id,
            'enriched_at' => now(),
        ])->save();
        $car->marktplaats_id = $id; // ook in de collectie, zodat hij niet nog eens gematcht wordt
    }
}
