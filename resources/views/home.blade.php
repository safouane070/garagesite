@php
    ['rating' => $rating, 'count' => $reviewCount] = \App\Support\Reviews::summary();
    $stock = $stockIndex->count();
    $heroBrands = $stockIndex->countBy(0)->sortKeys();
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

                {{-- Direct zoeken, zoals elke dealer in de regio het bovenaan zet. De knop telt live
                     mee; zonder JS werkt het formulier gewoon als link naar het gefilterde aanbod. --}}
                <form action="{{ route('cars.index') }}" method="get"
                      x-data="{ brand: '', max: '', cars: @js($stockIndex),
                                get n() { return this.cars.filter(([b, p]) => (!this.brand || b === this.brand) && (!this.max || p <= +this.max)).length } }"
                      @submit="[...$el.elements].forEach(f => { if (f.name && !f.value) f.disabled = true })"
                      class="mt-7 grid max-w-xl grid-cols-2 gap-2 rounded-[4px] border border-cream/15 bg-ink/70 p-2 backdrop-blur-md sm:mt-9 sm:grid-cols-[1fr_1fr_auto]"
                      style="text-shadow: none">
                    <label class="sr-only" for="hero-brand">Merk</label>
                    <select id="hero-brand" name="brand" x-model="brand"
                            class="rounded-[3px] border-0 bg-graphite-800 py-2.5 pl-3 pr-8 text-sm text-cream focus:ring-2 focus:ring-brass-400">
                        <option value="">Alle merken</option>
                        @foreach ($heroBrands as $b => $count)
                            <option value="{{ $b }}">{{ $b }} ({{ $count }})</option>
                        @endforeach
                    </select>
                    <label class="sr-only" for="hero-max">Maximale prijs</label>
                    <select id="hero-max" name="price_max" x-model="max"
                            class="rounded-[3px] border-0 bg-graphite-800 py-2.5 pl-3 pr-8 text-sm text-cream focus:ring-2 focus:ring-brass-400">
                        <option value="">Elke prijs</option>
                        @foreach ([15000, 20000, 25000, 30000, 40000, 50000, 75000] as $max)
                            <option value="{{ $max }}">Tot € {{ number_format($max, 0, ',', '.') }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="btn btn-primary col-span-2 sm:col-span-1">
                        Toon <span class="tabular" x-text="n">{{ $stock }}</span> auto's <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                </form>

                <a href="tel:{{ config('brand.contact.phone_href') }}"
                   class="mt-4 inline-flex items-center gap-2 text-sm font-medium text-cream/80 transition-colors hover:text-brass-300">
                    <x-icon name="phone" class="h-4 w-4" /> Liever bellen? {{ config('brand.contact.phone') }}
                </a>
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
        {{-- Index-rijen: titel, uitleg en pijl op één lijn (telefoon: uitleg onder de titel).
             Vier kolommen naast elkaar lieten veel leegte en verweesde pijlen achter. --}}
        <ul class="mt-8 border-t border-hairline">
            @foreach ($teaser as $s)
                <li class="border-b border-hairline">
                    <a href="{{ route('diensten') }}"
                       class="group -mx-3 grid grid-cols-[1fr_auto] items-center gap-x-6 gap-y-1 rounded-[3px] px-3 py-5 transition-colors duration-150 ease-out hover:bg-cream/[0.03] active:bg-cream/[0.05] sm:py-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,2fr)_auto] lg:gap-x-10">
                        <span class="font-display text-lg font-semibold text-cream sm:text-xl">{{ $s['t'] }}</span>
                        <span class="col-start-1 text-sm leading-relaxed text-cream/65 lg:col-start-2 lg:row-start-1 lg:text-base">{{ $s['d'] }}</span>
                        <x-icon name="arrow-right" class="col-start-2 row-span-2 row-start-1 h-5 w-5 text-cream/40 transition duration-200 ease-out group-hover:translate-x-1 group-hover:text-brass-300 lg:col-start-3 lg:row-span-1" />
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
