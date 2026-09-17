<?php

namespace Database\Factories;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

/**
 * @extends Factory<Car>
 *
 * Genereert plausibele, willekeurige auto's. We kiezen uit realistische
 * lijstjes i.p.v. volledig random tekst, zodat de data geloofwaardig blijft.
 */
class CarFactory extends Factory
{
    public function definition(): array
    {
        $brands = [
            'BMW' => ['320d', '118i', 'X3', 'M340i'],
            'Audi' => ['A4', 'A3', 'Q5', 'A6'],
            'Volkswagen' => ['Golf', 'Passat', 'Tiguan', 'Polo'],
            'Mercedes-Benz' => ['C 220 d', 'A 180', 'GLC 300', 'E 350 e'],
            'Volvo' => ['V60', 'XC40', 'XC60', 'S90'],
            'Tesla' => ['Model 3', 'Model Y'],
        ];

        $brand = Arr::random(array_keys($brands));
        $model = Arr::random($brands[$brand]);
        $year = fake()->numberBetween(2016, 2024);
        $fuel = Arr::random(['Benzine', 'Diesel', 'Elektrisch', 'Hybride']);

        return [
            'brand' => $brand,
            'model' => $model,
            'variant' => Arr::random([null, 'M Sport', 'S line', 'R-Design', 'Business']),
            'year' => $year,
            'price' => fake()->numberBetween(12, 68) * 1000 - 50,
            'mileage' => fake()->numberBetween(5, 210) * 1000,
            'fuel_type' => $fuel,
            'transmission' => Arr::random(['Automaat', 'Handgeschakeld']),
            'color' => Arr::random(['Zwart', 'Wit', 'Grijs', 'Blauw', 'Zilver', 'Rood']),
            'body_type' => Arr::random(['Sedan', 'Hatchback', 'Stationwagen', 'SUV']),
            'description' => fake()->paragraph(4),
            'specs' => [
                'vermogen_pk' => fake()->numberBetween(90, 340),
                'cilinderinhoud_cc' => $fuel === 'Elektrisch' ? null : Arr::random([1499, 1968, 1995, 2993]),
                'deuren' => Arr::random([3, 5]),
                'zitplaatsen' => 5,
                'aandrijving' => Arr::random(['Voorwielaandrijving', 'Achterwielaandrijving', 'Vierwielaandrijving']),
            ],
            'status' => Arr::random(CarStatus::cases()),
            'is_featured' => fake()->boolean(25),
        ];
    }
}
