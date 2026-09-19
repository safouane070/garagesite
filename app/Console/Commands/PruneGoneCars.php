<?php

namespace App\Console\Commands;

use App\Models\Car;
use App\Support\DealerListing;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Verwijdert auto's die niet meer op de dealersite staan: hun voertuig-pagina
 * geeft 404/410 (uit de voorraad gehaald), dus tonen we ze niet langer als
 * beschikbaar. Alleen auto's zónder opgehaalde opties zijn kandidaat — auto's
 * mét opties zijn per definitie nog live (pagina gaf 200). Een auto die live is
 * maar toevallig geen optielijst heeft, blijft staan.
 *
 * Draai altijd eerst `--dry-run` om te zien wat er weggaat.
 * Herbruikbaar: `php artisan cars:prune-gone [--dry-run]`.
 */
class PruneGoneCars extends Command
{
    protected $signature = 'cars:prune-gone {--dry-run : Toon alleen wat verwijderd zou worden}';

    protected $description = 'Verwijdert auto\'s waarvan de voertuig-pagina op de dealersite verdwenen is (404/410).';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $paths = json_decode((string) file_get_contents(database_path('seeders/rijswijk_listings.json')), true) ?: [];
        $cars = Car::all();
        $assignment = DealerListing::match($cars, $paths);

        // Kandidaten: auto's zonder opgehaalde opties (auto's mét opties zijn live).
        $candidates = $cars->filter(fn (Car $c) => empty($c->options));

        $gone = [];
        foreach ($candidates as $car) {
            $slug = $assignment[$car->id] ?? null;
            // Geen dealer-slug om te controleren: laten staan, niet gokken.
            if (! $slug) {
                continue;
            }

            if (in_array($this->pageStatus(DealerListing::BASE . $slug . '/'), [404, 410], true)) {
                $gone[] = $car;
            }
        }

        if (empty($gone)) {
            $this->info('Geen verdwenen auto\'s gevonden. Niets te verwijderen.');
            return self::SUCCESS;
        }

        $this->line(($dry ? 'ZOU VERWIJDEREN' : 'VERWIJDEREN') . ' — ' . count($gone) . " auto's (weg van de dealersite):");
        foreach ($gone as $car) {
            $this->line("  · {$car->title()}");
        }

        if ($dry) {
            $this->info('Dry-run: er is niets gewijzigd. Draai zonder --dry-run om te verwijderen.');
            return self::SUCCESS;
        }

        foreach ($gone as $car) {
            DB::transaction(function () use ($car) {
                Storage::disk('public')->deleteDirectory("cars/{$car->slug}");
                $car->images()->delete();
                $car->delete();
            });
        }

        $this->info('Verwijderd: ' . count($gone) . " auto's. Resterend in de catalogus: " . Car::count() . '.');

        return self::SUCCESS;
    }

    private function pageStatus(string $url): int
    {
        try {
            // Bewust géén retry: retry() gooit bij een 404 een exception ná de
            // pogingen, waardoor een echt verdwenen pagina als "houden" zou
            // gelden. Een kale get geeft de 404 gewoon als status terug.
            return Http::withHeaders(['User-Agent' => self::UA])->timeout(20)->get($url)->status();
        } catch (\Throwable $e) {
            // Onbereikbaar (timeout/DNS) ≠ bewezen verdwenen: behandel als "houden".
            return 0;
        }
    }
}
