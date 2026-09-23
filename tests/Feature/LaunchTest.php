<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/** Wat er moet kloppen op het moment dat deze site het oude domein overneemt. */
class LaunchTest extends TestCase
{
    use RefreshDatabase;

    public function test_old_wordpress_urls_redirect_permanently(): void
    {
        $car = Car::factory()->create(['dealer_slug' => 'volkswagen-golf-gti-2021']);

        $this->get('/occasions/')->assertRedirect('/aanbod')->assertStatus(301);
        $this->get('/privacy-policy/')->assertRedirect('/privacybeleid')->assertStatus(301);
        $this->get('/voertuig/volkswagen-golf-gti-2021/')->assertRedirect(route('cars.show', $car))->assertStatus(301);
        $this->get('/voertuig/allang-verkocht/')->assertRedirect(route('cars.index'))->assertStatus(301);
    }

    /** De juridische teksten staan op deze site zelf, niet op de WordPress-site die verdwijnt. */
    public function test_legal_pages_link_to_local_pdfs_that_exist(): void
    {
        foreach (['voorwaarden' => ['voorwaarden-particulier.pdf', 'voorwaarden-zakelijk.pdf'], 'privacy' => ['privacybeleid.pdf']] as $route => $files) {
            $html = $this->get(route($route))->assertOk()->getContent();
            $this->assertStringNotContainsString('autobedrijfrijswijk.nl/', $html);
            foreach ($files as $file) {
                $this->assertStringContainsString(asset("docs/{$file}"), $html);
                $this->assertStringStartsWith('%PDF', file_get_contents(public_path("docs/{$file}")));
            }
        }
    }

    /** www (CNAME naar dezelfde server) → één canoniek adres, pad en query blijven behouden. */
    public function test_www_redirects_to_the_canonical_host_in_production(): void
    {
        config(['app.url' => 'https://autobedrijfrijswijk.nl']);
        $this->app['env'] = 'production';

        $this->get('http://www.autobedrijfrijswijk.nl/aanbod?merk=audi')
            ->assertStatus(301)->assertRedirect('https://autobedrijfrijswijk.nl/aanbod?merk=audi');
        $this->get('https://autobedrijfrijswijk.nl/contact')->assertOk();
    }

    /** Wettelijk verplicht: juridische naam, KvK en btw in de footer en in de bedrijfsgegevens voor Google. */
    public function test_footer_and_structured_data_show_company_registration(): void
    {
        $this->get('/')
            ->assertSee('BS Rijswijk Automotive B.V. · Alle rechten voorbehouden · KvK 95760733 · btw NL867282368B01')
            ->assertSee('"vatID":"NL867282368B01"', false)
            ->assertSee('"streetAddress":"Poldermeesterstraat 16","postalCode":"2288 GV"', false);

        config(['brand.company.kvk' => null, 'brand.company.vat' => null]);
        $this->get('/')->assertDontSee('KvK')->assertDontSee('btw N');
    }

    /** Op het eigen domein zou de sync zichzelf uitlezen: hij moet weigeren zonder iets te wijzigen. */
    public function test_sync_refuses_when_dealer_site_is_this_site(): void
    {
        config(['app.url' => 'https://autobedrijfrijswijk.nl', 'brand.dealer_site_url' => 'https://autobedrijfrijswijk.nl']);
        Http::fake();
        $car = Car::factory()->create(['dealer_slug' => 'x', 'status' => CarStatus::Available]);

        $this->artisan('cars:sync')->assertFailed();

        Http::assertNothingSent();
        $this->assertSame(CarStatus::Available, $car->fresh()->status);
    }
}
