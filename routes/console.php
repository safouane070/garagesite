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
Schedule::command('cars:sync')->dailyAt('06:00')->withoutOverlapping();

// Wachtrij (aanvraag-mails) verwerken. Op shared hosting draait er geen vaste
// worker: de minuut-cron start 'm kort, hij stopt zodra de wachtrij leeg is.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();

// AVG: oude aanvragen opruimen volgens de bewaartermijn (Lead::prunable()).
Schedule::command('model:prune', ['--model' => [\App\Models\Lead::class]])->daily();
