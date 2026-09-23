<?php

namespace App\Console\Commands;

use App\Models\CarImage;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;

/**
 * Geeft bestaande foto's (van vóór de miniaturen) alsnog een miniatuur en hun
 * afmetingen. Idempotent: foto's die al afmetingen hebben worden overgeslagen.
 */
class GenerateThumbnails extends Command
{
    protected $signature = 'images:thumbs';

    protected $description = "Maakt miniaturen + afmetingen voor bestaande autofoto's.";

    public function handle(): int
    {
        [$done, $skipped] = [0, 0];

        CarImage::whereNull('width')->chunkById(100, function ($images) use (&$done, &$skipped) {
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
