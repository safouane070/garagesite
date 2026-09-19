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
        try {
            if (Storage::exists(self::CACHE)) {
                $data = json_decode((string) Storage::get(self::CACHE), true);
                if (is_array($data) && $data !== []) {
                    return $data;
                }
            }
        } catch (\Throwable $e) {
            // Storage onbereikbaar: val terug op de config-lijst.
        }

        return config('brand.testimonials', []);
    }
}
