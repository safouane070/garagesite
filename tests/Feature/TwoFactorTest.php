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

    /** Nieuwe herstelcodes: alleen met wachtwoord, en de oude werken daarna niet meer. */
    public function test_new_recovery_codes_need_the_password_and_replace_the_old_ones(): void
    {
        $user = $this->userWithTwoFactor();
        $old = $user->two_factor_recovery_codes;

        $this->actingAs($user)->post(route('two-factor.recovery-codes'), ['current_password' => 'fout'])
            ->assertSessionHasErrors('current_password', null, 'twoFactor');
        $this->assertSame($old, $user->fresh()->two_factor_recovery_codes);

        $this->actingAs($user)->post(route('two-factor.recovery-codes'), ['current_password' => 'password'])
            ->assertSessionHas('two_factor_codes');
        $new = $user->fresh()->two_factor_recovery_codes;
        $this->assertCount(8, $new);
        $this->assertEmpty(array_intersect($old, $new));

        auth()->logout();
        $this->loginWithPassword($user);
        $this->post(route('two-factor.challenge'), ['code' => $old[0]])->assertSessionHasErrors('code');
        $this->assertGuest();
        $this->get(route('two-factor.challenge'))->assertOk()->assertSee('herstelcode'); // tweede stap toont het scherm
    }

    /** Raden vanaf steeds een ander IP-adres helpt niet: de limiet geldt per account. */
    public function test_code_guessing_is_limited_per_account_not_per_ip(): void
    {
        $user = $this->userWithTwoFactor();
        $this->loginWithPassword($user);

        foreach (range(1, 5) as $i) {
            $this->withServerVariables(['REMOTE_ADDR' => "10.0.0.{$i}"])
                ->post(route('two-factor.challenge'), ['code' => '000000'])->assertSessionHasErrors('code');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('two-factor.challenge'), ['code' => $this->now($user)])->assertStatus(429);
        $this->assertGuest();
    }

    /** "Inschakelen" opnieuw aanroepen mocht een actieve 2FA niet zonder wachtwoord wissen. */
    public function test_enable_cannot_wipe_active_two_factor(): void
    {
        $user = $this->userWithTwoFactor();

        $this->actingAs($user)->post(route('two-factor.enable'))->assertForbidden();
        $this->assertTrue($user->fresh()->hasTwoFactor());
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
