{{-- Platte-tekstmail (text/plain): daarom {!! !!} — HTML-escaping zou hier
     letterlijk als "auto&#039;s" in de mail belanden. Geen XSS-risico, want
     text/plain wordt nooit als HTML weergegeven. --}}
Nieuwe aanvraag via de website
================================

Onderwerp : {!! $lead->typeLabel() !!}
@if ($lead->preferred_date)
Voorkeur  : {!! $lead->preferred_date->translatedFormat('l j F Y') !!}
@endif
@if ($lead->car)
Auto      : {!! $lead->car->title() !!} ({!! $lead->car->formattedPrice() !!})
Link      : {!! route('cars.show', $lead->car) !!}
@endif

Naam      : {!! $lead->name !!}
E-mail    : {!! $lead->email !!}
Telefoon  : {!! $lead->phone ?: '—' !!}
Ontvangen : {!! $lead->created_at->format('d-m-Y H:i') !!}

Bericht:
{!! $lead->message ?: '(geen bericht)' !!}

--
Reageren kan rechtstreeks via "Beantwoorden" — dat gaat naar {!! $lead->email !!}.
