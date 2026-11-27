<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use App\Models\LeadInboxSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestoreDemoCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_redeploy_restores_built_photos_without_replacing_inbox_or_car_identity(): void
    {
        $car = Car::factory()->create(['dealer_slug' => 'vaste-auto', 'price' => 12345]);
        $lead = Lead::create(['car_id' => $car->id, 'type' => 'vraag', 'name' => 'Klant', 'email' => 'klant@example.com']);
        LeadInboxSettings::create(['id' => 1, 'notification_email' => 'eigenaar@example.com']);

        $snapshot = tempnam(sys_get_temp_dir(), 'catalog-');
        config(['database.connections.snapshot_fixture' => ['driver' => 'sqlite', 'database' => $snapshot, 'prefix' => '']]);
        $db = DB::connection('snapshot_fixture');
        $db->statement('CREATE TABLE cars (id INTEGER PRIMARY KEY, dealer_slug TEXT, slug TEXT, brand TEXT, model TEXT, year INTEGER, price NUMERIC, mileage INTEGER, fuel_type TEXT, transmission TEXT, color TEXT, specs TEXT, options TEXT, is_featured INTEGER)');
        $db->statement('CREATE TABLE car_images (id INTEGER PRIMARY KEY, car_id INTEGER, path TEXT, is_primary INTEGER, sort_order INTEGER)');
        foreach ([77 => 'vaste-auto', 88 => 'nieuwe-auto'] as $id => $slug) {
            $db->table('cars')->insert([
                'id' => $id, 'dealer_slug' => $slug, 'slug' => $slug, 'brand' => 'Volkswagen', 'model' => 'Golf',
                'year' => 2024, 'price' => 20000, 'mileage' => 10000, 'fuel_type' => 'Benzine',
                'transmission' => 'Automaat', 'color' => 'Zwart', 'specs' => '{"deuren":5}',
                'options' => '["Airco"]', 'is_featured' => 1,
            ]);
            $db->table('car_images')->insert(['id' => $id, 'car_id' => $id, 'path' => "build/{$slug}.webp", 'is_primary' => 1, 'sort_order' => 0]);
        }

        try {
            foreach ([1, 2] as $deploy) {
                $this->artisan('demo:restore-catalog', ['--snapshot' => $snapshot])->assertSuccessful();
                $this->assertSame(2, Car::count());
                $this->assertSame($car->id, $lead->refresh()->car->id);
                $this->assertSame('12345.00', $car->refresh()->price);
                $this->assertSame('build/vaste-auto.webp', $car->primaryImage->path);
                $this->assertSame('eigenaar@example.com', LeadInboxSettings::current()->notification_email);
                $this->assertDatabaseCount('leads', 1);
            }
            $new = Car::where('dealer_slug', 'nieuwe-auto')->sole();
            $this->assertSame(['deuren' => 5], $new->specs);
            $this->assertSame(['Airco'], $new->options);
            $this->assertTrue($new->is_featured);
        } finally {
            DB::disconnect('build_snapshot');
            DB::disconnect('snapshot_fixture');
            unlink($snapshot);
        }
    }

    /**
     * Een auto die tussen twee builds door de uurlijkse sync is toegevoegd, staat in de blijvende
     * database, maar zijn foto's stonden in de vorige container. Die verwijzingen gaven live een 403
     * (kapotte foto's); nu worden ze leeggemaakt en leest de sync de auto opnieuw in.
     */
    public function test_cars_outside_the_build_with_missing_photo_files_are_refetched(): void
    {
        Storage::fake('public');
        $lost = Car::factory()->create(['dealer_slug' => 'tussendoor', 'dealer_modified_at' => now()]);
        $lost->images()->create(['path' => 'cars/tussendoor/weg.webp', 'is_primary' => true, 'sort_order' => 0]);
        $fine = Car::factory()->create(['dealer_slug' => 'heeft-fotos', 'dealer_modified_at' => now()]);
        $fine->images()->create(['path' => 'cars/heeft-fotos/er.webp', 'is_primary' => true, 'sort_order' => 0]);
        Storage::disk('public')->put('cars/heeft-fotos/er.webp', 'x');

        $snapshot = tempnam(sys_get_temp_dir(), 'catalog-');
        config(['database.connections.snapshot_fixture' => ['driver' => 'sqlite', 'database' => $snapshot, 'prefix' => '']]);
        $db = DB::connection('snapshot_fixture');
        $db->statement('CREATE TABLE cars (id INTEGER PRIMARY KEY, dealer_slug TEXT)');
        $db->statement('CREATE TABLE car_images (id INTEGER PRIMARY KEY, car_id INTEGER, path TEXT)');

        try {
            $this->artisan('demo:restore-catalog', ['--snapshot' => $snapshot])->assertSuccessful();

            $this->assertCount(0, $lost->images()->get());
            $this->assertNull($lost->refresh()->dealer_modified_at); // sync leest hem opnieuw (en haalt foto's)
            $this->assertCount(1, $fine->images()->get());
            $this->assertNotNull($fine->refresh()->dealer_modified_at);
        } finally {
            DB::disconnect('build_snapshot');
            DB::disconnect('snapshot_fixture');
            unlink($snapshot);
        }
    }
}
