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

    public function test_home_shows_no_sales_claim_or_sold_car_grid(): void
    {
        foreach (['X5', 'Q8', 'Cayenne'] as $model) {
            Car::factory()->create(['status' => CarStatus::Sold, 'model' => $model, 'slug' => "verkocht-{$model}"]);
        }
        $this->get('/')
            ->assertOk()
            ->assertDontSee('de deur uit in de afgelopen')
            ->assertDontSee('verkocht-X5')
            ->assertDontSee('Deze klanten waren je voor');
    }

    public function test_too_few_sales_shows_no_claim(): void
    {
        Car::factory()->create(['status' => CarStatus::Sold]);

        $this->get('/')->assertOk()->assertDontSee('de deur uit in de afgelopen');
    }

    /** Rustige hero: de nieuwste auto's staan één keer op de pagina (in de rail), niet ook in de hero. */
    public function test_newest_cars_appear_once_in_the_rail(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'price' => 30000, 'mileage' => 40000, 'is_featured' => false]);

        $html = $this->get('/')->assertOk()->assertSee('Net binnengereden')->getContent();
        $this->assertSame(1, substr_count($html, 'href="' . route('cars.show', $car) . '"'));
    }
}
