<?php

namespace App\Console\Commands;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Console\Command;

/**
 * Houdt de opslag in toom: een auto die al een tijd verkocht is, heeft zijn
 * volledige galerij (15–40 foto's × 4 maten) niet meer nodig. De omslagfoto
 * blijft, voor "met trots verkocht". Dagelijks ingepland.
 */
class PruneSoldPhotos extends Command
{
    protected $signature = 'cars:prune-photos {--days=60 : Zo lang na verkoop blijft de hele galerij staan}';

    protected $description = "Verwijdert de extra foto's van auto's die al een tijd verkocht zijn (omslagfoto blijft).";

    public function handle(): int
    {
        [$cars, $photos] = [0, 0];

        // updated_at: het moment van "verkocht" zetten (verkochte auto's worden daarna niet meer bewerkt).
        Car::where('status', CarStatus::Sold->value)
            ->where('updated_at', '<', now()->subDays((int) $this->option('days')))
            ->has('images', '>', 1)
            ->with('images')
            ->each(function (Car $car) use (&$cars, &$photos) {
                // images() staat op omslag-eerst + volgorde: de eerste blijft altijd, ook zonder omslagvlag.
                $extra = $car->images->slice(1);
                $car->images()->whereKey($extra->modelKeys())->delete();
                $extra->each->deleteFiles();
                $cars++;
                $photos += $extra->count();
            });

        $this->info("Klaar: {$photos} foto's verwijderd bij {$cars} verkochte auto's (omslagfoto's blijven).");

        return self::SUCCESS;
    }
}
