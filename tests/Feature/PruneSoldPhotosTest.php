<?php

namespace Tests\Feature;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PruneSoldPhotosTest extends TestCase
{
    use RefreshDatabase;

    private function carWithPhotos(string $status, int $daysAgo, bool $flagCover = true): Car
    {
        $car = Car::factory()->create(['status' => $status]);
        foreach (range(0, 2) as $i) {
            Storage::disk('public')->put("cars/{$car->id}/{$i}.webp", 'x');
            Storage::disk('public')->put("cars/{$car->id}/xs/{$i}.webp", 'x');
            $car->images()->create(['path' => "cars/{$car->id}/{$i}.webp", 'xs_path' => "cars/{$car->id}/xs/{$i}.webp", 'is_primary' => $flagCover && $i === 0, 'sort_order' => $i]);
        }
        Car::whereKey($car->id)->update(['updated_at' => now()->subDays($daysAgo)]);

        return $car;
    }

    public function test_long_sold_cars_keep_only_their_cover(): void
    {
        Storage::fake('public');
        $old = $this->carWithPhotos('sold', 90);
        $noFlag = $this->carWithPhotos('sold', 90, flagCover: false);
        $recent = $this->carWithPhotos('sold', 10);
        $forSale = $this->carWithPhotos('available', 400);

        $this->artisan('cars:prune-photos')->expectsOutputToContain("4 foto's verwijderd bij 2")->assertSuccessful();

        $this->assertSame(["cars/{$old->id}/0.webp"], $old->images()->pluck('path')->all());
        $this->assertSame(["cars/{$noFlag->id}/0.webp"], $noFlag->images()->pluck('path')->all()); // eerste blijft, ook zonder omslagvlag
        Storage::disk('public')->assertExists(["cars/{$old->id}/0.webp", "cars/{$old->id}/xs/0.webp"]);
        Storage::disk('public')->assertMissing(["cars/{$old->id}/1.webp", "cars/{$old->id}/xs/1.webp", "cars/{$old->id}/2.webp"]);
        $this->assertCount(3, $recent->images);
        $this->assertCount(3, $forSale->images);
    }

    public function test_it_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('cars:prune-photos');
    }
}
