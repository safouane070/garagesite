<?php

namespace App\Mail;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;

/** Foutmelding voor de beheerder (zie App\Support\ErrorAlert). Platte tekst. */
class ErrorAlertMail extends Mailable
{
    public function __construct(public string $alertSubject, public string $alertBody)
    {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: '[' . config('app.name') . '] ' . $this->alertSubject);
    }

    public function content(): Content
    {
        return new Content(text: 'emails.error-alert');
    }
}
