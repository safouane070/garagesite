@php($lead = $lead)
Nieuwe aanvraag via de website
================================

Onderwerp : {{ $lead->typeLabel() }}
@if ($lead->car)
Auto      : {{ $lead->car->title() }} ({{ $lead->car->formattedPrice() }})
Link      : {{ route('cars.show', $lead->car) }}
@endif

Naam      : {{ $lead->name }}
E-mail    : {{ $lead->email }}
Telefoon  : {{ $lead->phone ?: '—' }}
Ontvangen : {{ $lead->created_at->format('d-m-Y H:i') }}

Bericht:
{{ $lead->message ?: '(geen bericht)' }}

--
Reageren kan rechtstreeks via "Beantwoorden" — dat gaat naar {{ $lead->email }}.
