<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Maakt autofoto's web-klaar: rechtop gedraaid (EXIF), verkleind tot maximaal
 * MAX_EDGE px, als WebP gecomprimeerd, plus een miniatuur van THUMB_WIDTH px
 * breed voor de kaartjes. Een telefoonfoto van 4–8 MB wordt zo ±200–400 kB, de
 * miniatuur ±30–60 kB.
 *
 * Kan het niet veilig (geen GD/exif, onbekend formaat, of te weinig geheugen
 * voor een enorme afbeelding), dan slaan we het origineel ongewijzigd op:
 * liever een grote foto dan een scheve of een gecrasht proces.
 */
class ImageOptimizer
{
    public const MAX_EDGE = 2000;

    public const THUMB_WIDTH = 640;

    private const QUALITY = 82;

    /** Boven dit aantal pixels decoderen we nooit (ook niet met veel geheugen). */
    private const MAX_PIXELS = 40_000_000;

    /**
     * @return array{path:string,thumb_path:?string,width:?int,height:?int}
     */
    public static function store(UploadedFile $file, string $dir): array
    {
        $source = $file->getRealPath();
        $image = self::load($source);

        if ($image === null) {
            $info = @getimagesize($source) ?: [null, null];

            return ['path' => $file->store($dir, 'public'), 'thumb_path' => null, 'width' => $info[0], 'height' => $info[1]];
        }

        $image = self::downscale(self::orient($image, $source), self::MAX_EDGE);
        $name = Str::random(40);

        $path = "{$dir}/{$name}.webp";
        Storage::disk('public')->put($path, self::webp($image));

        return ['path' => $path] + self::thumbnail($image, $dir, $name);
    }

    /**
     * Miniatuur + afmetingen voor een al opgeslagen foto (bestaande voorraad).
     *
     * @return array{thumb_path:?string,width:int,height:int}|null
     */
    public static function describeStored(string $path): ?array
    {
        $disk = Storage::disk('public');
        if (! $disk->exists($path)) {
            return null;
        }

        $image = self::load($disk->path($path));
        if ($image === null) {
            return null;
        }

        return self::thumbnail($image, dirname($path), pathinfo($path, PATHINFO_FILENAME));
    }

    /** @return array{thumb_path:?string,width:int,height:int} */
    private static function thumbnail(\GdImage $image, string $dir, string $name): array
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $thumbPath = null;

        // Alleen als de foto duidelijk breder is dan de miniatuur.
        if ($w > self::THUMB_WIDTH * 1.2) {
            $thumb = imagescale($image, self::THUMB_WIDTH, (int) round($h * self::THUMB_WIDTH / $w), IMG_BICUBIC);
            $thumbPath = "{$dir}/thumbs/{$name}.webp";
            Storage::disk('public')->put($thumbPath, self::webp($thumb));
        }

        return ['thumb_path' => $thumbPath, 'width' => $w, 'height' => $h];
    }

    private static function webp(\GdImage $image): string
    {
        ob_start();
        imagewebp($image, null, self::QUALITY);

        return (string) ob_get_clean();
    }

    private static function load(string $file): ?\GdImage
    {
        if (! function_exists('imagewebp') || ! function_exists('exif_read_data')) {
            return null;
        }

        $info = @getimagesize($file);
        if (! $info || $info[0] * $info[1] > self::MAX_PIXELS || ! self::fitsInMemory($info[0] * $info[1])) {
            return null;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($file),
            IMAGETYPE_PNG => @imagecreatefrompng($file),
            IMAGETYPE_WEBP => @imagecreatefromwebp($file),
            default => false,
        };

        return $image ?: null;
    }

    /**
     * Past het decoderen in het geheugen? Een beeld kost uitgepakt ±4 bytes per
     * pixel, en rechtdraaien maakt tijdelijk een kopie (×2), plus marge. Past
     * het niet, dan liever het origineel opslaan dan het proces laten crashen.
     */
    private static function fitsInMemory(int $pixels): bool
    {
        $limit = ini_parse_quantity((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true; // geen limiet
        }

        return memory_get_usage(true) + $pixels * 4 * 2.5 < $limit;
    }

    /** Telefoons slaan foto's "liggend" op met een draai-tag; pas die toe. */
    private static function orient(\GdImage $image, string $file): \GdImage
    {
        $exif = @exif_read_data($file);
        $angle = match ((int) ($exif['Orientation'] ?? 1)) {
            3 => 180,
            6 => -90,
            8 => 90,
            default => 0,
        };

        return $angle === 0 ? $image : imagerotate($image, $angle, 0);
    }

    private static function downscale(\GdImage $image, int $maxEdge): \GdImage
    {
        $w = imagesx($image);
        $h = imagesy($image);
        $scale = $maxEdge / max($w, $h);

        return $scale >= 1
            ? $image
            : imagescale($image, (int) round($w * $scale), (int) round($h * $scale), IMG_BICUBIC);
    }
}
