<?php

namespace App\Console\Commands;

use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\Lead;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;

/**
 * Controleert of aanvraag-mails echt aankomen, vóór klanten het merken:
 *  1. de mail-instellingen (.env);
 *  2. de DNS van het afzenddomein: SPF, DKIM en DMARC (anders: spam);
 *  3. verstuurt de twee echte mails (naar de zaak + bevestiging aan de klant)
 *     rechtstreeks (sendNow, buiten de wachtrij), zodat een fout meteen zichtbaar is.
 *
 *   php artisan mail:test jouw@adres.nl
 * Check daarna ook de spamscore: stuur naar het adres van mail-tester.com.
 */
class MailTest extends Command
{
    protected $signature = 'mail:test {to : Ontvanger van de testmails} {--dns-only : Alleen instellingen + DNS controleren}';

    protected $description = 'Controleert mail-instellingen en DNS (SPF/DKIM/DMARC) en verstuurt de aanvraag-mails als test.';

    /** Bekende SMTP-diensten: wat SPF moet bevatten en onder welke naam de DKIM-sleutel staat. */
    private const PROVIDERS = [
        'smtp.gmail.com' => ['spf' => '_spf.google.com', 'dkim' => 'google'],
        'smtp-relay.gmail.com' => ['spf' => '_spf.google.com', 'dkim' => 'google'],
        'smtp.transip.email' => ['spf' => '_spf.transip.email', 'dkim' => 'transip-A'],
    ];

    public function handle(): int
    {
        $mailer = config('mail.default');
        $host = (string) config("mail.mailers.{$mailer}.host");
        $from = (string) config('mail.from.address');
        $domain = substr(strrchr($from, '@') ?: '', 1);

        $this->line("Mailer: {$mailer}" . ($mailer === 'smtp' ? " via {$host}:" . config("mail.mailers.smtp.port") : ''));
        $this->line("Afzender: {$from} · naar de zaak: " . config('brand.contact.email'));

        $ok = true;
        if (in_array($mailer, ['log', 'array'], true)) {
            $this->warn("  ✗ MAIL_MAILER={$mailer}: mails worden niet echt verstuurd.");
            $ok = false;
        }
        if ($domain === '' || str_ends_with($domain, 'example.com')) {
            $this->warn('  ✗ MAIL_FROM_ADDRESS staat niet op het eigen domein.');
            $ok = false;
        }

        if ($domain !== '') {
            $ok = $this->checkDns($domain, self::PROVIDERS[$host] ?? null) && $ok;
        }

        if ($this->option('dns-only')) {
            return $ok ? self::SUCCESS : self::FAILURE;
        }

        // Voorbeeldaanvraag (niet opgeslagen) → dezelfde mails als een echte aanvraag.
        $lead = new Lead([
            'type' => 'proefrit', 'name' => 'Testaanvraag (mail:test)', 'email' => $this->argument('to'),
            'phone' => '06 12345678', 'message' => 'Dit is een testaanvraag om de mail te controleren.',
            'preferred_date' => now()->addDays(2)->toDateString(),
        ]);
        $lead->created_at = now();

        try {
            Mail::to($this->argument('to'))->sendNow(new LeadReceived($lead));
            Mail::to($this->argument('to'))->sendNow(new LeadConfirmation($lead));
        } catch (\Throwable $e) {
            $this->error('  ✗ Versturen mislukt: ' . $e->getMessage());

            return self::FAILURE;
        }

        $this->info("  ✓ 2 testmails verstuurd naar {$this->argument('to')} (aanvraag + bevestiging). Kijk ook in de spammap.");
        if ($ok) {
            $this->info('MAIL TEST OK');
        }

        return $ok ? self::SUCCESS : self::FAILURE;
    }

    /** @param  array{spf:string,dkim:string}|null  $provider */
    private function checkDns(string $domain, ?array $provider): bool
    {
        $txt = fn (string $name): array => array_map(
            fn ($r) => $r['txt'] ?? implode('', $r['entries'] ?? []),
            @dns_get_record($name, DNS_TXT) ?: []
        );

        $ok = true;
        $spf = array_values(array_filter($txt($domain), fn ($t) => str_starts_with($t, 'v=spf1')));
        if (count($spf) !== 1) {
            $this->warn('  ✗ SPF: ' . (count($spf) === 0 ? 'geen record' : 'meerdere records (mag er maar één zijn)'));
            $ok = false;
        } elseif ($provider && ! str_contains($spf[0], $provider['spf'])) {
            $this->warn("  ✗ SPF staat de mailserver niet toe (mist include:{$provider['spf']}): {$spf[0]}");
            $ok = false;
        } else {
            $this->line("  ✓ SPF: {$spf[0]}");
        }

        if ($provider) {
            $dkim = array_filter($txt("{$provider['dkim']}._domainkey.{$domain}"), fn ($t) => str_contains($t, 'p='));
            if ($dkim === [] && ! @dns_get_record("{$provider['dkim']}._domainkey.{$domain}", DNS_CNAME)) {
                $this->warn("  ✗ DKIM: geen sleutel op {$provider['dkim']}._domainkey.{$domain} (zie DEPLOY.md, stap 3)");
                $ok = false;
            } else {
                $this->line("  ✓ DKIM: {$provider['dkim']}._domainkey");
            }
        } else {
            $this->line('  ? DKIM: onbekende mailserver, controleer de DKIM-instelling bij je provider');
        }

        $dmarc = array_values(array_filter($txt("_dmarc.{$domain}"), fn ($t) => str_starts_with($t, 'v=DMARC1')));
        $dmarc ? $this->line("  ✓ DMARC: {$dmarc[0]}") : $this->warn('  ✗ DMARC: geen record');
        $ok = $ok && $dmarc !== [];

        return $ok;
    }
}
