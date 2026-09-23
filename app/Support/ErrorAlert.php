<?php

namespace App\Support;

use App\Mail\ErrorAlertMail;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Throwable;

/**
 * Eenvoudige foutbewaking zonder externe dienst: in productie krijgt de
 * beheerder een mail bij een fout (ERROR_ALERT_EMAIL). Dezelfde fout hooguit
 * eens per 10 minuten, zodat een storing geen mailstorm wordt.
 *
 * Bewust rechtstreeks verstuurd (niet via de wachtrij): als juist de wachtrij
 * of de database stuk is, moet deze melding er tóch doorkomen.
 */
class ErrorAlert
{
    private const THROTTLE_SECONDS = 600;

    public static function exception(Throwable $e): void
    {
        $where = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $e->getFile()) . ':' . $e->getLine();

        self::send(
            'Fout op de site: ' . class_basename($e),
            $e->getMessage() . "\n\nLocatie: {$where}\nURL: " . (app()->runningInConsole() ? '(console)' : request()->fullUrl()),
            get_class($e) . $where,
        );
    }

    public static function send(string $subject, string $body, ?string $key = null): void
    {
        $to = config('brand.alerts.error_email');
        if (config('app.env') !== 'production' || blank($to)) {
            return;
        }

        try {
            // Cache::add slaagt alleen als de sleutel nog niet bestaat → demping.
            if (! Cache::add('error-alert:' . md5($key ?? $subject), true, self::THROTTLE_SECONDS)) {
                return;
            }
            Mail::to($to)->send(new ErrorAlertMail($subject, $body));
        } catch (Throwable) {
            // Een melding mag nooit zelf de site laten vallen (of zichzelf herhalen).
        }
    }
}
