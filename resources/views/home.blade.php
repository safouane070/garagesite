<x-layouts.public>
    {{-- ═══════════ HERO · echte showroomfoto met tekst eroverheen ═══════════ --}}
    <section class="relative -mt-[4.5rem] flex min-h-[90vh] items-end overflow-hidden bg-ink pt-[4.5rem]">
        {{-- Echte foto van het wagenpark van de zaak --}}
        {{-- Staand scherm (telefoon): de middenuitsnede. object-cover toont daar toch alleen het
             midden van de vierkante foto; zelfde beeld, ±40% minder bytes voor de grootste afbeelding. --}}
        <picture>
            <source media="(max-aspect-ratio: 9/16)" srcset="{{ asset('images/hero-showroom-portrait.webp') }}" width="1000" height="1600">
            <img src="{{ asset('images/hero-showroom.webp') }}" alt="Wagenpark van {{ config('app.name') }}"
                 width="1600" height="1600" fetchpriority="high" decoding="async"
                 class="absolute inset-0 h-full w-full object-cover object-center">
        </picture>
        {{-- Lichte overlay: alleen onderin/links donker voor tekst, boven blijft de foto helder --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-ink via-ink/40 to-ink/5"></div>
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-r from-ink/55 via-transparent to-transparent"></div>

        <div class="container-x relative py-16">
            <div class="max-w-2xl" style="text-shadow: 0 2px 24px rgba(0,0,0,0.45)">
                <h1 class="font-display text-[2.6rem] font-bold uppercase leading-[0.95] tracking-tight text-white sm:text-6xl lg:text-[4.6rem]">
                    Dé Duitse occasions<br>van Rijswijk
                </h1>

                {{-- USP's, zoals op de originele hero (kort, geen lange alinea) --}}
                <ul class="mt-8 flex flex-wrap gap-x-6 gap-y-2">
                    @foreach (['BOVAG-garantie', 'Geen afleverkosten', 'VAG-specialist'] as $usp)
                        <li class="inline-flex items-center gap-2 text-sm font-medium text-cream">
                            <x-icon name="check" class="h-4 w-4 text-brass-400" /> {{ $usp }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-9 flex flex-wrap items-center gap-4">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary">
                        Bekijk het aanbod <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="tel:{{ config('brand.contact.phone_href') }}"
                       class="inline-flex items-center gap-2 rounded-[3px] border border-cream/25 px-5 py-2.5 text-sm font-medium text-cream backdrop-blur-sm transition hover:border-brass-400/70 hover:text-brass-300">
                        <x-icon name="phone" class="h-4 w-4" /> {{ config('brand.contact.phone') }}
                    </a>
                </div>
            </div>
        </div>
    </section>

    {{-- ═══════════ CIJFERS ═══════════ --}}
    <x-home.stats />

    {{-- ═══════════ AANRADERS ═══════════ --}}
    @if ($featured->count())
        <section class="container-x py-16 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-hairline pb-6">
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                    Onze aanraders
                </h2>
                <a href="{{ route('cars.index') }}" class="group inline-flex items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300">
                    Volledig aanbod
                    <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </a>
            </div>

            <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($featured as $car)
                    <x-car-card :car="$car" />
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════ VERTROUWEN · ingetogen band (geen kaartjes, geen gimmick) ═══════════ --}}
    @php
        $trust = [
            ['icon' => 'shield-check', 't' => 'BOVAG-garantie', 'd' => 'Elke occasion met BOVAG-garantie en een geldige keuring.'],
            ['icon' => 'tag', 't' => 'Geen afleverkosten', 'd' => 'De prijs die je ziet, is de prijs die je betaalt.'],
            ['icon' => 'repeat', 't' => 'Inruil & financiering', 'd' => 'Een eerlijke inruilprijs en financiering die bij je past.'],
        ];
    @endphp
    <section class="border-y border-hairline bg-graphite-800">
        <div class="container-x grid gap-x-10 gap-y-8 py-12 md:grid-cols-3 lg:py-14">
            @foreach ($trust as $item)
                <div class="flex items-start gap-4 {{ ! $loop->last ? 'md:border-r md:border-hairline md:pr-10' : '' }}">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400">
                        <x-icon name="{{ $item['icon'] }}" class="h-5 w-5" />
                    </span>
                    <div>
                        <p class="font-display text-base font-semibold text-cream">{{ $item['t'] }}</p>
                        <p class="mt-1.5 text-sm leading-relaxed text-cream/65">{{ $item['d'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </section>

    {{-- ═══════════ NET BINNEN · scroll-rail ═══════════ --}}
    <section class="py-16 lg:py-20">
        <div class="container-x flex items-end justify-between gap-4 border-b border-hairline pb-6">
            <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">Net binnengereden</h2>
            <a href="{{ route('cars.index') }}" class="group hidden items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300 sm:inline-flex">
                Bekijk alles <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
            </a>
        </div>

        <div class="mt-10 flex snap-x snap-mandatory gap-6 overflow-x-auto px-5 pb-4 sm:px-8 [scrollbar-width:thin]">
            @foreach ($newest as $car)
                <div class="w-[280px] shrink-0 snap-start sm:w-[330px]">
                    <x-car-card :car="$car" />
                </div>
            @endforeach
            <a href="{{ route('cars.index') }}" class="flex w-[200px] shrink-0 snap-start flex-col items-center justify-center gap-3 rounded-[4px] border border-dashed border-hairline text-cream/55 transition hover:border-brass-500/50 hover:text-brass-300">
                <span class="flex h-12 w-12 items-center justify-center rounded-full border border-hairline"><x-icon name="arrow-right" class="h-5 w-5" /></span>
                <span class="font-mono text-xs uppercase tracking-wider">Bekijk alles</span>
            </a>
        </div>
    </section>

    {{-- ═══════════ MET TROTS VERKOCHT ═══════════ --}}
    <x-home.sold :cars="$sold" />

    {{-- ═══════════ DIENSTEN ═══════════ --}}
    <section class="container-x py-16 lg:py-24">
        <div class="flex flex-wrap items-end justify-between gap-4 border-b border-hairline pb-6">
            <div>
                <p class="kicker">Diensten</p>
                <h2 class="mt-3 font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                    Alles rondom je auto
                </h2>
            </div>
            <a href="{{ route('diensten') }}" class="group inline-flex items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300">
                Alle diensten <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
            </a>
        </div>

        @php
            $teaser = [
                ['icon' => 'tag', 't' => 'Inkoop', 'd' => 'Eerlijke dagprijs voor je auto, direct geregeld.'],
                ['icon' => 'badge-check', 't' => 'Verkoop', 'd' => 'Kwaliteitsoccasions met BOVAG-garantie, zonder afleverkosten.'],
                ['icon' => 'users', 't' => 'Aankoop', 'd' => 'Zelf een auto op het oog? Wij begeleiden je bij de aankoop.'],
                ['icon' => 'search', 't' => 'Zoekopdracht', 'd' => 'Niet gevonden? Wij zoeken gericht naar jouw droomauto.'],
            ];
        @endphp
        <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($teaser as $s)
                <a href="{{ route('diensten') }}" class="flex flex-col rounded-[4px] border border-hairline bg-graphite-700/50 p-6 transition hover:-translate-y-1 hover:border-brass-500/40">
                    <span class="flex h-11 w-11 items-center justify-center rounded-full border border-brass-500/35 text-brass-400">
                        <x-icon name="{{ $s['icon'] }}" class="h-5 w-5" />
                    </span>
                    <h3 class="mt-5 font-display text-lg font-semibold text-cream">{{ $s['t'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-cream/65">{{ $s['d'] }}</p>
                </a>
            @endforeach
        </div>
    </section>

    {{-- ═══════════ KLANTERVARINGEN ═══════════ --}}
    <x-home.reviews class="border-t border-hairline" />

    {{-- ═══════════ CTA ═══════════ --}}
    <section class="container-x py-16 pb-24">
        <div class="grid items-center gap-8 rounded-[4px] border border-hairline bg-graphite-700 p-8 lg:grid-cols-12 lg:p-14">
            <div class="lg:col-span-8">
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                    Iets specifieks zoeken?
                </h2>
                <p class="mt-4 max-w-lg leading-relaxed text-cream/70">
                    Niet gevonden wat je zoekt? Laat het ons weten, dan gaan we gericht
                    voor je op zoek en houden we je op de hoogte zodra hij binnenkomt.
                </p>
            </div>
            <div class="flex flex-wrap gap-3 lg:col-span-4 lg:justify-end">
                <a href="{{ route('diensten') }}#contact" class="btn btn-primary">Plaats een zoekopdracht</a>
                <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline">Bel ons</a>
            </div>
        </div>
    </section>
</x-layouts.public>
