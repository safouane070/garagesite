<x-layouts.public title="Diensten"
    description="Inkoop, verkoop, aankoopbemiddeling en gepersonaliseerde zoekopdrachten. Autobedrijf Rijswijk ontzorgt je bij elke stap rond je auto.">

    <x-page-hero title="Alles rondom je auto onder één dak"
        intro="Van het vinden van je droomauto tot het verkopen van je huidige. Wij begeleiden het hele traject — betrouwbaar, transparant en zonder gedoe." />

    <section class="container-x py-12 lg:py-16">
        @php
            $services = [
                ['t' => 'Inkoop', 'd' => 'We kopen je auto direct in tegen een eerlijke dagprijs. Snelle afhandeling, betaling en vrijwaring geregeld.'],
                ['t' => 'Verkoop', 'd' => 'Elke occasion met BOVAG-garantie, APK en servicebeurt. Geen afleverkosten, geen verrassingen.'],
                ['t' => 'Aankoop', 'd' => 'Zelf een auto op het oog? Wij begeleiden je bij de aankoop, zodat je met een gerust hart koopt.'],
                ['t' => 'Zoekopdracht', 'd' => 'Niet in de showroom gevonden? Geef je wensen door, dan gaan wij gericht voor je op zoek.'],
            ];
        @endphp
        {{-- Lijst met scheidingslijnen, geen kaartjes met icoon-cirkels. --}}
        <div class="divide-y divide-hairline border-y border-hairline lg:grid lg:grid-cols-4 lg:divide-x lg:divide-y-0">
            @foreach ($services as $s)
                <div class="py-6 lg:px-6 lg:py-8 lg:first:pl-0">
                    <h2 class="font-display text-xl font-semibold text-cream">{{ $s['t'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-cream/70">{{ $s['d'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Zoekopdracht --}}
    <section class="container-x border-t border-hairline py-14">
        <div class="mb-8 max-w-2xl">
            <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">Samen vinden we jouw droomauto</h2>
            <p class="mt-4 leading-relaxed text-cream/70">
                Vertel ons welke auto je zoekt — merk, budget, uitvoering — en wij gaan gericht op zoek.
                Zodra we een passende occasion binnenkrijgen, ben jij de eerste die het hoort.
            </p>
        </div>
        <div class="mx-auto max-w-2xl">
            <x-lead-form type="zoekopdracht"
                title="Plaats een zoekopdracht"
                intro="Beschrijf je droomauto zo concreet mogelijk in het bericht, dan gaan we voor je aan de slag." />
        </div>
    </section>
</x-layouts.public>
