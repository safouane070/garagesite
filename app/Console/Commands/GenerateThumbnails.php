<?php

namespace App\Console\Commands;

use App\Models\CarImage;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;

/**
 * Geeft bestaande foto's alsnog hun verkleinde varianten (xs/miniatuur/md) en
 * afmetingen. Idempotent: foto's die al compleet zijn worden overgeslagen.
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'images:thumbs';

    protected $description = "Maakt miniaturen + afmetingen voor bestaande autofoto's.";

    public function handle(): int
    {
        [$done, $skipped] = [0, 0];

        $xs = ImageOptimizer::VARIANTS['xs_path'][0] * 1.2;
        CarImage::whereNull('width')
            ->orWhere(fn ($q) => $q->whereNull('xs_path')->where('width', '>', $xs))
            ->chunkById(100, function ($images) use (&$done, &$skipped) {
            foreach ($images as $image) {
                $info = ImageOptimizer::describeStored($image->path);
                if ($info === null) {
                    $skipped++;
                    continue;
                }
                $image->update($info);
                $done++;
            }
        });

        $this->info("Klaar: {$done} foto's voorzien van miniatuur/afmetingen, {$skipped} overgeslagen (extern of onleesbaar).");

        return self::SUCCESS;
    }
}
