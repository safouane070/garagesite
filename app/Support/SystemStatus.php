<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Werkt alles wat bezoekers niet zien, maar waar aanvragen van afhangen?
 * Gebruikt door /health (externe bewaking, bv. UptimeRobot) en door de
 * melding bovenaan het beheer.
 *
 * - database bereikbaar;
 * - de cron draait (de scheduler zet elke minuut een hartslag) — zonder cron
 *   blijven aanvraag-mails in de wachtrij hangen en stopt de voorraad-sync;
 * - geen mails die al te lang wachten of definitief mislukt zijn.
 */
class SystemStatus
{
    public const HEARTBEAT_KEY = 'scheduler:heartbeat';

    /** Zo lang mag de laatste hartslag / de oudste wachtende mail oud zijn. */
    private const MAX_MINUTES = 10;

    /** @return list<string> leesbare problemen; leeg = alles in orde */
    public static function problems(): array
    {
        try {
            DB::select('select 1');
        } catch (\Throwable) {
            return ['De database is niet bereikbaar.'];
        }

        $problems = [];
        $minutes = fn (int $timestamp) => (int) floor((time() - $timestamp) / 60);

        // Lokaal draait er geen cron (de wachtrij is daar 'sync'): niet melden.
        $beat = Cache::get(self::HEARTBEAT_KEY);
        if (! app()->isLocal() && (! $beat || $minutes($beat) > self::MAX_MINUTES)) {
            $problems[] = 'De cronjob draait niet: aanvraag-mails blijven liggen en de voorraad wordt niet bijgewerkt.';
        }

        $oldest = DB::table('jobs')->min('created_at');
        if ($oldest && $minutes((int) $oldest) > self::MAX_MINUTES) {
            $problems[] = 'Er wachten mails al langer dan ' . self::MAX_MINUTES . ' minuten in de wachtrij.';
        }

        $failed = DB::table('failed_jobs')->where('failed_at', '>=', now()->subDay())->count();
        if ($failed > 0) {
            $problems[] = "{$failed} " . ($failed === 1 ? 'mail kon' : 'mails konden') . ' de afgelopen 24 uur niet worden verstuurd. Controleer de mailinstellingen (php artisan mail:test).';
        }

        return $problems;
    }
}
