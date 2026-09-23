<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminUxTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_navigation_links_to_stock_leads_and_profile_with_open_count(): void
    {
        Lead::create(['type' => 'vraag', 'name' => 'A', 'email' => 'a@example.com']);
        Lead::create(['type' => 'vraag', 'name' => 'B', 'email' => 'b@example.com']);

        $this->actingAs(User::factory()->create())
            ->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee(route('admin.leads.index'), false)
            ->assertSee(route('profile.edit'), false)
            ->assertSee('aanvragen wachten op een reactie');
    }

    /** Handleiding in het beheer: bereikbaar via de navigatie, met de sync-valkuil als de sync aan staat. */
    public function test_help_page_explains_the_admin_and_the_sync_pitfall(): void
    {
        $admin = User::factory()->create(['email' => 'eigenaar@example.com']);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertSee(route('admin.help'), false);
        $this->actingAs($admin)->get(route('admin.help'))->assertOk()
            ->assertSee('Nieuwe auto')->assertSee('Markeer als afgehandeld')
            ->assertSee('eigenaar@example.com')
            ->assertSee('komt een verwijderde auto de volgende ochtend terug');

        config(['brand.dealer_site_url' => '']);
        $this->actingAs($admin)->get(route('admin.help'))->assertDontSee('dealersite');
    }

    /** Beschrijving leeg gelaten → de site maakt er een uit de echte gegevens; eigen tekst blijft staan. */
    public function test_empty_description_is_generated_from_the_car_data(): void
    {
        $payload = [
            'brand' => 'Audi', 'model' => 'A3', 'variant' => 'Sportback', 'year' => 2020, 'price' => 21950,
            'mileage' => 61000, 'fuel_type' => 'Benzine', 'transmission' => 'Automaat', 'color' => 'Grijs',
            'status' => 'available', 'options' => ['Navigatie', 'Cruise control'],
        ];
        $admin = User::factory()->create();

        $this->actingAs($admin)->post(route('admin.cars.store'), $payload)->assertRedirect();
        $car = \App\Models\Car::firstWhere('model', 'A3');
        $this->assertStringContainsString('61.000 km', $car->description);

        $this->actingAs($admin)->put(route('admin.cars.update', $car), $payload + ['description' => 'Eigen tekst.']);
        $this->assertSame('Eigen tekst.', $car->fresh()->description);
    }

    /** Verwijderen van een auto van de dealersite waarschuwt dat hij terugkomt. */
    public function test_deleting_a_synced_car_warns_it_comes_back(): void
    {
        \App\Models\Car::factory()->create(['dealer_slug' => 'x']);

        $this->actingAs(User::factory()->create())->get(route('admin.dashboard'))
            ->assertSee('komt hij morgen terug', false);
    }

    /** Upload-preview gebruikt blob:-URL's; de CSP moet die toestaan (anders lege miniaturen). */
    public function test_csp_allows_blob_images_for_upload_preview(): void
    {
        $csp = $this->actingAs(User::factory()->create())
            ->get(route('admin.cars.create'))
            ->headers->get('Content-Security-Policy');

        $this->assertMatchesRegularExpression("/img-src [^;]*blob:/", $csp);
    }

    /** De enige beheerder mag zichzelf niet buitensluiten (er is geen registratie). */
    public function test_account_cannot_be_deleted_from_the_profile(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertDontSee('Account verwijderen')
            ->assertDontSee('Delete Account');

        $this->actingAs($user)->delete('/profile', ['password' => 'password'])->assertStatus(405);
        $this->assertNotNull($user->fresh());
    }

    public function test_profile_is_dutch_and_flash_is_human_readable(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertSee('Wachtwoord wijzigen')
            ->assertSee('Profielgegevens')
            ->assertDontSee('Update Password');

        $this->actingAs($user)
            ->withSession(['status' => 'profile-updated'])
            ->get(route('profile.edit'))
            ->assertSee('Je profiel is opgeslagen.')
            ->assertDontSee('profile-updated</p>', false);
    }
}
