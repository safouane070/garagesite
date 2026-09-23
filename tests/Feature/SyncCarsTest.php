<?php

namespace Tests\Feature;

use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SyncCarsTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<string,array{price:int,km:int,year:int,fuel:string,title:string,modified:string}> */
    private array $dealer = [];

    private bool $listFails = false;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');

        Http::fake(function (Request $request) {
            $url = $request->url();

            if (str_contains($url, '/wp-json/wp/v2/voertuig')) {
                if ($this->listFails) {
                    return Http::response('', 500);
                }
                $list = [];
                foreach ($this->dealer as $slug => $v) {
                    $list[] = [
                        'slug' => $slug, 'title' => ['rendered' => $v['title']],
                        'link' => "https://autobedrijfrijswijk.nl/voertuig/{$slug}/",
                        'modified' => $v['modified'], 'featured_media' => 7,
                    ];
                }

                return Http::response($list, 200, ['X-WP-TotalPages' => 1]);
            }
            if (str_contains($url, '/wp-json/wp/v2/media/')) {
                return Http::response(['source_url' => 'https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/555-1.jpg']);
            }
            if (preg_match('#/voertuig/([^/]+)/#', $url, $m)) {
                return Http::response($this->page($this->dealer[$m[1]]));
            }
            if (str_contains($url, '/wp-content/uploads/')) {
                return Http::response($this->jpeg());
            }

            return Http::response('', 404);
        });
    }

    private function vehicle(string $slug, array $attrs = []): void
    {
        $this->dealer[$slug] = array_merge([
            'title' => 'Volkswagen Golf 1.5 TSI R-Line Pano/Camera', 'price' => 24950, 'km' => 50000,
            'year' => 2021, 'fuel' => 'B', 'modified' => '2026-09-20T10:00:00',
        ], $attrs);
    }

    /** Minimale nabootsing van de voertuigpagina van de dealer. */
    private function page(array $v): string
    {
        $row = fn ($label, $value) => "<div class=\"ct-div-block table-row\" ><span>{$label}</span><div><span>{$value}</span></div></div></div>";
        $price = $v['price'] ? "<h4>€&nbsp;<span id=\"span-375-134\" class=\"ct-span\" >{$v['price']}</span></h4>" : '';

        return '<html><body>'
            . $row('Bouwjaar', $v['year']) . $row('Kilometerstand', "{$v['km']} km") . $row('Brandstof', $v['fuel'])
            . $row('Caressorie', 'Hatchback') . $row('Kleur', 'zwart') . $row('Aantal deuren', 5)
            . $price
            . '<span class="ct-text-block">Transmissie</span><span class="ct-span">Automaat</span>'
            . '<ul class="optionstest"><li>Navigatie</li><li>LED-koplampen</li><li>Panoramadak</li></ul>'
            . '<img src="https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/555-1-1024x768.jpg">'
            . '<img src="https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/555-2-1024x768.jpg">'
            . '<img src="https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/999-1-300x225.jpg">' // andere auto
            . '</body></html>';
    }

    private function jpeg(): string
    {
        $img = imagecreatetruecolor(400, 300);
        ob_start();
        imagejpeg($img);

        return (string) ob_get_clean();
    }

    private function car(array $attrs): Car
    {
        return Car::factory()->create(array_merge(['status' => 'available', 'options' => null], $attrs));
    }

    public function test_creates_new_car_with_photos_options_and_description(): void
    {
        $this->vehicle('volkswagen-golf-1-5-tsi-r-line-pano-camera');

        $this->artisan('cars:sync')->assertSuccessful();

        $car = Car::firstWhere('dealer_slug', 'volkswagen-golf-1-5-tsi-r-line-pano-camera');
        $this->assertNotNull($car);
        $this->assertSame(['Volkswagen', 'Golf', '1.5 TSI R-Line Pano'], [$car->brand, $car->model, $car->variant]);
        $this->assertSame([24950, 50000, 2021], [(int) $car->price, $car->mileage, $car->year]);
        $this->assertSame(['Benzine', 'Automaat', 'Hatchback', 'Zwart'], [$car->fuel_type, $car->transmission, $car->body_type, $car->color]);
        $this->assertSame(['Navigatie', 'LED-koplampen', 'Panoramadak'], $car->options);
        $this->assertCount(2, $car->images); // foto van de "andere auto" (999) telt niet mee
        $this->assertStringContainsString('navigatie, LED-koplampen en panoramadak', $car->description);
    }

    public function test_car_that_disappears_is_marked_sold_and_manual_car_is_left_alone(): void
    {
        $this->vehicle('audi-a3-sportback', ['title' => 'Audi A3 Sportback 35 TFSI', 'km' => 10000]);
        $this->vehicle('audi-a4-avant', ['title' => 'Audi A4 Avant 40 TFSI', 'km' => 20000]);
        $this->vehicle('audi-a5-coupe', ['title' => 'Audi A5 Coupé 45 TFSI', 'km' => 30000]);
        foreach (['audi-a3-sportback', 'audi-a4-avant', 'audi-a5-coupe'] as $slug) {
            $this->car(['slug' => $slug, 'dealer_slug' => $slug, 'dealer_modified_at' => '2026-09-21 00:00:00']);
        }
        $gone = $this->car(['slug' => 'bmw-weg', 'dealer_slug' => 'bmw-weg', 'dealer_modified_at' => '2026-09-21 00:00:00']);
        $manual = $this->car(['slug' => 'handmatig-ingevoerd', 'brand' => 'Tesla', 'mileage' => 1]);

        $this->artisan('cars:sync')->assertSuccessful();

        $this->assertSame('sold', $gone->fresh()->status->value);
        $this->assertModelExists($gone); // niet gewist: blijft in "met trots verkocht"
        $this->assertSame('available', $manual->fresh()->status->value);
    }

    public function test_price_change_on_dealer_site_is_applied(): void
    {
        $this->vehicle('volkswagen-polo', ['title' => 'Volkswagen Polo 1.0 TSI', 'price' => 17950, 'modified' => '2026-09-22T09:00:00']);
        $car = $this->car(['slug' => 'volkswagen-polo', 'dealer_slug' => 'volkswagen-polo', 'price' => 18950, 'dealer_modified_at' => '2026-09-01 00:00:00']);

        $this->artisan('cars:sync')->assertSuccessful();

        $this->assertSame(17950, (int) $car->fresh()->price);
    }

    /** Andere slug (Marktplaats vs. dealer), zelfde auto: koppelen, niet dubbel aanmaken. */
    public function test_existing_car_is_linked_by_identity_instead_of_duplicated(): void
    {
        $this->vehicle('mercedes-benz-glc-300e-4matic', ['title' => 'Mercedes-Benz GLC 300e 4MATIC', 'km' => 34956, 'year' => 2025, 'price' => 69950]);
        $ours = $this->car(['slug' => 'mercedes-benz-glc-klasse-300e-4matic-amg-pano', 'brand' => 'Mercedes-Benz', 'year' => 2025, 'mileage' => 34800]);

        $this->artisan('cars:sync')->assertSuccessful();

        $this->assertSame(1, Car::count());
        $this->assertSame('mercedes-benz-glc-300e-4matic', $ours->fresh()->dealer_slug);
    }

    public function test_stops_before_any_change_when_most_linked_cars_seem_gone(): void
    {
        $this->vehicle('iets-anders', ['title' => 'Kia Picanto 1.0', 'km' => 5]);
        $cars = collect(['a', 'b', 'c', 'd'])->map(fn ($s) => $this->car(['slug' => "auto-{$s}", 'dealer_slug' => "auto-{$s}"]));

        $this->artisan('cars:sync')->assertFailed();

        $cars->each(fn ($c) => $this->assertSame('available', $c->fresh()->status->value));
        $this->assertSame(4, Car::count()); // ook niets nieuws aangemaakt
    }

    public function test_stops_when_dealer_site_is_unreachable(): void
    {
        $this->listFails = true;
        $car = $this->car(['slug' => 'auto-x', 'dealer_slug' => 'auto-x']);

        $this->artisan('cars:sync')->assertFailed();

        $this->assertSame('available', $car->fresh()->status->value);
    }

    public function test_unreadable_vehicle_is_skipped_not_half_created(): void
    {
        $this->vehicle('zonder-prijs', ['title' => 'Opel Corsa 1.2', 'price' => 0]);

        $this->artisan('cars:sync')->assertSuccessful();

        $this->assertSame(0, Car::count());
    }

    public function test_dry_run_changes_nothing(): void
    {
        $this->vehicle('volkswagen-golf-nieuw');

        $this->artisan('cars:sync', ['--dry-run' => true])
            ->expectsOutputToContain('SYNC DRY-RUN OK')
            ->assertSuccessful();

        $this->assertSame(0, Car::count());
    }

    public function test_sell_unmatched_marks_old_unlinkable_stock_sold(): void
    {
        $this->vehicle('volkswagen-golf-nieuw');
        $old = $this->car(['slug' => 'oude-seed-auto', 'brand' => 'Renault', 'mileage' => 123]);

        $this->artisan('cars:sync', ['--sell-unmatched' => true])->assertSuccessful();

        $this->assertSame('sold', $old->fresh()->status->value);
    }
}
