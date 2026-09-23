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

    public function test_footer_shows_kvk_and_vat_once_filled_in(): void
    {
        $this->get('/')->assertDontSee('KvK');

        config(['brand.company.kvk' => '12345678', 'brand.company.vat' => 'NL001234567B01']);
        $this->get('/')->assertSee('· KvK 12345678 · btw NL001234567B01')->assertDontSee('@if');
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
