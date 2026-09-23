<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Bevestiging naar de klant: "we hebben je aanvraag ontvangen". Zet
 * verwachtingen (reactietermijn) en scheelt "is het aangekomen?"-telefoontjes.
 * Antwoorden gaan naar de zaak (Reply-To).
 */
class LeadConfirmation extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    /** Opnieuw proberen als de mailserver hapert: na 1 en na 5 minuten. */
    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public Lead $lead)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'We hebben je aanvraag ontvangen — ' . config('app.name'),
            replyTo: [new Address(config('brand.contact.email'), config('app.name'))],
        );
    }

    public function content(): Content
    {
        return new Content(text: 'emails.lead-confirmation');
    }
}
