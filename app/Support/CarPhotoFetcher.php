<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

/**
 * Haalt professionele autofoto's op bij Unsplash via het publieke zoek-endpoint
 * (napi). Unsplash-foto's vallen onder de Unsplash-licentie: vrij te gebruiken,
 * ook commercieel, zonder toestemming. Geen API-sleutel nodig.
 *
 * We filteren de betaalde "Unsplash+"-resultaten (plus.unsplash.com) eruit en
 * geven liggende JPEG's op een nette breedte terug. Lukt het niet (geen
 * internet), dan valt de seeder terug op SVG-placeholders in de huisstijl.
 */
class CarPhotoFetcher
{
    private const ENDPOINT = 'https://unsplash.com/napi/search/photos';
    private const USER_AGENT = 'Mozilla/5.0 (AutobedrijfRijswijkDemo; educational demo)';

    /**
     * @return array<int, string> Lijst van afbeeldings-URL's.
     */
    public function fetch(string $query, int $limit = 4, int $width = 1600): array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => self::USER_AGENT,
                'Accept' => 'application/json',
            ])
                ->timeout(25)
                ->retry(2, 400)
                ->get(self::ENDPOINT, [
                    'query' => $query,
                    'per_page' => 30,
                    'orientation' => 'landscape',
                    'content_filter' => 'high',
                ]);
        } catch (\Throwable $e) {
            return []; // geen internet? vangnet in de seeder neemt het over
        }

        if (! $response->ok()) {
            return [];
        }

        return collect($response->json('results', []))
            ->map(fn ($photo) => $photo['urls']['raw'] ?? null)
            ->filter()
            // Alleen gratis Unsplash-foto's (images.unsplash.com); de betaalde
            // "Unsplash+"-beelden staan op plus.unsplash.com en vallen af.
            ->filter(fn (string $url) => str_starts_with($url, 'https://images.unsplash.com/'))
            // De raw-URL heeft al een querystring (?ixid=...), dus we hangen de
            // schaal-parameters met & aan: nette JPEG op $width breed.
            ->map(fn (string $url) => $url . '&w=' . $width . '&q=80&fm=jpg&fit=max')
            ->unique()
            ->take($limit)
            ->values()
            ->all();
    }
}
