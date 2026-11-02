<?php

namespace App\Providers;

use App\Http\Controllers\Auth\TwoFactorController;
use App\Support\SystemStatus;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Productie: alle gegenereerde links/redirects via https, ook achter een proxy.
        URL::forceHttps($this->app->isProduction());

        // Achter een proxy (Render) anders denkt Laravel dat alles http is en dat elke
        // bezoeker hetzelfde IP heeft (dan gelden de limieten voor iedereen samen).
        if ($proxies = config('app.trusted_proxies')) {
            TrustProxies::at($proxies);
        }

        // 2FA-code: limiet per account (de gebruiker uit de wachtwoordstap), niet per IP.
        // Per IP kon iemand met het wachtwoord en veel IP-adressen de 6 cijfers gewoon raden.
        RateLimiter::for('two-factor', fn (Request $request) => Limit::perMinute(5)->by(
            'two-factor:' . ($request->session()->get(TwoFactorController::SESSION_USER) ?? $request->ip())
        ));

        // /up (voor UptimeRobot e.d.) geeft 500 zodra database, cron of mail hapert;
        // de details gaan via report() naar de foutmail, niet naar de buitenwereld.
        Event::listen(DiagnosingHealth::class, function () {
            if ($problems = SystemStatus::problems()) {
                throw new \RuntimeException(implode(' ', $problems));
            }
        });
    }
}
