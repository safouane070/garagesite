<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\Totp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Tweestapsverificatie met een authenticator-app (TOTP).
 *
 * Inschakelen gaat in twee stappen (geheim tonen → eerste code bevestigen), zodat
 * niemand zich buitensluit met een half ingestelde app. Daarna krijgt de beheerder
 * 8 herstelcodes voor eenmalig gebruik. Kwijt? `php artisan user:2fa-off <email>`.
 */
class TwoFactorController extends Controller
{
    public const SESSION_USER = 'two_factor.user_id';

    public const SESSION_REMEMBER = 'two_factor.remember';

    /* ─── Profiel: instellen ─────────────────────────────────────────── */

    public function enable(Request $request): RedirectResponse
    {
        $request->user()->forceFill([
            'two_factor_secret' => Totp::generateSecret(),
            'two_factor_confirmed_at' => null,
            'two_factor_recovery_codes' => null,
        ])->save();

        return redirect()->to(route('profile.edit') . '#tweestaps');
    }

    public function confirm(Request $request): RedirectResponse
    {
        $request->validate(['code' => ['required', 'string']]);
        $user = $request->user();

        if (! $user->two_factor_secret || $user->two_factor_confirmed_at || ! Totp::verify($user->two_factor_secret, $request->input('code'))) {
            throw ValidationException::withMessages(['code' => 'Deze code klopt niet. Kijk of de tijd op je telefoon goed staat en probeer de nieuwste code.'])
                ->errorBag('twoFactor');
        }

        $codes = self::newRecoveryCodes();
        $user->forceFill(['two_factor_confirmed_at' => now(), 'two_factor_recovery_codes' => $codes])->save();

        return redirect()->to(route('profile.edit') . '#tweestaps')->with('two_factor_codes', $codes);
    }

    public function recoveryCodes(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']]);
        abort_unless($request->user()->hasTwoFactor(), 404);

        $codes = self::newRecoveryCodes();
        $request->user()->forceFill(['two_factor_recovery_codes' => $codes])->save();

        return redirect()->to(route('profile.edit') . '#tweestaps')->with('two_factor_codes', $codes);
    }

    public function disable(Request $request): RedirectResponse
    {
        $request->validateWithBag('twoFactor', ['current_password' => ['required', 'current_password']]);

        $request->user()->forceFill([
            'two_factor_secret' => null, 'two_factor_recovery_codes' => null, 'two_factor_confirmed_at' => null,
        ])->save();

        return redirect()->to(route('profile.edit') . '#tweestaps')->with('status', 'two-factor-disabled');
    }

    /* ─── Inloggen: tweede stap ──────────────────────────────────────── */

    public function challenge(Request $request): View|RedirectResponse
    {
        return $request->session()->has(self::SESSION_USER)
            ? view('auth.two-factor-challenge')
            : redirect()->route('login');
    }

    public function verify(Request $request): RedirectResponse
    {
        $user = User::find($request->session()->get(self::SESSION_USER));
        if (! $user?->hasTwoFactor()) {
            return redirect()->route('login');
        }

        $input = trim((string) $request->input('code'));
        if (! $this->passes($user, $input)) {
            throw ValidationException::withMessages(['code' => 'Deze code klopt niet.']);
        }

        $remember = (bool) $request->session()->pull(self::SESSION_REMEMBER);
        $request->session()->forget(self::SESSION_USER);
        Auth::login($user, $remember);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard', absolute: false));
    }

    /** Code uit de app (niet twee keer dezelfde), of een ongebruikte herstelcode. */
    private function passes(User $user, string $input): bool
    {
        if (preg_match('/^\d{3}\s?\d{3}$/', $input)) {
            $step = Totp::verify($user->two_factor_secret, $input);

            // Cache::add slaagt maar één keer per gebruiker + tijdstap: een onderschepte
            // code is daarna waardeloos.
            return $step !== null && Cache::add("two_factor:used:{$user->id}:{$step}", true, 120);
        }

        $codes = $user->two_factor_recovery_codes ?? [];
        $normalized = strtoupper(str_replace(' ', '', $input));
        foreach ($codes as $i => $code) {
            if (hash_equals($code, $normalized)) {
                unset($codes[$i]);
                $user->forceFill(['two_factor_recovery_codes' => array_values($codes)])->save();

                return true;
            }
        }

        return false;
    }

    /** @return list<string> bv. "K7QM-4XTR" */
    private static function newRecoveryCodes(): array
    {
        return array_map(fn () => Str::upper(Str::random(4) . '-' . Str::random(4)), range(1, 8));
    }
}
