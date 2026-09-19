<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Support\DealerListing;
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

        $assignment = DealerListing::match($cars, DealerListing::dealerSlugs($paths));

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
                    ->get(DealerListing::BASE . $slug . '/');
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

    /** Haalt de <li>-labels uit de "optionstest"-lijst van de voertuig-pagina. */
    private function extractOptions(string $html): array
    {
        if (! preg_match('#<ul[^>]*optionstest[^>]*>(.*?)</ul>#is', $html, $m)) {
            return [];
        }

        preg_match_all('#<li[^>]*>(.*?)</li>#is', $m[1], $lis);

        $out = [];
        foreach ($lis[1] as $li) {
            $label = self::cleanLabel(html_entity_decode(strip_tags($li), ENT_QUOTES));
            // Nette optielabels; sla lege of absurd lange fragmenten over.
            if ($label !== '' && mb_strlen($label) <= 80) {
                $out[$label] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * Schoont een optielabel op: spaties normaliseren en verdubbelde
     * inch-tekens (bv. "Lichtmetalen velgen 18\"\"\"") terug naar één ".
     */
    public static function cleanLabel(string $label): string
    {
        $label = preg_replace('/\s+/', ' ', $label);
        $label = preg_replace('/"{2,}/', '"', $label);

        return trim($label);
    }
}
