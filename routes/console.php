<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Houd de klantreviews vers: haal ze wekelijks op uit de Trustindex-feed.
Schedule::command('reviews:fetch')->weekly()->sundays()->at('04:00');
