<?php

namespace Tests\Feature;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_from_the_car_beheer(): void
    {
        $car = Car::factory()->create();

        $this->get(route('admin.cars.index'))->assertRedirect(route('login'));
        $this->get(route('admin.cars.edit', $car))->assertRedirect(route('login'));
    }

    public function test_guest_cannot_update_a_car(): void
    {
        $car = Car::factory()->create(['price' => 25000]);

        $this->put(route('admin.cars.update', $car), ['price' => 1])
            ->assertRedirect(route('login'));

        $this->assertEquals(25000, $car->fresh()->price);
    }

    public function test_guest_cannot_delete_a_car(): void
    {
        $car = Car::factory()->create();

        $this->delete(route('admin.cars.destroy', $car))->assertRedirect(route('login'));

        $this->assertDatabaseHas('cars', ['id' => $car->id]);
    }
}
