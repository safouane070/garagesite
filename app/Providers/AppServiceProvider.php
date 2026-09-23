<?php

namespace App\Providers;

use App\Support\SystemStatus;
use Illuminate\Foundation\Events\DiagnosingHealth;
use Illuminate\Support\Facades\Event;
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

        // /up (voor UptimeRobot e.d.) geeft 500 zodra database, cron of mail hapert;
        // de details gaan via report() naar de foutmail, niet naar de buitenwereld.
        Event::listen(DiagnosingHealth::class, function () {
            if ($problems = SystemStatus::problems()) {
                throw new \RuntimeException(implode(' ', $problems));
            }
        });
    }
}
