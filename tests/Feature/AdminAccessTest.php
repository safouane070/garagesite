<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
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

    /** Verwijderen wist auto, fotorijen en fotomap; aanvragen over die auto blijven (zonder koppeling). */
    public function test_admin_deletes_a_car_with_its_photos_but_keeps_its_leads(): void
    {
        Storage::fake('public');
        $car = Car::factory()->create(['slug' => 'audi-weg']);
        $car->images()->create(['path' => 'cars/audi-weg/0.webp', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('public')->put('cars/audi-weg/0.webp', 'foto');
        $other = Car::factory()->create(['slug' => 'audi-blijft']);
        Storage::disk('public')->put('cars/audi-blijft/0.webp', 'foto');
        $lead = Lead::create(['car_id' => $car->id, 'type' => 'vraag', 'name' => 'Jan', 'email' => 'jan@example.com']);

        $this->actingAs(User::factory()->create())->get(route('admin.cars.edit', $car))->assertOk();
        $this->actingAs(User::factory()->create())->delete(route('admin.cars.destroy', $car))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertModelMissing($car);
        $this->assertDatabaseCount('car_images', 0);
        Storage::disk('public')->assertMissing('cars/audi-weg/0.webp');
        Storage::disk('public')->assertExists('cars/audi-blijft/0.webp'); // alleen de eigen map
        $this->assertModelExists($other);
        $this->assertNull($lead->fresh()->car_id);
    }

    public function test_admin_changes_status_and_unknown_status_is_refused(): void
    {
        $car = Car::factory()->create(['status' => 'available']);
        $admin = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.cars.status', $car), ['status' => 'sold'])->assertRedirect();
        $this->assertSame('sold', $car->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.cars.status', $car), ['status' => 'weg'])->assertSessionHasErrors('status');
        $this->assertSame('sold', $car->fresh()->status->value);
    }
}
