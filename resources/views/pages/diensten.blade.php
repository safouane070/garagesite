<x-layouts.public title="Diensten"
    description="Inkoop, verkoop, aankoopbemiddeling en gepersonaliseerde zoekopdrachten. Autobedrijf Rijswijk ontzorgt je bij elke stap rond je auto.">

    <x-page-hero kicker="Diensten"
        title="Alles rondom je auto onder één dak"
        intro="Van het vinden van je droomauto tot het verkopen van je huidige. Wij begeleiden het hele traject — betrouwbaar, transparant en zonder gedoe." />

    <section class="container-x py-12 lg:py-16">
        @php
            $services = [
                ['icon' => 'tag', 't' => 'Inkoop', 'd' => 'We kopen je auto direct in tegen een eerlijke dagprijs. Snelle afhandeling, betaling en vrijwaring geregeld.'],
                ['icon' => 'badge-check', 't' => 'Verkoop', 'd' => 'Elke occasion met BOVAG-garantie, APK en servicebeurt. Geen afleverkosten, geen verrassingen.'],
                ['icon' => 'users', 't' => 'Aankoop', 'd' => 'Zelf een auto op het oog? Wij begeleiden je bij de aankoop, zodat je met een gerust hart koopt.'],
                ['icon' => 'search', 't' => 'Zoekopdracht', 'd' => 'Niet in de showroom gevonden? Geef je wensen door, dan gaan wij gericht voor je op zoek.'],
            ];
        @endphp
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($services as $s)
                <div class="flex flex-col rounded-[4px] border border-hairline bg-graphite-700/50 p-6 transition hover:border-brass-500/40">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full border border-brass-500/35 text-brass-400">
                        <x-icon name="{{ $s['icon'] }}" class="h-5 w-5" />
                    </span>
                    <h2 class="mt-5 font-display text-xl font-semibold text-cream">{{ $s['t'] }}</h2>
                    <p class="mt-2 text-sm leading-relaxed text-cream/70">{{ $s['d'] }}</p>
                </div>
            @endforeach
        </div>
    </section>

    {{-- Zoekopdracht --}}
    <section class="container-x border-t border-hairline py-14">
        <div class="mb-8 max-w-2xl">
            <p class="kicker">Zoekopdracht</p>
            <h2 class="mt-3 font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">Samen vinden we jouw droomauto</h2>
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
