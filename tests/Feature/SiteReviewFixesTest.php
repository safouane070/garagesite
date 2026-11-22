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
            ->assertSee('Wat is een slottermijn?');
    }

    public function test_404_has_no_label_above_the_heading(): void
    {
        $this->get('/bestaat-echt-niet')->assertNotFound()->assertDontSee('Foutcode');
    }
}
