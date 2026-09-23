<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Bron voor de klantreviews op de site. Levert de door `reviews:fetch` opgehaalde
 * echte Google-reviews (gecachet in storage), en valt terug op de vaste lijst in
 * config/brand.php zodra dat bestand er (nog) niet is. Zo toont een verse deploy
 * altijd echte reviews, en ververst het geplande command ze zonder codewijziging.
 */
class Reviews
{
    public const CACHE = 'reviews.json';

    /**
     * @return list<array{name:string,rating:int,text:string}>
     */
    public static function all(): array
    {
        $reviews = config('brand.testimonials', []);
        try {
            if (Storage::exists(self::CACHE)) {
                $data = json_decode((string) Storage::get(self::CACHE), true);
                if (is_array($data) && $data !== []) {
                    $reviews = $data;
                }
            }
        } catch (\Throwable $e) {
            // Storage onbereikbaar: val terug op de config-lijst.
        }

        // Verborgen reviews (brand.reviews.hidden), ongeacht hoofdletters.
        $hidden = array_map('mb_strtolower', config('brand.reviews.hidden', []));

        return array_values(array_filter($reviews, fn ($r) => ! in_array(mb_strtolower($r['name'] ?? ''), $hidden, true)));
    }

    public const SUMMARY = 'reviews-summary.json';

    /**
     * Google-score en aantal reviews: de laatst opgehaalde stand, anders de
     * vaste waarden uit config/brand.php.
     *
     * @return array{rating:float,count:int}
     */
    public static function summary(): array
    {
        $fallback = ['rating' => (float) config('brand.reviews.rating'), 'count' => (int) config('brand.reviews.count')];

        try {
            $data = Storage::exists(self::SUMMARY) ? json_decode((string) Storage::get(self::SUMMARY), true) : null;
        } catch (\Throwable $e) {
            $data = null;
        }

        return isset($data['rating'], $data['count']) && $data['count'] > 0
            ? ['rating' => (float) $data['rating'], 'count' => (int) $data['count']]
            : $fallback;
    }
}
