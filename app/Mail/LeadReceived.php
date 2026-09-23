<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Aanvraag naar de zaak. Via de wachtrij: het "Bedankt"-scherm wacht zo nooit
 * op een trage of haperende mailserver, en mislukte pogingen worden herhaald.
 */
class LeadReceived extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [60, 300];

    public function __construct(public Lead $lead)
    {
    }

    public function envelope(): Envelope
    {
        $subject = $this->lead->typeLabel();
        if ($this->lead->car) {
            $subject .= ' — ' . $this->lead->car->title();
        }

        return new Envelope(
            subject: 'Nieuwe aanvraag: ' . $subject,
            // Zo kan de zaak direct op de klant reageren met "Beantwoorden".
            replyTo: [new Address($this->lead->email, $this->lead->name)],
        );
    }

    public function content(): Content
    {
        // Platte tekst: de template leunt op regeleinden en uitlijning.
        return new Content(text: 'emails.lead-received');
    }
}
