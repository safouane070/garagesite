<?php

namespace Tests\Feature;

use App\Mail\ErrorAlertMail;
use App\Support\ErrorAlert;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class SafetyNetTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
        config(['brand.alerts.error_email' => 'beheer@example.com']);
    }

    public function test_production_error_mails_the_admin_once_per_window(): void
    {
        config(['app.env' => 'production']);
        $e = new RuntimeException('Database weg');

        ErrorAlert::exception($e);
        ErrorAlert::exception($e); // zelfde fout direct nog eens: gedempt

        Mail::assertSent(ErrorAlertMail::class, 1);
        Mail::assertSent(ErrorAlertMail::class, fn ($m) => $m->hasTo('beheer@example.com')
            && str_contains($m->alertBody, 'Database weg'));
    }

    /** Via de echte foutafhandeling van Laravel (report()), niet alleen direct. */
    public function test_reported_exceptions_reach_the_alert(): void
    {
        config(['app.env' => 'production']);

        report(new RuntimeException('Iets onverwachts'));

        Mail::assertSent(ErrorAlertMail::class, fn ($m) => str_contains($m->alertBody, 'Iets onverwachts'));
    }

    public function test_no_alerts_outside_production_or_without_recipient(): void
    {
        ErrorAlert::exception(new RuntimeException('lokaal'));        // env = testing
        config(['app.env' => 'production', 'brand.alerts.error_email' => null]);
        ErrorAlert::exception(new RuntimeException('geen ontvanger'));

        Mail::assertNothingSent();
    }

    /** Een kapotte mailserver mag de site niet laten vallen. */
    public function test_alert_failure_is_swallowed(): void
    {
        config(['app.env' => 'production']);
        Mail::shouldReceive('to')->andThrow(new RuntimeException('smtp stuk'));

        ErrorAlert::exception(new RuntimeException('x'));

        $this->assertTrue(true); // geen exception = geslaagd
    }

    public function test_ci_workflow_runs_tests_and_build(): void
    {
        $yml = file_get_contents(base_path('.github/workflows/tests.yml'));

        $this->assertStringContainsString('php artisan test', $yml);
        $this->assertStringContainsString('npm run build', $yml);
        $this->assertStringContainsString('exif', $yml);
    }

    /** Dagelijks om 06:00, en een mislukte run (exitcode ≠ 0) mailt de beheerder. */
    public function test_failed_scheduled_sync_alerts_the_admin(): void
    {
        config(['app.env' => 'production']);
        $event = collect(app(\Illuminate\Console\Scheduling\Schedule::class)->events())
            ->first(fn ($e) => str_contains($e->command ?? '', 'cars:sync'));

        $this->assertNotNull($event, 'cars:sync staat niet ingepland');
        $this->assertSame('0 6 * * *', $event->expression);

        $event->exitCode = 1; // zoals na "Gestopt vóór enige wijziging"
        $event->callAfterCallbacks(app());

        Mail::assertSent(ErrorAlertMail::class, fn ($m) => $m->alertSubject === 'Voorraad-sync mislukt');
    }
}
