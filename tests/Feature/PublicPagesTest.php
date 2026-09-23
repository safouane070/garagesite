<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPagesTest extends TestCase
{
    use RefreshDatabase;

    /** Elke publieke pagina rendert (200) én krijgt de beveiligingsheaders. */
    public function test_public_pages_load_with_security_headers(): void
    {
        $routes = [
            'home', 'cars.index', 'diensten', 'financial-lease',
            'vw-specialist', 'over-ons', 'contact', 'voorwaarden', 'privacy',
        ];

        foreach ($routes as $name) {
            $response = $this->get(route($name));

            $response->assertStatus(200);
            $response->assertHeader('X-Content-Type-Options', 'nosniff');
            $response->assertHeader('Content-Security-Policy');
        }
    }

    /** Sitemap bevat de inhoudspagina's; robots.txt verwijst er absoluut naar. */
    public function test_sitemap_lists_content_pages_and_robots_points_to_it(): void
    {
        $this->get(route('sitemap'))
            ->assertOk()
            ->assertSee(route('diensten'))
            ->assertSee(route('contact'))
            ->assertSee(route('privacy'));

        // Test-/stagingomgeving: alles dicht voor zoekmachines.
        $this->get('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertDontSee('Sitemap:');
        $this->get('/')->assertHeader('X-Robots-Tag', 'noindex, nofollow');

        // Productie: indexeerbaar, met sitemap, en https-only.
        $this->app['env'] = 'production';
        $this->get('/robots.txt')
            ->assertOk()
            ->assertSee('Sitemap: ' . route('sitemap'))
            ->assertDontSee("Disallow: /\n", false);
        $this->get('/')->assertHeaderMissing('X-Robots-Tag');
        $this->get('https://localhost/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
    }

    /** Verkochte auto's horen niet in het publieke aanbod. */
    public function test_sold_cars_are_excluded_from_the_catalogue(): void
    {
        Car::factory()->create(['status' => 'available', 'slug' => 'te-koop-auto']);
        Car::factory()->create(['status' => 'sold', 'slug' => 'verkochte-auto']);

        $response = $this->get(route('cars.index'));

        $response->assertSee('te-koop-auto');
        $response->assertDontSee('verkochte-auto');
    }

    /** Een proefrit-aanvraag slaat de voorkeursdatum op. */
    public function test_proefrit_lead_stores_preferred_date(): void
    {
        $date = now()->addDays(3)->toDateString();

        $this->post(route('leads.store'), [
            'type' => 'proefrit',
            'name' => 'Test Persoon',
            'email' => 'proefrit@example.com',
            'preferred_date' => $date,
        ])->assertRedirect();

        $lead = Lead::where('email', 'proefrit@example.com')->first();

        $this->assertNotNull($lead);
        $this->assertSame('proefrit', $lead->type);
        $this->assertSame($date, $lead->preferred_date->toDateString());
    }

    /** Een voorkeursdatum in het verleden wordt geweigerd. */
    public function test_preferred_date_in_the_past_is_rejected(): void
    {
        $this->post(route('leads.store'), [
            'type' => 'proefrit',
            'name' => 'Test Persoon',
            'email' => 'verleden@example.com',
            'preferred_date' => now()->subDay()->toDateString(),
        ])->assertSessionHasErrors('preferred_date');

        $this->assertSame(0, Lead::count());
    }
}
