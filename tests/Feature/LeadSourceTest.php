<?php

namespace Tests\Feature;

use App\Models\Car;
use App\Models\Lead;
use App\Models\User;
use App\Support\Reviews;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Inzicht: welke kanalen en pagina's leveren aanvragen op. */
class LeadSourceTest extends TestCase
{
    use RefreshDatabase;

    private function submit(string $fromPage): void
    {
        $this->withHeader('referer', url($fromPage))->post(route('leads.store'), [
            'type' => 'proefrit', 'name' => 'Jan', 'email' => 'jan@example.com',
        ])->assertSessionHasNoErrors();
    }

    public function test_lead_remembers_channel_landing_page_and_form_page(): void
    {
        Mail::fake();
        $car = Car::factory()->create(['status' => 'available']);

        // Binnen via Google op een autopagina, daarna verder geklikt (interne referer telt niet).
        $this->withHeader('referer', 'https://www.google.com/')->get(route('cars.show', $car))->assertOk();
        $this->withHeader('referer', url('/'))->get(route('contact'))->assertOk();
        $this->submit('/contact');

        $lead = Lead::sole();
        $this->assertSame(['Google', '/aanbod/' . $car->slug, '/contact'], [$lead->source, $lead->landing_page, $lead->form_page]);

        $this->actingAs(User::factory()->create())->get(route('admin.leads.index'))
            ->assertSee('Via Google · binnen op /aanbod/' . $car->slug . ' · formulier op /contact')
            ->assertSee('Herkomst · laatste 90 dagen');
    }

    public function test_campaign_link_and_direct_visits(): void
    {
        Mail::fake();

        $this->get('/aanbod?utm_source=marktplaats')->assertOk();
        $this->submit('/aanbod');
        $this->assertSame('Marktplaats', Lead::latest('id')->first()->source);

        $this->flushSession();
        $this->get('/')->assertOk(); // geen referer
        $this->submit('/');
        $this->assertSame('Direct', Lead::latest('id')->first()->source);
    }

    public function test_analytics_is_off_by_default_and_csp_follows_when_enabled(): void
    {
        $this->get('/')->assertDontSee('data-domain', false);
        $this->assertStringNotContainsString('plausible.io', $this->get('/')->headers->get('Content-Security-Policy'));

        config(['brand.analytics.plausible_domain' => 'autobedrijfrijswijk.nl']);
        $response = $this->get('/');
        $response->assertSee('data-domain="autobedrijfrijswijk.nl" src="https://plausible.io/js/script.js"', false);
        $this->assertStringContainsString("script-src 'self' 'unsafe-inline' 'unsafe-eval' https://plausible.io", $response->headers->get('Content-Security-Policy'));
        $this->get(route('privacy'))->assertSee('Plausible');
    }

    public function test_hidden_review_is_not_shown_even_when_fetched_again(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        \Illuminate\Support\Facades\Storage::put(Reviews::CACHE, json_encode([
            ['name' => 'Gemeente Den haag', 'rating' => 5, 'text' => 'G63 gekocht'],
            ['name' => 'Echte Klant', 'rating' => 5, 'text' => 'Top'],
        ]));

        $this->assertSame(['Echte Klant'], array_column(Reviews::all(), 'name'));
        $this->get('/')->assertDontSee('Gemeente Den')->assertSee('Echte Klant');
    }
}
