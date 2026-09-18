<x-layouts.public title="Privacybeleid"
    description="Hoe Autobedrijf Rijswijk omgaat met je persoonsgegevens.">

    <x-page-hero kicker="Juridisch" title="Privacybeleid" />

    <section class="container-x py-12 lg:py-16">
        <div class="mx-auto max-w-3xl space-y-8 leading-relaxed text-cream/75">
            <p class="rounded-[4px] border border-brass-500/30 bg-brass-500/[0.06] p-4 text-sm text-cream/70">
                Dit is een voorbeeldtekst voor de demo. Stem het definitieve privacybeleid vóór livegang af op de
                daadwerkelijke gegevensverwerking (AVG) en laat het zo nodig juridisch controleren.
            </p>

            @php
                $sections = [
                    ['Welke gegevens we verwerken', 'Wanneer je een aanvraag of contactformulier indient, verwerken we je naam, e-mailadres, telefoonnummer en je bericht. Bij een afspraak ook een voorkeursdatum.'],
                    ['Waarvoor we ze gebruiken', 'We gebruiken je gegevens uitsluitend om te reageren op je aanvraag, een afspraak in te plannen en je te informeren over de betreffende auto of dienst.'],
                    ['Bewaartermijn', 'We bewaren je gegevens niet langer dan nodig is voor het doel waarvoor ze zijn verstrekt, of zolang een wettelijke bewaarplicht dat vereist.'],
                    ['Delen met derden', 'We delen je gegevens niet met derden, tenzij dat noodzakelijk is voor de uitvoering van de overeenkomst (bijvoorbeeld een financierings- of RDW-partij) of wettelijk verplicht is.'],
                    ['Je rechten', 'Je hebt het recht om je gegevens in te zien, te corrigeren of te laten verwijderen. Neem hiervoor contact met ons op via de contactgegevens onderaan de site.'],
                    ['Cookies', 'Deze site gebruikt uitsluitend functionele voorzieningen die nodig zijn om de website te laten werken. Er worden geen tracking-cookies voor advertenties geplaatst.'],
                ];
            @endphp

            @foreach ($sections as [$title, $body])
                <div>
                    <h2 class="font-display text-lg font-semibold text-cream">{{ $title }}</h2>
                    <p class="mt-2 text-sm">{{ $body }}</p>
                </div>
            @endforeach

            <p class="text-sm text-cream/60">
                Vragen over je privacy? Mail ons via
                <a href="mailto:{{ config('brand.contact.email') }}" class="text-brass-300 hover:underline">{{ config('brand.contact.email') }}</a>.
            </p>
        </div>
    </section>
</x-layouts.public>
