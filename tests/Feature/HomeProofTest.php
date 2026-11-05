<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeProofTest extends TestCase
{
    use RefreshDatabase;

    public function test_sold_at_follows_the_status(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available]);
        $this->assertNull($car->sold_at);

        $car->update(['status' => CarStatus::Sold]);
        $this->assertNotNull($car->fresh()->sold_at);

        $car->update(['status' => CarStatus::Available]);
        $this->assertNull($car->fresh()->sold_at);
    }

    public function test_home_shows_recent_sales_as_one_line_not_a_car_grid(): void
    {
        foreach (['X5', 'Q8', 'Cayenne'] as $model) {
            Car::factory()->create(['status' => CarStatus::Sold, 'model' => $model, 'slug' => "verkocht-{$model}"]);
        }
        // Buiten het venster: telt niet mee.
        Car::factory()->create(['status' => CarStatus::Sold, 'slug' => 'oud-verkocht'])
            ->forceFill(['sold_at' => now()->subYear()])->save();

        $this->get('/')
            ->assertOk()
            ->assertSee('verkocht in de afgelopen 3 maanden')
            ->assertSeeInOrder(['<span class="font-semibold text-cream tabular">3</span>'], false)
            ->assertDontSee('verkocht-X5')
            ->assertDontSee('Deze klanten waren je voor');
    }

    public function test_too_few_sales_shows_no_claim(): void
    {
        Car::factory()->create(['status' => CarStatus::Sold]);

        $this->get('/')->assertOk()->assertDontSee('verkocht in de afgelopen');
    }

    public function test_hero_lists_newest_cars_with_price(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'price' => 30000, 'mileage' => 40000, 'is_featured' => false]);

        $this->get('/')->assertOk()->assertSee('Net binnen')->assertSee(route('cars.show', $car), false);
    }
}
