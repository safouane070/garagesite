<?php

namespace Tests\Feature;

use App\Mail\LeadConfirmation;
use App\Mail\LeadReceived;
use App\Models\User;
use App\Support\SystemStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Bewaking van wat aanvragen raakt: cron, mailwachtrij, mislukte mails. */
class MonitoringTest extends TestCase
{
    use RefreshDatabase;

    private function heartbeat(int $minutesAgo = 0): void
    {
        Cache::put(SystemStatus::HEARTBEAT_KEY, time() - $minutesAgo * 60);
    }

    public function test_health_is_up_when_cron_runs_and_mail_flows(): void
    {
        $this->heartbeat();

        $this->assertSame([], SystemStatus::problems());
        $this->get('/up')->assertOk();
    }

    public function test_health_is_down_when_cron_stopped(): void
    {
        $this->heartbeat(minutesAgo: 30);

        $this->assertStringContainsString('cronjob draait niet', implode(' ', SystemStatus::problems()));
        $this->get('/up')->assertStatus(500);
    }

    public function test_stuck_and_failed_mails_are_reported_and_shown_to_the_owner(): void
    {
        $this->heartbeat();
        DB::table('jobs')->insert(['queue' => 'default', 'payload' => '{}', 'attempts' => 0, 'available_at' => time(), 'created_at' => time() - 20 * 60]);
        DB::table('failed_jobs')->insert(['uuid' => 'x', 'connection' => 'database', 'queue' => 'default', 'payload' => '{}', 'exception' => 'SMTP 535']);

        $problems = implode(' ', SystemStatus::problems());
        $this->assertStringContainsString('wachten mails al langer', $problems);
        $this->assertStringContainsString('1 mail kon', $problems);

        $this->actingAs(User::factory()->create())->get(route('admin.leads.index'))
            ->assertSee('er hapert iets achter de schermen')
            ->assertSee('1 mail kon');
    }

    public function test_the_heartbeat_is_scheduled(): void
    {
        $this->artisan('schedule:list')->expectsOutputToContain('heartbeat');
    }

    public function test_mail_test_sends_both_lead_mails_directly(): void
    {
        Mail::fake();
        config(['mail.default' => 'smtp', 'mail.from.address' => 'info@garage.invalid']);

        $this->artisan('mail:test', ['to' => 'eigenaar@example.org']);

        Mail::assertSent(LeadReceived::class, fn ($m) => $m->hasTo('eigenaar@example.org'));
        Mail::assertSent(LeadConfirmation::class, fn ($m) => $m->hasTo('eigenaar@example.org'));
        Mail::assertNothingQueued(); // direct, niet via de wachtrij: een fout moet meteen zichtbaar zijn
    }

    public function test_mail_test_flags_a_non_sending_mailer(): void
    {
        config(['mail.default' => 'log', 'mail.from.address' => 'hello@example.com']);

        $this->artisan('mail:test', ['to' => 'x@example.org', '--dns-only' => true])
            ->expectsOutputToContain('mails worden niet echt verstuurd')
            ->expectsOutputToContain('niet op het eigen domein')
            ->assertFailed();
    }
}
