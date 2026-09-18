<?php

namespace Database\Seeders;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Database\Seeder;

/**
 * Zet een handvol occasions op "verkocht" zodat de "met trots verkocht"-sectie
 * op de homepage iets te tonen heeft. Verzint verder GEEN gegevens: opties en
 * specs blijven zoals ze uit de bron komen; echte uitrusting voegt de beheerder
 * per auto toe via het dashboard.
 *
 * Idempotent: draait deterministisch en stopt zodra er genoeg verkocht zijn.
 */
class CarEnrichmentSeeder extends Seeder
{
    public function run(): void
    {
        $alreadySold = Car::where('status', CarStatus::Sold->value)->count();
        $need = 6 - $alreadySold;

        if ($need > 0) {
            Car::where('status', CarStatus::Available->value)
                ->orderByDesc('mileage')
                ->take($need)
                ->get()
                ->each(fn (Car $car) => $car->update(['status' => CarStatus::Sold->value]));
        }

        $this->command?->info('Verkocht-status ingesteld voor de homepage-showcase.');
    }
}
