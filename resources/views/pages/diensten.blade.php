<x-layouts.public title="Diensten"
    description="Inkoop, verkoop, aankoopbemiddeling en gepersonaliseerde zoekopdrachten. Autobedrijf Rijswijk ontzorgt je bij elke stap rond je auto.">

    <x-page-hero title="Alles rondom je auto onder één dak"
        intro="Van het vinden van je droomauto tot het verkopen van je huidige. Wij begeleiden het hele traject — betrouwbaar, transparant en zonder gedoe."
        image="images/site/golf-r.webp" alt="Volkswagen Golf R voor de showroom van Autobedrijf Rijswijk" position="60% 60%" />

    <section class="container-x py-12 lg:py-16">
        @php
            $services = [
                ['t' => 'Inkoop', 'd' => 'We kopen je auto direct in tegen een eerlijke dagprijs. Snelle afhandeling, betaling en vrijwaring geregeld.', 'cta' => 'Verkoop je auto', 'url' => route('diensten', ['onderwerp' => 'inkoop']) . '#contact'],
                ['t' => 'Verkoop', 'd' => 'Elke occasion met BOVAG-garantie, APK en servicebeurt. Geen afleverkosten, geen verrassingen.', 'cta' => 'Bekijk de occasions', 'url' => route('cars.index')],
                ['t' => 'Aankoop', 'd' => 'Zelf een auto op het oog? Wij begeleiden je bij de aankoop, zodat je met een gerust hart koopt.', 'cta' => 'Bespreek aankoopbegeleiding', 'url' => route('diensten', ['onderwerp' => 'aankoop']) . '#contact'],
                ['t' => 'Zoekopdracht', 'd' => 'Niet in de showroom gevonden? Geef je wensen door, dan gaan wij gericht voor je op zoek.', 'cta' => 'Plaats een zoekopdracht', 'url' => route('diensten', ['onderwerp' => 'zoekopdracht']) . '#contact'],
            ];
        @endphp
        {{-- Lijst met scheidingslijnen, geen kaartjes met icoon-cirkels. --}}
        <div class="divide-y divide-hairline border-y border-hairline lg:grid lg:grid-cols-4 lg:divide-x lg:divide-y-0">
            @foreach ($services as $s)
                <div class="flex flex-col py-6 lg:px-6 lg:py-8 lg:first:pl-0">
                    <h2 class="font-display text-xl font-semibold text-cream">{{ $s['t'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-cream/70">{{ $s['d'] }}</p>
                    <a href="{{ $s['url'] }}" class="mt-auto inline-flex min-h-11 items-center gap-2 pt-4 text-sm font-medium text-brass-300 underline-offset-4 hover:underline">
                        {{ $s['cta'] }} <x-icon name="arrow-right" class="h-4 w-4 shrink-0" />
                    </a>
                </div>
            @endforeach
        </div>
    </section>

    @php
        $type = request()->query('onderwerp');
        $copy = match ($type) {
            'inkoop' => [
                'type' => 'inkoop',
                'title' => 'Verkoop je auto',
                'intro' => 'Vertel ons welke auto je wilt verkopen. Vermeld in je bericht het kenteken, de kilometerstand en de staat van de auto. Dan nemen we contact met je op om de mogelijkheden te bespreken.',
            ],
            'aankoop' => [
                'type' => 'aankoop',
                'title' => 'Bespreek aankoopbegeleiding',
                'intro' => 'Een auto op het oog? Deel de advertentielink, je budget en je vragen in het bericht. Dan bespreken we hoe we je bij de aankoop kunnen helpen.',
            ],
            default => [
                'type' => 'zoekopdracht',
                'title' => 'Plaats een zoekopdracht',
                'intro' => 'Vertel ons welk merk, model en welke uitvoering je zoekt, wat je budget is en wat je belangrijk vindt. Dan zoeken we gericht met je mee.',
            ],
        };
    @endphp
    <section class="container-x border-t border-hairline py-14">
        <div class="mx-auto max-w-2xl">
            <x-lead-form :type="$copy['type']" :title="$copy['title']" :intro="$copy['intro']" />
        </div>
    </section>
</x-layouts.public>
