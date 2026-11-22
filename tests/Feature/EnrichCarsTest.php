<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Support\Marktplaats;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class EnrichCarsTest extends TestCase
{
    use RefreshDatabase;

    private const SELLER = '/u/autobedrijf-rijswijk/40360814';

    /** Advertentiepagina zoals Marktplaats hem levert: kenmerken in window.__CONFIG__. */
    private function listingHtml(array $attributes, int $priceCents = 4795000): string
    {
        $config = ['listing' => [
            'priceInfo' => ['priceCents' => $priceCents],
            'carAttributes' => ['groupedWithIcons' => [['attributes' => array_map(
                fn ($k, $v) => ['key' => $k, 'value' => $v],
                array_keys($attributes), $attributes,
            )]]],
        ]];

        return '<html><script>window.__CONFIG__ = ' . json_encode($config) . ';</script></html>';
    }

    private function tiguan(array $extra = []): array
    {
        return $extra + [
            'brand' => 'Volkswagen Tiguan',
            'constructionYear' => '2025',
            'mileage' => '35.920',
            'transmission' => 'Automaat 6 versnellingen',
            'powerInHorsePower' => '272',
            'cylinderCapacity' => '1.5',
            'torque' => '240 nm / 1.500 tpm',
            'fuelConsumption' => '62.5',
            'emptyWeight' => '1.766',
            'maxWeightWithBrakes' => '1.800',
            'newCarPrice' => '€ 41.686',
            'energyLabel' => 'A',
            'dateApk' => '26 april 2027',
            'totalNumberOfOwners' => '2',
            'licensePlate' => 'pt-848-n',
        ];
    }

    /** Profiel met één advertentie; elke volgende profielpagina levert niets nieuws. */
    private function fakeMarktplaats(array $attributes): void
    {
        $path = '/v/auto-s/volkswagen/m2468195560-volkswagen-tiguan-1-5-ehybrid';
        Http::fake([
            'www.marktplaats.nl/u/*' => Http::response("<a href=\"{$path}\">Tiguan</a>"),
            'www.marktplaats.nl/v/*' => Http::response($this->listingHtml($attributes)),
        ]);
    }

    /** 146 advertenties staan over 5 pagina's verspreid; we lezen door tot een lege pagina. */
    public function test_listing_paths_follow_all_profile_pages(): void
    {
        Http::fake([
            'www.marktplaats.nl/u/*/p/2/' => Http::response('<a href="/v/auto-s/audi/m2-audi-q8">Q8</a>'),
            'www.marktplaats.nl/u/*/p/3/' => Http::response('<p>geen advertenties</p>'),
            'www.marktplaats.nl/u/*' => Http::response('<a href="/v/auto-s/volkswagen/m1-vw-golf">Golf</a>'),
        ]);

        $this->assertSame(
            ['/v/auto-s/volkswagen/m1-vw-golf', '/v/auto-s/audi/m2-audi-q8'],
            Marktplaats::listingPaths(self::SELLER),
        );
    }

    public function test_parse_and_specs_handle_dutch_number_formats(): void
    {
        $listing = Marktplaats::parse($this->listingHtml($this->tiguan()));

        $this->assertSame(2025, $listing['year']);
        $this->assertSame(35920, $listing['mileage']);
        $this->assertSame(47950, $listing['price']);

        $specs = Marktplaats::specs($listing['attributes']);
        $this->assertSame(272, $specs['vermogen_pk']);
        $this->assertSame(1.5, $specs['motorinhoud_l']);
        $this->assertSame(240, $specs['koppel_nm']);
        $this->assertSame(6, $specs['versnellingen']);
        $this->assertSame(62.5, $specs['verbruik_km_l']);
        $this->assertSame(1766, $specs['gewicht_kg']);
        $this->assertSame(1800, $specs['trekgewicht_geremd_kg']);
        $this->assertSame(41686, $specs['nieuwprijs']);
        $this->assertSame('PT-848-N', $specs['kenteken']);
        $this->assertSame('26 april 2027', $specs['apk_tot']);
        $this->assertNull(Marktplaats::parse('<html>geen config</html>'));
    }

    public function test_matching_car_is_enriched_and_admin_values_win(): void
    {
        $car = Car::factory()->create([
            'status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'year' => 2025, 'mileage' => 36100, 'price' => 47950,
            'specs' => ['vermogen_pk' => 270, 'apk_tot' => '1 januari 2026'], // beheerder + oude APK
        ]);
        $this->fakeMarktplaats($this->tiguan());

        $this->artisan('cars:enrich', ['--delay' => 0])->assertSuccessful();

        $car->refresh();
        $this->assertSame('2468195560', $car->marktplaats_id);
        $this->assertNotNull($car->enriched_at);
        $this->assertSame(270, $car->specs['vermogen_pk'], 'beheerderswaarde blijft staan');
        $this->assertSame('1 januari 2026', $car->specs['apk_tot'], 'ook de APK van de beheerder blijft staan');
        $this->assertSame(1766, $car->specs['gewicht_kg'], 'ontbrekend veld aangevuld');
    }

    public function test_ambiguous_or_distant_matches_are_skipped(): void
    {
        // Twee even goede kandidaten: liever niets dan de specs van de verkeerde auto.
        $a = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'year' => 2025, 'price' => 47950, 'mileage' => 35820, 'specs' => null]);
        $b = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'year' => 2025, 'price' => 47950, 'mileage' => 36020, 'specs' => null]);
        // Te ver weg op kilometerstand en een ander merk: nooit gekoppeld.
        $c = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'year' => 2025, 'price' => 47950, 'mileage' => 40000, 'specs' => null]);
        $d = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Audi', 'year' => 2025, 'price' => 47950, 'mileage' => 35920, 'specs' => null]);
        $this->fakeMarktplaats($this->tiguan());

        $this->artisan('cars:enrich', ['--delay' => 0])->assertSuccessful();

        foreach ([$a, $b, $c, $d] as $car) {
            $this->assertNull($car->fresh()->marktplaats_id);
        }
    }

    /** Twee bijna-nieuwe VW's van hetzelfde jaar: de Golf krijgt nooit de specs van de Tiguan. */
    public function test_other_model_of_the_same_brand_is_never_matched(): void
    {
        $golf = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Golf', 'year' => 2025, 'price' => 47950, 'mileage' => 35920, 'specs' => null]);
        $this->fakeMarktplaats($this->tiguan(['brand' => 'Volkswagen TIGUAN, 1.5 eHybrid R-Line']));

        $this->artisan('cars:enrich', ['--delay' => 0])->assertSuccessful();

        $this->assertNull($golf->fresh()->marktplaats_id);
    }

    /**
     * Een verkochte Tiguan staat nog even op Marktplaats (€ 47.950). Hier staat een andere
     * Tiguan van hetzelfde jaar met bijna dezelfde km, maar voor € 32.950: die mag de specs
     * (en de koppeling) van de verkochte auto niet krijgen.
     */
    public function test_car_with_a_different_price_is_not_matched(): void
    {
        $other = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'model' => 'Tiguan', 'year' => 2025, 'mileage' => 36300, 'price' => 32950, 'specs' => null]);
        $this->fakeMarktplaats($this->tiguan());

        $this->artisan('cars:enrich', ['--delay' => 0])->assertSuccessful();

        $this->assertNull($other->fresh()->marktplaats_id);
    }

    public function test_linked_car_is_never_read_again_so_admin_edits_stay(): void
    {
        Car::factory()->create([
            'status' => CarStatus::Available, 'brand' => 'Volkswagen', 'year' => 2025, 'mileage' => 35920,
            'marktplaats_id' => '2468195560', 'enriched_at' => now()->subYear(),
        ]);
        $this->fakeMarktplaats($this->tiguan());

        $this->artisan('cars:enrich', ['--delay' => 0])->assertSuccessful();

        Http::assertNotSent(fn ($request) => str_contains($request->url(), '/v/auto-s/'));
    }

    public function test_empty_seller_disables_enrichment(): void
    {
        config(['brand.marktplaats_seller' => '']);
        Http::fake();

        $this->artisan('cars:enrich')->assertSuccessful();

        Http::assertNothingSent();
    }

    public function test_spec_sections_group_and_format_in_dutch(): void
    {
        $car = new Car([
            'year' => 2025, 'mileage' => 35920, 'fuel_type' => 'Hybride', 'transmission' => 'Automaat',
            'body_type' => 'SUV', 'color' => 'Zilver of Grijs',
            'specs' => ['vermogen_pk' => 272, 'motorinhoud_l' => 1.5, 'verbruik_km_l' => 62.5, 'acceleratie_0_100' => 9.2,
                'gewicht_kg' => 1766, 'nieuwprijs' => 41686, 'apk_tot' => '26 april 2027', 'eigen_veld' => 'x'],
        ]);

        $s = $car->specSections();

        $this->assertSame('35.920 km', $s['Algemeen']['Kilometerstand']);
        $this->assertSame('Zilver / Grijs', $s['Algemeen']['Kleur']);
        $this->assertSame('272 pk', $s['Motor & aandrijving']['Vermogen']);
        $this->assertSame('1,5 liter', $s['Motor & aandrijving']['Motorinhoud']);
        $this->assertSame('2,0 liter', (new Car(['specs' => ['motorinhoud_l' => 2.0]]))->specSections()['Motor & aandrijving']['Motorinhoud']);
        $this->assertSame('62,5 km/l', $s['Prestaties & verbruik']['Verbruik']);
        $this->assertSame('9,2 s', $s['Prestaties & verbruik']['0-100 km/u']);
        $this->assertSame('1.766 kg', $s['Afmetingen & gewicht']['Leeggewicht']);
        $this->assertSame('€ 41.686', $s['Historie']['Nieuwprijs']);
        $this->assertSame('26 april 2027', $s['Historie']['APK geldig tot']);
        $this->assertSame('x', $s['Overig']['Eigen veld']);
    }

    public function test_detail_page_shows_grouped_specs_and_power(): void
    {
        $car = Car::factory()->create([
            'status' => CarStatus::Available,
            'specs' => ['vermogen_pk' => 272, 'gewicht_kg' => 1766, 'apk_tot' => '26 april 2027'],
        ]);

        $this->get(route('cars.show', $car))->assertOk()
            ->assertSee('272 pk')
            ->assertSee('Afmetingen &amp; gewicht', false)
            ->assertSee('1.766 kg')
            ->assertSee('APK geldig tot');
    }
}
