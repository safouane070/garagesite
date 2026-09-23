<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Houd de klantreviews vers: haal ze wekelijks op uit de Trustindex-feed.
Schedule::command('reviews:fetch')->weekly()->sundays()->at('04:00');

// Voorraad gelijk houden met de dealersite: nieuwe auto's erbij, verkochte eraf,
// prijswijzigingen bijgewerkt. Stopt zelf bij een verdachte bron (zie SyncCars).
Schedule::command('cars:sync')->dailyAt('06:00')->withoutOverlapping()
    ->when(fn () => filled(config('brand.dealer_site_url')))
    ->onFailure(fn () => \App\Support\ErrorAlert::send(
        'Voorraad-sync mislukt',
        "cars:sync is gestopt zonder wijzigingen (dealersite onbereikbaar of verdacht veel auto's verdwenen).\n"
        . 'Controleer de dealersite en draai zo nodig: php artisan cars:sync --dry-run',
    ));

// Wachtrij (aanvraag-mails) verwerken. Op shared hosting draait er geen vaste
// worker: de minuut-cron start 'm kort, hij stopt zodra de wachtrij leeg is.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();

// Hartslag: bewijst dat de cron draait (gecontroleerd door /up en het beheer, zie SystemStatus).
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put(\App\Support\SystemStatus::HEARTBEAT_KEY, time(), now()->addDay()))
    ->everyMinute()->name('heartbeat');

// AVG: oude aanvragen opruimen volgens de bewaartermijn (Lead::prunable()).
Schedule::command('model:prune', ['--model' => [\App\Models\Lead::class]])->daily();
