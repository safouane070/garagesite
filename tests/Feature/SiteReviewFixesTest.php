<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** De punten uit de sitereview van november 2026. */
class SiteReviewFixesTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_offers_transmission_and_mileage_filters_with_counts(): void
    {
        Car::factory()->count(2)->create(['status' => CarStatus::Available, 'transmission' => 'Automaat']);
        Car::factory()->create(['status' => CarStatus::Available, 'transmission' => 'Handgeschakeld']);

        $html = $this->get(route('cars.index'))->assertOk()
            ->assertSee('Automaat (2)')
            ->assertSee('Handgeschakeld (1)')
            ->assertSee('Tot 100.000 km')
            ->getContent();

        // Het filterpaneel staat twee keer op de pagina (zijbalk + mobiele lade): geen dubbele id's.
        preg_match_all('/id="([^"]+)"/', $html, $m);
        $this->assertSame([], array_keys(array_filter(array_count_values($m[1]), fn ($n) => $n > 1)));
    }

    public function test_subpages_show_the_dealers_own_photos(): void
    {
        foreach (['diensten', 'financial-lease', 'vw-specialist', 'over-ons', 'contact'] as $route) {
            $this->get(route($route))->assertOk()->assertSee('images/site/', false);
        }
        $this->assertFileExists(public_path('images/site/kantoor.webp'));
    }

    public function test_home_has_a_search_title_and_no_jargon_usp(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<title>VW &amp; Audi occasions in Rijswijk · ', false)
            ->assertSee('Volkswagen- &amp; Audi-specialist', false)
            ->assertSee('data-fab-avoid', false);
    }

    public function test_over_ons_uses_real_reviews_instead_of_number_tiles(): void
    {
        $this->get(route('over-ons'))->assertOk()
            ->assertDontSee('Afleverkosten</dt>', false)
            ->assertDontSee('keuring')
            ->assertSee('Klantreviews');
    }

    /** Rekenvoorbeeld = dezelfde bedragen als de calculator van de leasepartner (€ 20.000 → € 345). */
    public function test_financial_lease_page_has_a_real_example_and_faq(): void
    {
        $this->get(route('financial-lease'))->assertOk()
            ->assertSee('Leasebedrag € 20.000')
            ->assertSee('€ 345 p/m', false)
            ->assertSee('Wat is een slottermijn?')
            ->assertSee('excl. btw')
            ->assertSee('10,5% rente');
    }

    /** Kop noemt het product en voor wie; actie al in de kop, niet pas halverwege. */
    public function test_financial_lease_hero_names_product_and_audience_with_action(): void
    {
        $this->get(route('financial-lease'))->assertOk()
            ->assertSeeInOrder(['<h1', 'Financial lease voor ondernemers', 'Alleen zakelijk', 'Vraag een voorstel aan'], false);
    }

    /** Eigen auto's met leaseprijs i.p.v. het widget van de partner (scroll-val, blauwe UI, aanvraag lekt weg). */
    public function test_financial_lease_shows_own_stock_instead_of_partner_widget(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'price' => 24950, 'is_featured' => true]);
        Car::factory()->create(['status' => CarStatus::Sold, 'price' => 30000, 'brand' => 'Verkocht']);

        $this->get(route('financial-lease'))->assertOk()
            ->assertSee(route('cars.show', $car), false)
            ->assertSee('Lease v.a.')
            ->assertDontSee('Verkocht')
            ->assertDontSee('iframe.financiallease.nl', false);
    }

    /** Te oud voor de leasepartner: geen maandbedrag, geen "ook via lease" en niet op de lease-pagina. */
    public function test_too_old_car_gets_no_lease_promise_anywhere(): void
    {
        $old = Car::factory()->create(['status' => CarStatus::Available, 'price' => 9000, 'year' => now()->year - 13, 'is_featured' => true]);

        $this->get(route('cars.show', $old))->assertOk()
            ->assertDontSee('Lease v.a.')->assertDontSee('Bereken je eigen bedrag')->assertDontSee('Ook mogelijk via financial lease');
        $this->get(route('financial-lease'))->assertOk()->assertDontSee(route('cars.show', $old), false);
    }

    /** Identieke auto's (de dealer heeft er vijf dezelfde) krijgen toch elk een eigen paginatitel. */
    public function test_detail_title_is_unique_for_identical_cars(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Audi', 'model' => 'Q3', 'variant' => 'S Edition', 'year' => 2023, 'mileage' => 21436]);

        $this->get(route('cars.show', $car))->assertOk()
            ->assertSee('<title>Audi Q3 S Edition · 2023 · 21.436 km · ', false);
    }

    public function test_404_has_no_label_above_the_heading(): void
    {
        $this->get('/bestaat-echt-niet')->assertNotFound()->assertDontSee('Foutcode');
    }
}
