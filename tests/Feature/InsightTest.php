<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use App\Models\User;
use App\Support\CarDescription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InsightTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_views_are_counted_without_touching_updated_at(): void
    {
        $car = Car::factory()->create(['status' => 'available']);
        $car->forceFill(['updated_at' => now()->subDays(3)])->saveQuietly();
        $before = $car->fresh()->updated_at;

        $this->get(route('cars.show', $car));
        $this->get(route('cars.show', $car));

        $this->assertSame(2, $car->fresh()->views);
        $this->assertEquals($before, $car->fresh()->updated_at); // sitemap blijft rustig
    }

    public function test_bots_and_the_admin_are_not_counted(): void
    {
        $car = Car::factory()->create(['status' => 'available']);

        $this->withHeader('User-Agent', 'Googlebot/2.1')->get(route('cars.show', $car));
        $this->withHeader('User-Agent', 'WhatsApp/2.23')->get(route('cars.show', $car));
        $this->actingAs(User::factory()->create())->get(route('cars.show', $car));

        $this->assertSame(0, $car->fresh()->views);
    }

    public function test_no_cookies_set_for_counting(): void
    {
        $car = Car::factory()->create(['status' => 'available']);

        $response = $this->get(route('cars.show', $car));

        // Alleen Laravels eigen sessie/CSRF-cookies; geen tracking-cookie.
        $names = collect($response->headers->getCookies())->map->getName()->all();
        $this->assertEqualsCanonicalizing(['XSRF-TOKEN', config('session.cookie')], $names);
    }

    public function test_admin_sees_views_and_leads_per_car(): void
    {
        $car = Car::factory()->create(['status' => 'available', 'views' => 42]);
        Lead::create(['car_id' => $car->id, 'type' => 'proefrit', 'name' => 'A', 'email' => 'a@example.com']);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))
            ->assertSee('Meest bekeken')
            ->assertSee('42 × bekeken · 1 aanvraag');
    }

    public function test_description_is_unique_per_car_from_real_data(): void
    {
        $a = Car::factory()->make(['brand' => 'Audi', 'model' => 'A4', 'variant' => 'Avant', 'year' => 2021, 'mileage' => 50000,
            'options' => ['Navigatie', 'LED-koplampen', 'BOSE audio'], 'specs' => ['vermogen_pk' => 204]]);
        $b = Car::factory()->make(['brand' => 'Audi', 'model' => 'A4', 'variant' => 'Avant', 'year' => 2021, 'mileage' => 50000,
            'options' => ['Trekhaak', 'Stoelverwarming']]);

        $textA = CarDescription::for($a);

        $this->assertNotSame($textA, CarDescription::for($b));
        $this->assertStringContainsString('navigatie, LED-koplampen en BOSE audio', $textA); // afkortingen intact
        $this->assertStringContainsString('204 pk', $textA);
        $this->assertStringContainsString('50.000 km', $textA);
    }

    public function test_only_generic_descriptions_are_replaceable(): void
    {
        $this->assertTrue(CarDescription::isReplaceable(null));
        $this->assertTrue(CarDescription::isReplaceable('X uit 2020. ' . CarDescription::LEGACY_MARKER));
        $this->assertFalse(CarDescription::isReplaceable('Door de beheerder zelf geschreven tekst.'));
    }
}
