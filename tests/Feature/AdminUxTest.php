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
