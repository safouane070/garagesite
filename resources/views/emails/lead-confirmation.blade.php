{{-- Platte-tekstmail (text/plain), zie lead-received voor de {!! !!}-uitleg. --}}
Hallo {!! $lead->name !!},

Bedankt voor je aanvraag bij {!! config('app.name') !!}. We hebben 'm goed ontvangen.

Onderwerp : {!! $lead->typeLabel() !!}
@if ($lead->car)
Auto      : {!! $lead->car->title() !!} ({!! $lead->car->formattedPrice() !!})
@endif
@if ($lead->preferred_date)
Voorkeur  : {!! $lead->preferred_date->translatedFormat('l j F Y') !!}
@endif

@if ($lead->preferred_date)
We nemen meestal binnen één werkdag contact met je op om de afspraak te bevestigen.
@else
We nemen meestal binnen één werkdag contact met je op.
@endif

Liever direct contact?
Bel     : {!! config('brand.contact.phone') !!}
WhatsApp: https://wa.me/{!! config('brand.contact.whatsapp') !!}
Adres   : {!! config('brand.contact.address') !!}

Met vriendelijke groet,
{!! config('app.name') !!}

--
Je ontvangt deze mail omdat je een aanvraag deed via onze website. Beantwoord
deze mail gerust; je reactie komt direct bij ons terecht.
