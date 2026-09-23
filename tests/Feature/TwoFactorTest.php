<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\Totp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function now(User $user): string
    {
        return Totp::code($user->fresh()->two_factor_secret, intdiv(time(), 30));
    }

    /** Beheerder met tweestapsverificatie aan (via de echte schermen ingesteld). */
    private function userWithTwoFactor(): User
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.enable'));
        $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => $this->now($user)]);
        auth()->logout();
        $this->flushSession();
        \Illuminate\Support\Facades\Cache::flush(); // bevestigingscode telt niet als "gebruikt" bij inloggen

        return $user->fresh();
    }

    private function loginWithPassword(User $user)
    {
        return $this->post(route('login'), ['email' => $user->email, 'password' => 'password']);
    }

    public function test_setup_needs_a_valid_first_code_and_then_shows_recovery_codes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->post(route('two-factor.enable'))->assertRedirect();
        $this->actingAs($user)->get(route('profile.edit'))->assertSee('Scan deze code')->assertSee('<svg', false);

        $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => '000000'])->assertSessionHasErrors('code', null, 'twoFactor');
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $this->actingAs($user)->post(route('two-factor.confirm'), ['code' => $this->now($user)])->assertSessionHas('two_factor_codes');
        $this->assertTrue($user->fresh()->hasTwoFactor());
        $this->assertCount(8, $user->fresh()->two_factor_recovery_codes);
        $this->assertStringNotContainsString($user->fresh()->two_factor_secret, (string) \DB::table('users')->value('two_factor_secret')); // versleuteld opgeslagen
    }

    public function test_login_requires_the_code_after_the_password(): void
    {
        $user = $this->userWithTwoFactor();

        $this->loginWithPassword($user)->assertRedirect(route('two-factor.challenge'));
        $this->assertGuest();
        $this->get(route('admin.dashboard'))->assertRedirect(route('login'));

        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertGuest();

        // Terug naar waar de beheerder heen wilde (/admin).
        $this->post(route('two-factor.challenge'), ['code' => $this->now($user)])->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_a_code_cannot_be_used_twice(): void
    {
        $user = $this->userWithTwoFactor();
        $code = $this->now($user);

        $this->loginWithPassword($user);
        $this->post(route('two-factor.challenge'), ['code' => $code]);
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->loginWithPassword($user);
        $this->post(route('two-factor.challenge'), ['code' => $code])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_recovery_code_works_exactly_once(): void
    {
        $user = $this->userWithTwoFactor();
        $recovery = $user->two_factor_recovery_codes[0];

        $this->loginWithPassword($user);
        $this->post(route('two-factor.challenge'), ['code' => strtolower($recovery)])->assertRedirect(route('dashboard'));
        $this->assertCount(7, $user->fresh()->two_factor_recovery_codes);

        $this->post(route('logout'));
        $this->loginWithPassword($user);
        $this->post(route('two-factor.challenge'), ['code' => $recovery])->assertSessionHasErrors('code');
        $this->assertGuest();
    }

    public function test_challenge_without_password_step_goes_back_to_login(): void
    {
        $this->get(route('two-factor.challenge'))->assertRedirect(route('login'));
        $this->post(route('two-factor.challenge'), ['code' => '123456'])->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_disabling_needs_the_password_and_rescue_command_works(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)->delete(route('two-factor.disable'), ['current_password' => 'fout'])->assertSessionHasErrors('current_password', null, 'twoFactor');
        $this->assertTrue($user->fresh()->hasTwoFactor());

        $this->actingAs($user)->delete(route('two-factor.disable'), ['current_password' => 'password']);
        $this->assertFalse($user->fresh()->hasTwoFactor());

        $locked = $this->userWithTwoFactor();
        $this->artisan('user:2fa-off', ['email' => $locked->email])->assertSuccessful();
        $this->assertFalse($locked->fresh()->hasTwoFactor());
        $this->loginWithPassword($locked)->assertRedirect(route('dashboard'));
    }
}
