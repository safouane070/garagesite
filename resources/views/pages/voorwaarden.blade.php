<x-layouts.public title="Algemene voorwaarden"
    description="De algemene voorwaarden van Autobedrijf Rijswijk.">

    <x-page-hero kicker="Juridisch" title="Algemene voorwaarden" />

    <section class="container-x py-12 lg:py-16">
        <div class="mx-auto max-w-3xl space-y-8 leading-relaxed text-cream/75">
            <p class="rounded-[4px] border border-brass-500/30 bg-brass-500/[0.06] p-4 text-sm text-cream/70">
                Dit is een voorbeeldtekst voor de demo. Laat de definitieve voorwaarden vóór livegang opstellen
                of controleren door een jurist, afgestemd op de werkwijze van de zaak.
            </p>

            @php
                $sections = [
                    ['1. Toepasselijkheid', 'Deze voorwaarden zijn van toepassing op elk aanbod van Autobedrijf Rijswijk en op elke tot stand gekomen overeenkomst tussen de verkoper en de koper.'],
                    ['2. Aanbod en prijzen', 'Alle getoonde prijzen zijn in euro\'s en inclusief btw of op basis van de marge­regeling, tenzij anders vermeld. Kennelijke vergissingen of fouten in het aanbod binden de verkoper niet.'],
                    ['3. Garantie', 'Op de verkochte occasions is BOVAG-garantie van toepassing conform de geldende BOVAG-voorwaarden. De duur en dekking worden bij aankoop schriftelijk vastgelegd.'],
                    ['4. Levering en eigendom', 'De auto wordt geleverd met geldige APK, een servicebeurt en de bijbehorende documenten. Het eigendom gaat over na volledige betaling van de koopsom.'],
                    ['5. Inruil', 'Een inruilauto wordt overgenomen in de staat zoals beoordeeld op het moment van taxatie. Verborgen gebreken die de waarde beïnvloeden kunnen tot een herbeoordeling leiden.'],
                    ['6. Betaling', 'Betaling vindt plaats op de overeengekomen wijze en vóór of bij aflevering, tenzij schriftelijk anders is overeengekomen.'],
                    ['7. Herroeping', 'Een koop op afstand of buiten de verkoopruimte kan onderworpen zijn aan een wettelijk herroepingsrecht. Bezichtiging en aankoop in de showroom vallen hier doorgaans buiten.'],
                    ['8. Toepasselijk recht', 'Op alle overeenkomsten is Nederlands recht van toepassing. Geschillen worden voorgelegd aan de bevoegde rechter in het arrondissement van de verkoper.'],
                ];
            @endphp

            @foreach ($sections as [$title, $body])
                <div>
                    <h2 class="font-display text-lg font-semibold text-cream">{{ $title }}</h2>
                    <p class="mt-2 text-sm">{{ $body }}</p>
                </div>
            @endforeach
        </div>
    </section>
</x-layouts.public>
