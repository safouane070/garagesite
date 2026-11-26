<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeadInboxSettings extends Model
{
    protected $fillable = ['notification_email'];

    public static function current(): self
    {
        // Bestaande productie-installaties behouden hun ontvanger totdat die hier is aangepast.
        return static::find(1) ?? new static([
            'notification_email' => config('brand.demo') ? null : config('brand.contact.email'),
        ]);
    }

    public static function usesDeliveryMailer(): bool
    {
        // Log/array zijn ontwikkelopslag, geen e-mailbezorging. Ook geen persoonsgegevens in logs.
        $transport = config('mail.mailers.'.config('mail.default').'.transport');

        return in_array($transport, ['smtp', 'sendmail', 'ses', 'ses-v2', 'postmark', 'resend', 'roundrobin'], true);
    }
}
