<?php

namespace App\Console\Commands;

use App\Support\Reviews;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Haalt de ECHTE Google-reviews van de zaak op via de publieke Trustindex-feed
 * (zelfde bron die hun eigen site toont) en cachet ze, zodat de site ze in de
 * eigen huisstijl toont en ze automatisch vers blijven zonder codewijziging.
 * Plan dit periodiek in (zie routes/console.php).
 *
 * Geen verzinsels: alleen naam, score en tekst zoals op Google; enkel
 * kennelijke typefouten (verdubbelde spaties) en emoji worden opgeschoond.
 */
class FetchReviews extends Command
{
    protected $signature = 'reviews:fetch {--min=4 : Minimale sterrenscore om te tonen}';

    protected $description = 'Haalt de echte Google-reviews op via de Trustindex-feed en cachet ze.';

    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    public function handle(): int
    {
        $id = (string) config('brand.reviews.trustindex_widget_id');
        if ($id === '') {
            $this->error('Geen brand.reviews.trustindex_widget_id ingesteld.');
            return self::FAILURE;
        }

        // Widget-content: cdn.trustindex.io/widgets/<eerste2>/<id>/content.html
        $url = "https://cdn.trustindex.io/widgets/" . substr($id, 0, 2) . "/{$id}/content.html";

        try {
            $resp = Http::withHeaders(['User-Agent' => self::UA])->timeout(25)->retry(2, 400)->get($url);
        } catch (\Throwable $e) {
            $this->error('Ophalen mislukt: ' . $e->getMessage());
            return self::FAILURE;
        }

        if (! $resp->ok()) {
            $this->error("Ophalen mislukt (HTTP {$resp->status()}).");
            return self::FAILURE;
        }

        // Totaalscore uit de widget-voet: "<strong>4.7</strong> van 5, … <strong>227 recensies</strong>".
        if (preg_match('#<strong>([0-5][.,]\d)</strong>\s*van 5.*?<strong>(\d+)\s+recensies#s', $resp->body(), $s)) {
            Storage::put(Reviews::SUMMARY, json_encode(['rating' => (float) str_replace(',', '.', $s[1]), 'count' => (int) $s[2]]));
            $this->info("Google-score {$s[1]} uit {$s[2]} recensies.");
        } else {
            $this->warn('Totaalscore niet gevonden — vaste waarden uit config blijven staan.');
        }

        $reviews = $this->parse($resp->body(), (int) $this->option('min'));

        if (empty($reviews)) {
            $this->warn('Geen reviews gevonden — cache ongewijzigd gelaten.');
            return self::SUCCESS;
        }

        Storage::put(Reviews::CACHE, json_encode($reviews, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        $this->info(count($reviews) . ' reviews opgehaald en gecachet.');

        return self::SUCCESS;
    }

    /**
     * @return list<array{name:string,rating:int,text:string}>
     */
    private function parse(string $html, int $min): array
    {
        $dec = fn (string $s) => trim(preg_replace('/\s+/', ' ', html_entity_decode(strip_tags($s), ENT_QUOTES)));
        // Emoji en losse symbolen weg voor een nette weergave.
        $stripEmoji = fn (string $s) => trim(preg_replace('/[\x{1F000}-\x{1FAFF}\x{2600}-\x{27BF}\x{FE0F}\x{2190}-\x{21FF}\x{2B00}-\x{2BFF}]/u', '', $s));

        $blocks = preg_split('#<div class="ti-review-item source-Google#', $html);
        array_shift($blocks);

        $out = [];
        foreach ($blocks as $b) {
            preg_match('#data-rating="([0-9.]+)"#', $b, $mr);
            preg_match('#ti-name">\s*(.*?)\s*</div>#is', $b, $mn);
            preg_match('#ti-review-content">(.*?)</div>#is', $b, $mt);

            $rating = isset($mr[1]) ? (int) round((float) $mr[1]) : 0;
            $name = isset($mn[1]) ? $dec($mn[1]) : '';
            $text = isset($mt[1]) ? trim($stripEmoji($dec($mt[1]))) : '';

            if ($name === '' || $text === '' || $rating < $min) {
                continue;
            }
            $out[] = ['name' => $name, 'rating' => $rating, 'text' => $text];
        }

        return $out;
    }
}
