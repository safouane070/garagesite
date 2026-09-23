<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Slaat een geüploade autofoto web-klaar op: rechtop gedraaid (EXIF), verkleind
 * tot maximaal MAX_EDGE px aan de langste zijde en als WebP gecomprimeerd. Een
 * telefoonfoto van 4–8 MB wordt zo ±200–400 kB — snel op de site, zuinig met opslag.
 *
 * Kan het niet veilig (geen GD/exif, onbekend formaat of een extreem grote
 * afbeelding die het geheugen zou opblazen), dan slaan we het origineel
 * ongewijzigd op: liever een grote foto dan een scheve of kapotte.
 */
class ImageOptimizer
{
    public const MAX_EDGE = 2000;

    private const QUALITY = 82;

    /** Boven dit aantal pixels decoderen we niet (geheugen: ±4 bytes per pixel). */
    private const MAX_PIXELS = 40_000_000;

    /** @return string pad op de public-disk */
    public static function store(UploadedFile $file, string $dir): string
    {
        $image = self::load($file);

        if ($image === null) {
            return $file->store($dir, 'public');
        }

        $image = self::orient($image, $file);
        $image = self::downscale($image);

        ob_start();
        imagewebp($image, null, self::QUALITY);
        $binary = (string) ob_get_clean();

        $path = $dir . '/' . Str::random(40) . '.webp';
        Storage::disk('public')->put($path, $binary);

        return $path;
    }

    private static function load(UploadedFile $file): ?\GdImage
    {
        if (! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
            return null;
        }

        $info = @getimagesize($file->getRealPath());
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file->getRealPath()),
            IMAGETYPE_PNG => @imagecreatefrompng($file->getRealPath()),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file->getRealPath()),
            default => false,
        };

        return $image ?: null;
    }

    /** Telefoons slaan foto's "liggend" op met een draai-tag; pas die toe. */
    private static function orient(\GdImage $image, UploadedFile $file): \GdImage
    {
        $exif = @exif_read_data($file->getRealPath());
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        if ($angle === 0) {
            return $image;
        }

        $rotated = imagerotate($image, $angle, 0);

        return $rotated;
    }

    private static function downscale(\GdImage $image): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = self::MAX_EDGE / max($w, $h);

        if ($scale >= 1) {
            return $image;
        }

        $scaled = imagescale($image, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC);

        return $scaled;
    }
}
