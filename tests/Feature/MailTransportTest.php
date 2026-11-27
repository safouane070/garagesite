<?php

namespace Tests\Feature;

use App\Models\LeadInboxSettings;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Render Free blokkeert SMTP (poorten 25/465/587): meldingen gaan daar via Resend (HTTPS).
 * Zonder het pakket resend/resend-php crasht het versturen pas bij de eerste echte aanvraag.
 */
class MailTransportTest extends TestCase
{
    public function test_resend_transport_can_be_built(): void
    {
        config(['mail.default' => 'resend', 'services.resend.key' => 're_test_123']);

        $transport = Mail::mailer('resend')->getSymfonyTransport();

        $this->assertStringContainsString('resend', strtolower((string) $transport));
        $this->assertTrue(LeadInboxSettings::usesDeliveryMailer());
    }
}
