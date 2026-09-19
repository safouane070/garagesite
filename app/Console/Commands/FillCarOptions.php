<?php

namespace App\Console\Commands;

use App\Models\Car;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

/**
 * Vult per auto de ECHTE uitrusting/opties, opgehaald van de voertuig-pagina's
 * van de dealersite (autobedrijfrijswijk.nl). De voertuig-slug is af te leiden
 * uit de originele Marktplaats-listing (zelfde bron waaruit de auto's zijn
 * geseed), zodat de match 1-op-1 is. Geen verzinsels: staat er geen pagina of
 * geen optielijst, dan blijft de auto ongewijzigd.
 *
 * Herbruikbaar: `php artisan cars:fill-options`.
 */
class FillCarOptions extends Command
{
    protected $signature = 'cars:fill-options';

    protected $description = 'Haalt echte opties per auto op van de voertuig-pagina\'s van de dealersite.';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    public function handle(): int
    {
        $paths = json_decode((string) file_get_contents(database_path('seeders/rijswijk_listings.json')), true) ?: [];
        $cars = Car::all();
        $filled = 0;
        $skipped = 0;

        foreach ($paths as $path) {
            // De voertuig-slug op de dealersite = het deel ná "m<cijfers>-".
            if (! preg_match('#/m\d+-(.+)$#', $path, $mm)) {
                $skipped++;
                continue;
            }
            $slug = $mm[1];

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

            // Match onze auto: onze slug is een prefix van de (langere) dealer-slug.
            $car = $cars->filter(fn (Car $c) => $c->slug !== '' && str_starts_with($slug, $c->slug))
                ->sortByDesc(fn (Car $c) => strlen($c->slug))
                ->first();

            if (! $car) {
                $skipped++;
                continue;
            }

            $car->update(['options' => $options]);
            $filled++;
            $this->line("  ✓ {$car->title()} — " . count($options) . ' opties');
        }

        $this->info("Klaar: {$filled} auto's gevuld, {$skipped} overgeslagen.");

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
            $label = trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($li), ENT_QUOTES)));
            // Nette optielabels; sla lege of absurd lange fragmenten over.
            if ($label !== '' && mb_strlen($label) <= 80) {
                $out[$label] = true;
            }
        }

        return array_keys($out);
    }
}
