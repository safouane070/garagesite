<?php

namespace Tests\Feature;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PruneGuardTest extends TestCase
{
    use RefreshDatabase;

    /** @return list<Car> auto's waarvan de slug 1-op-1 matcht met een dealer-listing */
    private function carsMatchingListings(int $n): array
    {
        $paths = json_decode(file_get_contents(database_path('seeders/rijswijk_listings.json')), true);

        return collect($paths)->take($n)->map(function ($path) {
            preg_match('#/m\d+-(.+)$#', $path, $m);

            return Car::factory()->create(['slug' => $m[1], 'status' => 'available', 'options' => null]);
        })->all();
    }

    /** Geeft de dealersite ineens overal 404 (andere URL's/storing), dan niets wissen. */
    public function test_aborts_when_almost_everything_looks_gone(): void
    {
        $this->carsMatchingListings(4);
        Http::fake(['*' => Http::response('', 404)]);

        $this->artisan('cars:prune-gone')->assertFailed();

        $this->assertSame(4, Car::count());
    }

    public function test_force_overrides_the_guard(): void
    {
        $this->carsMatchingListings(4);
        Http::fake(['*' => Http::response('', 404)]);

        $this->artisan('cars:prune-gone', ['--force' => true])->assertSuccessful();

        $this->assertSame(0, Car::count());
    }

    /** Controle: normaal gebruik (één auto echt weg) werkt nog gewoon. */
    public function test_normal_prune_still_removes_a_single_gone_car(): void
    {
        $gone = $this->carsMatchingListings(4)[0];
        Http::fake(fn ($request) => Http::response('', str_contains($request->url(), $gone->slug) ? 404 : 200));

        $this->artisan('cars:prune-gone')->assertSuccessful();

        $this->assertModelMissing($gone);
        $this->assertSame(3, Car::count());
    }
}
