@php
    ['rating' => $rating, 'count' => $reviewCount] = \App\Support\Reviews::summary();
    $stock = \App\Models\Car::where('status', '!=', \App\Enums\CarStatus::Sold->value)->count();
@endphp

<x-layouts.public>
    {{-- ═══════════ HERO · echte showroomfoto met tekst eroverheen ═══════════ --}}
    <section class="relative -mt-[4.5rem] flex min-h-hero flex-col overflow-hidden bg-ink pt-[4.5rem] sm:flex-row sm:items-end">
        {{-- Echte foto van het wagenpark van de zaak --}}
        {{-- Staand scherm (telefoon): de middenuitsnede. object-cover toont daar toch alleen het
             midden van de vierkante foto; zelfde beeld, ±35% minder bytes voor de grootste afbeelding.
             Telefoon: de foto is een eigen strook boven de tekst, op breedte geschaald. Als
             achtergrond viel de rij auto's (45–67% van de foto) precies achter de kop en werden
             de buitenste auto's afgesneden. 62% houdt de rij in beeld van ±190px (iPhone in
             Safari) tot ±350px strookhoogte. Vanaf sm: weer de volle achtergrondfoto. --}}
        <div class="relative min-h-[12rem] flex-1 [mask-image:linear-gradient(to_bottom,#000_70%,transparent)] sm:absolute sm:inset-0 sm:[mask-image:none]">
            <picture>
                <source media="(max-aspect-ratio: 5/8)" srcset="{{ asset('images/hero-showroom-portrait.webp') }}" width="1120" height="1600">
                <img src="{{ asset('images/hero-showroom.webp') }}" alt="Wagenpark van {{ config('app.name') }}"
                     width="1600" height="1600" fetchpriority="high" decoding="async"
                     class="absolute inset-0 h-full w-full object-cover object-[50%_62%] sm:object-center">
            </picture>
            {{-- Lichte overlay: alleen onderin/links donker voor tekst, boven blijft de foto helder --}}
            <div class="pointer-events-none absolute inset-0 hidden bg-gradient-to-t from-ink via-ink/40 to-ink/5 sm:block"></div>
            <div class="pointer-events-none absolute inset-0 hidden bg-gradient-to-r from-ink/55 via-transparent to-transparent sm:block"></div>
        </div>

        <div class="container-x relative pb-12 pt-2 sm:py-16">
            <div class="max-w-2xl" style="text-shadow: 0 2px 24px rgba(0,0,0,0.45)">
                <h1 class="font-display text-[2.6rem] font-bold uppercase leading-[0.95] tracking-tight text-white sm:text-6xl lg:text-[4.6rem]">
                    Dé Duitse occasions<br>van Rijswijk
                </h1>

                {{-- USP's, zoals op de originele hero (kort, geen lange alinea) --}}
                <ul class="mt-6 flex flex-wrap gap-x-6 gap-y-2 sm:mt-8">
                    @foreach (['BOVAG-garantie', 'Geen afleverkosten', 'VAG-specialist'] as $usp)
                        <li class="inline-flex items-center gap-2 text-sm font-medium text-cream">
                            <x-icon name="check" class="h-4 w-4 text-brass-400" /> {{ $usp }}
                        </li>
                    @endforeach
                </ul>

                <div class="mt-7 flex flex-wrap items-center gap-3 sm:mt-9 sm:gap-4">
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

    {{-- ═══════════ BEWIJS · één regel i.p.v. cijfertegels + vertrouwensband ═══════════
         De beloftes (BOVAG, afleverkosten) staan al in de hero; hier alleen wat de hero
         niet zegt: de echte Google-score en de echte voorraad. --}}
    <div class="container-x flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-hairline py-5 text-sm text-cream/70">
        <a href="{{ config('brand.reviews.url') }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 transition hover:text-cream">
            <x-icon name="star" class="h-4 w-4 text-brass-400" />
            <span class="font-semibold text-cream tabular">{{ str_replace('.', ',', (string) $rating) }}</span>
            · {{ $reviewCount }} Google-reviews
        </a>
        <span class="text-cream/25" aria-hidden="true">/</span>
        <a href="{{ route('cars.index') }}" class="transition hover:text-cream">
            <span class="font-semibold text-cream tabular">{{ $stock }}</span> auto's op voorraad
        </a>
    </div>

    {{-- ═══════════ AANRADERS ═══════════ --}}
    @if ($featured->count())
        <section class="container-x py-16 lg:py-24">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                    Onze aanraders
                </h2>
                <a href="{{ route('cars.index') }}" class="group inline-flex items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300">
                    Volledig aanbod
                    <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </a>
            </div>

            {{-- Telefoon: swipe-rail (zoals "Net binnen") i.p.v. zes schermhoge kaarten onder elkaar. --}}
            <div class="-mx-5 mt-8 flex snap-x snap-mandatory scroll-px-5 gap-4 overflow-x-auto px-5 pb-4 scroll-thin sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
                @foreach ($featured as $car)
                    <div class="w-[280px] shrink-0 snap-start sm:w-auto">
                        <x-car-card :car="$car" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══════════ NET BINNEN · scroll-rail ═══════════ --}}
    <section class="py-16 lg:py-20">
        <div class="container-x flex items-end justify-between gap-4">
            <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">Net binnengereden</h2>
            <a href="{{ route('cars.index') }}" class="group hidden items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300 sm:inline-flex">
                Bekijk alles <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
            </a>
        </div>

        <div class="mt-8 flex snap-x snap-mandatory scroll-px-5 gap-4 scroll-thin overflow-x-auto px-5 pb-4 sm:scroll-px-8 sm:gap-6 sm:px-8">
            @foreach ($newest as $car)
                <div class="w-[280px] shrink-0 snap-start sm:w-[330px]">
                    <x-car-card :car="$car" />
                </div>
            @endforeach
            <a href="{{ route('cars.index') }}" class="flex w-[160px] shrink-0 snap-start flex-col items-center justify-center gap-3 text-cream/60 transition hover:text-brass-300">
                <span class="flex h-12 w-12 items-center justify-center rounded-full border border-hairline"><x-icon name="arrow-right" class="h-5 w-5" /></span>
                <span class="text-sm">Bekijk alles</span>
            </a>
        </div>
    </section>

    {{-- ═══════════ MET TROTS VERKOCHT ═══════════ --}}
    <x-home.sold :cars="$sold" />

    {{-- ═══════════ DIENSTEN · lijst, geen kaartjes ═══════════ --}}
    <section class="container-x py-16 lg:py-24">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                Alles rondom je auto
            </h2>
            <a href="{{ route('diensten') }}" class="group inline-flex items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300">
                Alle diensten <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
            </a>
        </div>

        @php
            $teaser = [
                ['t' => 'Inkoop', 'd' => 'Eerlijke dagprijs voor je auto, direct geregeld.'],
                ['t' => 'Verkoop', 'd' => 'Kwaliteitsoccasions met BOVAG-garantie, zonder afleverkosten.'],
                ['t' => 'Aankoop', 'd' => 'Zelf een auto op het oog? Wij begeleiden je bij de aankoop.'],
                ['t' => 'Zoekopdracht', 'd' => 'Niet gevonden? Wij zoeken gericht naar jouw droomauto.'],
            ];
        @endphp
        <ul class="mt-8 divide-y divide-hairline border-y border-hairline lg:grid lg:grid-cols-4 lg:divide-x lg:divide-y-0">
            @foreach ($teaser as $s)
                <li>
                    <a href="{{ route('diensten') }}" class="group flex items-start justify-between gap-6 py-5 transition active:bg-cream/[0.03] lg:h-full lg:flex-col lg:px-6 lg:py-8 lg:first:pl-0">
                        <span>
                            <span class="block font-display text-lg font-semibold text-cream">{{ $s['t'] }}</span>
                            <span class="mt-1 block text-sm leading-relaxed text-cream/65">{{ $s['d'] }}</span>
                        </span>
                        <x-icon name="arrow-right" class="mt-1 h-4 w-4 shrink-0 text-cream/40 transition group-hover:translate-x-0.5 group-hover:text-brass-300" />
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ═══════════ KLANTERVARINGEN ═══════════ --}}
    <x-home.reviews />

    {{-- ═══════════ CTA · volle band ═══════════ --}}
    <section class="border-y border-hairline bg-graphite-800">
        <div class="container-x grid items-center gap-8 py-14 lg:grid-cols-12 lg:py-20">
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
