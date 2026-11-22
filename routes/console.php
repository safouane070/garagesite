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
// --isolated: zelfde slot als de opstart-sync in docker/start.sh. --require-parse-ratio:
// verandert de dealer zijn template, dan is niets meer leesbaar en moet de foutmail komen.
Schedule::command('cars:sync', [
    // '--isolated' zonder waarde: met een waarde (true → "1") wordt een overgeslagen run een fout.
    '--max-photos' => config('brand.sync.max_photos'), '--isolated', '--require-parse-ratio' => 0.5,
])
    ->cron(config('brand.sync.cron'))
    ->when(fn () => filled(config('brand.dealer_site_url')))
    ->onFailure(fn () => \App\Support\ErrorAlert::send(
        'Voorraad-sync mislukt',
        "cars:sync is gestopt: dealersite onbereikbaar, verdacht veel auto's verdwenen, of minder dan de helft\n"
        . "van de voertuigpagina's leesbaar (de dealer veranderde waarschijnlijk zijn site).\n"
        . 'Controleer de dealersite en draai zo nodig: php artisan cars:sync --dry-run',
    ));

// Specs aanvullen uit de Marktplaats-advertenties van de dealer (na de ochtend-sync).
Schedule::command('cars:enrich')->dailyAt('06:30')->withoutOverlapping()
    ->when(fn () => filled(config('brand.marktplaats_seller')));

// Wachtrij (aanvraag-mails) verwerken. Op shared hosting draait er geen vaste
// worker: de minuut-cron start 'm kort, hij stopt zodra de wachtrij leeg is.
Schedule::command('queue:work --stop-when-empty --tries=3 --max-time=50')->everyMinute()->withoutOverlapping();

// Opslag: van auto's die >60 dagen verkocht zijn blijft alleen de omslagfoto.
Schedule::command('cars:prune-photos')->dailyAt('05:30');

// Hartslag: bewijst dat de cron draait (gecontroleerd door /up en het beheer, zie SystemStatus).
Schedule::call(fn () => \Illuminate\Support\Facades\Cache::put(\App\Support\SystemStatus::HEARTBEAT_KEY, time(), now()->addDay()))
    ->everyMinute()->name('heartbeat');

// AVG: oude aanvragen opruimen volgens de bewaartermijn (Lead::prunable()).
Schedule::command('model:prune', ['--model' => [\App\Models\Lead::class]])->daily();
