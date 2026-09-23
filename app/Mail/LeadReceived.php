<?php

namespace App\Mail;

use App\Models\Lead;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class LeadReceived extends Mailable
{
    use Queueable, SerializesModels;

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
