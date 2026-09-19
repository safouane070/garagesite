<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Vult per auto de ECHTE uitrusting/opties, opgehaald van de voertuig-pagina's
 * van de dealersite (autobedrijfrijswijk.nl). De voertuig-slug komt uit de
 * originele Marktplaats-listing (zelfde bron waaruit de auto's zijn geseed).
 *
 * De onze-auto ↔ dealer-pagina match gaat op genormaliseerde slug (koppeltekens
 * en punten weg, zodat "1-4-tfsi" en "14-tfsi" gelijk zijn), via de langste
 * gemeenschappelijke voorloop en een 1-op-1-toewijzing. Zo raken bijna-identieke
 * varianten (bv. meerdere Cupra Formentor 2.0 TSI) niet door elkaar.
 *
 * Geen verzinsels: staat er geen pagina of geen optielijst, dan blijft de auto
 * ongewijzigd. Herbruikbaar: `php artisan cars:fill-options [--force]`.
 */
class FillCarOptions extends Command
{
    protected $signature = 'cars:fill-options {--force : Ook auto\'s die al opties hebben opnieuw ophalen}';

    protected $description = 'Haalt echte opties per auto op van de voertuig-pagina\'s van de dealersite.';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    public function handle(): int
    {
        $paths = json_decode((string) file_get_contents(database_path('seeders/rijswijk_listings.json')), true) ?: [];
        $cars = Car::all();

        // Dealer-slug = het deel ná "m<cijfers>-" in de Marktplaats-listing.
        $dealerSlugs = [];
        foreach ($paths as $path) {
            if (preg_match('#/m\d+-(.+)$#', $path, $mm)) {
                $dealerSlugs[] = $mm[1];
            }
        }

        $assignment = $this->matchCarsToDealerSlugs($cars, $dealerSlugs);

        $filled = 0;
        $skipped = 0;
        $force = (bool) $this->option('force');

        foreach ($assignment as $carId => $slug) {
            $car = $cars->firstWhere('id', $carId);
            if (! $car || (! $force && ! empty($car->options))) {
                continue;
            }

            try {
                $resp = Http::withHeaders(['User-Agent' => self::UA])
                    ->timeout(25)->retry(2, 400)
                    ->get("https://autobedrijfrijswijk.nl/voertuig/{$slug}/");
            } catch (\Throwable $e) {
                $skipped++;
                continue;
            }

            if (! $resp->ok()) {
                $skipped++;
                continue;
            }

            $options = $this->extractOptions($resp->body());
            if (empty($options)) {
                $skipped++;
                continue;
            }

            $car->update(['options' => $options]);
            $filled++;
            $this->line("  ✓ {$car->title()} — " . count($options) . ' opties');
        }

        $withOptions = Car::whereNotNull('options')->where('options', '!=', '[]')->count();
        $this->info("Klaar: {$filled} gevuld, {$skipped} overgeslagen (geen pagina/opties). Totaal met opties: {$withOptions} van {$cars->count()}.");

        return self::SUCCESS;
    }

    /**
     * Koppelt elke auto aan hoogstens één dealer-slug via genormaliseerde
     * gemeenschappelijke voorloop, greedy en 1-op-1 (beste score eerst).
     *
     * @return array<int,string>  carId => dealerSlug
     */
    private function matchCarsToDealerSlugs($cars, array $dealerSlugs): array
    {
        $norm = fn (string $s): string => preg_replace('/[^a-z0-9]/', '', strtolower($s));

        $pairs = [];
        foreach ($dealerSlugs as $di => $ds) {
            $dn = $norm($ds);
            foreach ($cars as $car) {
                $cn = $norm($car->slug);
                if ($cn === '') {
                    continue;
                }
                $score = $this->commonPrefixLength($dn, $cn);
                // Match moet (bijna) de hele auto-slug dekken: voorkomt dat een
                // korte slug per ongeluk op een verre auto plakt.
                if ($score >= min(strlen($cn), 20)) {
                    $pairs[] = ['score' => $score, 'di' => $di, 'cid' => $car->id, 'slug' => $ds];
                }
            }
        }

        usort($pairs, fn ($a, $b) => $b['score'] <=> $a['score']);

        $usedDealer = [];
        $usedCar = [];
        $out = [];
        foreach ($pairs as $p) {
            if (isset($usedDealer[$p['di']]) || isset($usedCar[$p['cid']])) {
                continue;
            }
            $usedDealer[$p['di']] = true;
            $usedCar[$p['cid']] = true;
            $out[$p['cid']] = $p['slug'];
        }

        return $out;
    }

    private function commonPrefixLength(string $a, string $b): int
    {
        $n = min(strlen($a), strlen($b));
        $i = 0;
        while ($i < $n && $a[$i] === $b[$i]) {
            $i++;
        }

        return $i;
    }

    /** Haalt de <li>-labels uit de "optionstest"-lijst van de voertuig-pagina. */
    private function extractOptions(string $html): array
    {
        if (! preg_match('#<ul[^>]*optionstest[^>]*>(.*?)</ul>#is', $html, $m)) {
            return [];
        }

        preg_match_all('#<li[^>]*>(.*?)</li>#is', $m[1], $lis);

        $out = [];
        foreach ($lis[1] as $li) {
            $label = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($li), ENT_QUOTES)));
            // Nette optielabels; sla lege of absurd lange fragmenten over.
            if ($label !== '' && mb_strlen($label) <= 80) {
                $out[$label] = true;
            }
        }

        return array_keys($out);
    }
}
