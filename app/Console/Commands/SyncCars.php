<?php

namespace App\Console\Commands;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Support\CarDescription;
use App\Support\DealerSite;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * Houdt de voorraad gelijk met de dealersite (dagelijks ingepland):
 *  - nieuwe auto's op de dealersite → aangemaakt (met foto's en opties);
 *  - auto's die van de dealersite verdwijnen → op "verkocht" (niet gewist, zodat
 *    ze in "met trots verkocht" blijven staan);
 *  - gewijzigde auto's → prijs/kilometerstand bijgewerkt.
 *
 * Koppelen gebeurt op slug en — als die verschilt — op identiteit (merk +
 * bouwjaar + kilometerstand), zodat er geen dubbele auto's ontstaan.
 * Ongekoppelde auto's (bv. handmatig in het beheer aangemaakt) blijven met rust;
 * alleen met --sell-unmatched worden ze bewust op verkocht gezet.
 *
 * Eerst wordt berekend wat er verdwijnt; lijkt dat verdacht veel (bron veranderd
 * of storing), dan stopt het vóór de eerste wijziging.
 */
class SyncCars extends Command
{
    protected $signature = "cars:sync
        {--dry-run : Toon wat er zou veranderen, maar wijzig niets}
        {--force : Ook doorgaan als verdacht veel auto's verdwenen lijken}
        {--limit=0 : Maximaal zoveel nieuwe auto's aanmaken (0 = alle)}
        {--sell-unmatched : Zet niet-verkochte auto's die nergens aan te koppelen zijn op verkocht (eenmalig, na controle)}
        {--require-parse-ratio= : Faal als minder dan dit deel van de nieuwe auto's leesbaar is (0-1)}";

    protected $description = 'Synchroniseert de voorraad met de dealersite (nieuw, verkocht, prijswijzigingen).';

    private const MAX_GONE_RATIO = 0.5;

    /** Kilometerstand mag iets verschillen (proefritten, afronding). */
    private const MILEAGE_TOLERANCE = 500;

    private bool $dry = false;

    public function handle(): int
    {
        $this->dry = (bool) $this->option('dry-run');

        try {
            $vehicles = collect(DealerSite::vehicles())->keyBy('slug');
        } catch (\Throwable $e) {
            $this->error('Voorraadlijst van de dealersite niet op te halen — niets gewijzigd. ' . $e->getMessage());

            return self::FAILURE;
        }
        if ($vehicles->isEmpty()) {
            $this->error('De dealersite gaf een lege voorraadlijst — niets gewijzigd.');

            return self::FAILURE;
        }

        // 1. Koppelen op slug: niet-verkochte, ongekoppelde auto's aan vrije dealer-slugs.
        $linkedSlugs = Car::whereNotNull('dealer_slug')->pluck('dealer_slug')->all();
        $unlinked = Car::whereNull('dealer_slug')->where('status', '!=', CarStatus::Sold->value)->get();
        $slugLinks = DealerSite::match($unlinked, array_values(array_diff($vehicles->keys()->all(), $linkedSlugs)));
        foreach ($unlinked->whereIn('id', array_keys($slugLinks)) as $car) {
            $car->dealer_slug = $slugLinks[$car->id];
        }

        $managed = Car::whereNotNull('dealer_slug')->get()->merge($unlinked->whereIn('id', array_keys($slugLinks)));
        $active = $managed->where('status', '!=', CarStatus::Sold);
        $pool = $unlinked->whereNotIn('id', array_keys($slugLinks))->values();

        // 2. Verdwenen van de dealersite — veiligheidscheck vóór enige wijziging.
        $gone = $active->reject(fn (Car $c) => $vehicles->has($c->dealer_slug));
        if ($active->count() > 2 && $gone->count() / $active->count() > self::MAX_GONE_RATIO && ! $this->option('force')) {
            $this->error(sprintf(
                "Gestopt vóór enige wijziging: %d van de %d gekoppelde auto's lijkt verdwenen. Dat wijst eerder op een "
                . 'veranderde of haperende dealersite dan op een lege voorraad. Controleer de site; weet je het zeker, gebruik --force.',
                $gone->count(), $active->count()
            ));

            return self::FAILURE;
        }

        $managedSlugs = $managed->pluck('dealer_slug')->all();
        $new = $vehicles->reject(fn ($v) => in_array($v['slug'], $managedSlugs, true));
        $knownModels = Car::query()->distinct()->pluck('model')->filter()->all();

        $this->line(sprintf(
            "%s — dealersite: %d auto's · op slug gekoppeld: %d · verdwenen → verkocht: %d · te verwerken: %d",
            $this->dry ? 'DRY-RUN' : 'SYNC', $vehicles->count(), $managed->count(), $gone->count(), $new->count()
        ));

        if (! $this->dry) {
            foreach ($managed as $car) {
                if ($car->isDirty('dealer_slug')) {
                    $car->save();
                }
            }
            foreach ($gone as $car) {
                $car->update(['status' => CarStatus::Sold]);
                $this->line("  verkocht: {$car->title()}");
            }
        } else {
            $gone->each(fn (Car $c) => $this->line("  zou op verkocht gaan: {$c->title()}"));
        }

        $updated = $this->dry ? 0 : $this->updateChanged($active->diff($gone), $vehicles, $knownModels);

        // 3. Nieuw of op identiteit te koppelen.
        [$created, $twinned, $failed] = [0, 0, 0];
        $limit = (int) $this->option('limit');
        foreach ($new as $v) {
            $data = $this->read($v, $knownModels);
            if (is_string($data)) {
                $this->warn("  niet leesbaar ({$data}): {$v['title']}");
                $failed++;
                continue;
            }

            if ($twin = $this->findTwin($pool, $data)) {
                $pool = $pool->reject(fn (Car $c) => $c->id === $twin->id)->values();
                $this->line("  " . ($this->dry ? 'zou koppelen' : 'gekoppeld') . ": {$twin->title()} ↔ {$v['title']}");
                if (! $this->dry) {
                    $this->applyUpdate($twin->fill(['dealer_slug' => $v['slug']]), $data, Carbon::parse($v['modified']));
                }
                $twinned++;
                continue;
            }

            if ($limit > 0 && $created >= $limit) {
                continue;
            }
            if ($this->dry) {
                $this->line(sprintf("  zou nieuw worden: %s %s — %s · € %s · %d foto's",
                    $data['brand'], $data['model'], $data['variant'] ?? '', number_format($data['price'], 0, ',', '.'), count($data['photos'])));
                $created++;
            } elseif ($this->createFromDealer($v, $data)) {
                $created++;
            } else {
                $failed++;
            }
        }

        // 4. Wat nergens aan te koppelen is (handmatige auto's, of oude voorraad).
        foreach ($pool as $car) {
            if ($this->option('sell-unmatched') && ! $this->dry) {
                $car->update(['status' => CarStatus::Sold]);
                $this->line("  niet op de dealersite → verkocht: {$car->title()}");
            } else {
                $this->line("  niet gekoppeld (blijft staan): {$car->title()}");
            }
        }

        $readable = $new->count() === 0 ? 1.0 : ($new->count() - $failed) / $new->count();
        $this->info(sprintf(
            "%s: %d nieuw, %d op identiteit gekoppeld, %d verkocht, %d bijgewerkt, %d niet leesbaar/overgeslagen, %d niet gekoppeld. Leesbaar: %d%%.",
            $this->dry ? 'Dry-run (niets gewijzigd)' : 'Klaar',
            $created, $twinned, $gone->count(), $updated, $failed, $pool->count(), round($readable * 100)
        ));

        $required = $this->option('require-parse-ratio');
        if ($required !== null && $readable < (float) $required) {
            $this->error('Te weinig leesbaar voor een betrouwbare sync.');

            return self::FAILURE;
        }
        if ($this->dry) {
            $this->info('SYNC DRY-RUN OK');
        }

        return self::SUCCESS;
    }

    /** Dezelfde fysieke auto: zelfde merk en bouwjaar, kilometerstand vrijwel gelijk. */
    private function findTwin(Collection $pool, array $data): ?Car
    {
        return $pool
            ->filter(fn (Car $c) => mb_strtolower($c->brand) === mb_strtolower($data['brand'])
                && (int) $c->year === $data['year']
                && abs((int) $c->mileage - $data['mileage']) <= self::MILEAGE_TOLERANCE)
            ->sortBy(fn (Car $c) => abs((int) $c->mileage - $data['mileage']))
            ->first();
    }

    /** Prijs/kilometerstand bijwerken van gekoppelde auto's die op de dealersite gewijzigd zijn. */
    private function updateChanged(Collection $cars, Collection $vehicles, array $knownModels): int
    {
        $updated = 0;
        foreach ($cars as $car) {
            $v = $vehicles[$car->dealer_slug];
            $modified = Carbon::parse($v['modified']);
            if ($car->dealer_modified_at && $modified->lte($car->dealer_modified_at)) {
                continue;
            }

            $data = $this->read($v, $knownModels);
            if (is_string($data)) {
                $this->warn("  niet bijgewerkt ({$data}): {$car->title()}");
                continue;
            }
            $updated += $this->applyUpdate($car, $data, $modified) ? 1 : 0;
        }

        return $updated;
    }

    /** @return bool of er inhoudelijk iets veranderde */
    private function applyUpdate(Car $car, array $data, Carbon $modified): bool
    {
        $changed = false;
        if ((int) $car->price !== $data['price']) {
            $this->line(sprintf('  prijs %s: € %s → € %s', $car->title(), number_format((float) $car->price, 0, ',', '.'), number_format($data['price'], 0, ',', '.')));
            $car->price = $data['price'];
            $changed = true;
        }
        if ((int) $car->mileage !== $data['mileage']) {
            $car->mileage = $data['mileage'];
            $changed = true;
        }
        if (empty($car->options) && $data['options'] !== []) {
            $car->options = $data['options'];
            $changed = true;
        }
        if (CarDescription::isReplaceable($car->description)) {
            $car->description = CarDescription::for($car);
        }
        $car->dealer_modified_at = $modified;
        $car->save();

        return $changed;
    }

    /**
     * Crash-bestendig: eerst alle foto's als bestanden binnenhalen, pas daarna
     * de auto + fotoregels in één transactie. Valt het proces halverwege om
     * (bv. geheugen), dan staat er géén halve auto in de database; de volgende
     * run ruimt de losse bestanden op en probeert het opnieuw.
     */
    private function createFromDealer(array $v, array $data): bool
    {
        $slug = Car::makeUniqueSlug(trim("{$data['brand']} {$data['model']} {$data['variant']}"));
        $dir = "cars/{$slug}";

        try {
            Storage::disk('public')->deleteDirectory($dir); // resten van een eerdere mislukte poging
            $paths = $this->storePhotos($data['photos'], $dir);
            if ($paths === []) {
                throw new \RuntimeException('geen enkele foto te downloaden');
            }

            $car = DB::transaction(function () use ($v, $data, $slug, $paths) {
                $car = Car::create([
                    'slug' => $slug,
                    'brand' => $data['brand'], 'model' => $data['model'], 'variant' => $data['variant'],
                    'year' => $data['year'], 'price' => $data['price'], 'mileage' => $data['mileage'],
                    'fuel_type' => $data['fuel_type'], 'transmission' => $data['transmission'],
                    'color' => $data['color'], 'body_type' => $data['body_type'],
                    'specs' => $data['specs'] ?: null, 'options' => $data['options'] ?: null,
                    'status' => CarStatus::Available, 'is_featured' => false,
                    'dealer_slug' => $v['slug'], 'dealer_modified_at' => Carbon::parse($v['modified']),
                ]);
                $car->description = CarDescription::for($car);
                $car->save();

                foreach ($paths as $i => $stored) {
                    $car->images()->create($stored + ['is_primary' => $i === 0, 'sort_order' => $i]);
                }

                return $car;
            });

            $this->line("  nieuw: {$car->title()} (" . count($paths) . " foto's)");

            return true;
        } catch (\Throwable $e) {
            Storage::disk('public')->deleteDirectory($dir);
            $this->warn("  overgeslagen ({$e->getMessage()}): {$v['title']}");

            return false;
        }
    }

    /** @return list<array{path:string,thumb_path:?string,width:?int,height:?int}> in galerijvolgorde */
    private function storePhotos(array $urls, string $dir): array
    {
        $paths = [];
        foreach ($urls as $url) {
            $binary = DealerSite::download($url);
            if ($binary === null) {
                continue;
            }
            $tmp = tempnam(sys_get_temp_dir(), 'dealer');
            file_put_contents($tmp, $binary);
            try {
                $paths[] = ImageOptimizer::store(new UploadedFile($tmp, basename(parse_url($url, PHP_URL_PATH)), null, null, true), $dir);
            } finally {
                @unlink($tmp);
            }
        }

        return $paths;
    }

    /** Voertuigpagina ophalen + uitlezen. Array = gegevens, string = reden. */
    private function read(array $v, array $knownModels): array|string
    {
        try {
            $html = DealerSite::page($v['link']);
            if ($html === null) {
                return 'pagina niet bereikbaar';
            }

            return DealerSite::parse($html, $v['title'], DealerSite::mediaUrl($v['media']), $knownModels);
        } catch (\Throwable $e) {
            return 'fout bij ophalen: ' . $e->getMessage();
        }
    }
}
