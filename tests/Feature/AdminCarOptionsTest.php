<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminCarOptionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_checked_options_are_saved_as_a_clean_array(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create(['options' => ['Oude optie']]);

        $payload = array_merge(
            $car->only(['brand', 'model', 'variant', 'year', 'price', 'mileage', 'fuel_type', 'transmission', 'color', 'body_type', 'description']),
            [
                'status' => $car->status->value,
                'is_featured' => $car->is_featured,
                // Ruwe checkbox-invoer: dubbele en met spaties eromheen.
                'options' => ['Panoramadak', ' Achteruitrijcamera ', 'Panoramadak', 'Eigen optie'],
            ]
        );

        $this->actingAs($user)
            ->put(route('admin.cars.update', $car), $payload)
            ->assertRedirect();

        // Getrimd en ontdubbeld, volgorde behouden.
        $this->assertSame(
            ['Panoramadak', 'Achteruitrijcamera', 'Eigen optie'],
            $car->fresh()->options,
        );
    }

    public function test_saving_without_options_clears_them(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create(['options' => ['Panoramadak']]);

        $payload = array_merge(
            $car->only(['brand', 'model', 'variant', 'year', 'price', 'mileage', 'fuel_type', 'transmission', 'color', 'body_type', 'description']),
            ['status' => $car->status->value, 'is_featured' => $car->is_featured]
        );

        $this->actingAs($user)
            ->put(route('admin.cars.update', $car), $payload)
            ->assertRedirect();

        $this->assertNull($car->fresh()->options);
    }
}
