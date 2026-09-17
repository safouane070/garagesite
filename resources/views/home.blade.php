<x-layouts.public>
    {{-- ═══════════ HERO · uitgeknipte wagen op donker ═══════════ --}}
    <section class="relative -mt-[4.5rem] overflow-hidden bg-ink">
        {{-- Diepte: verticaal verloop (donkerder naar de vloer toe) + neutrale
             spotlight achter de wagen (geen gekleurde glow). --}}
        <div class="pointer-events-none absolute inset-0 bg-gradient-to-b from-paper via-ink to-scrim"></div>
        <div class="pointer-events-none absolute right-[-5%] top-1/2 h-[85%] w-[65%] -translate-y-1/2 rounded-full bg-[radial-gradient(ellipse_at_center,rgba(242,238,228,0.10),transparent_68%)]"></div>

        <div class="container-x relative grid items-center gap-10 pb-16 pt-32 lg:min-h-[90vh] lg:grid-cols-12 lg:gap-4 lg:pb-20 lg:pt-36">
            {{-- Copy --}}
            <div class="min-w-0 max-w-xl lg:col-span-6">
                <p class="kicker flex items-center gap-3">
                    <span class="h-px w-10 bg-brass-500"></span> BOVAG-erkend autobedrijf · Rijswijk
                </p>
                <h1 class="mt-6 font-display text-[2.4rem] font-bold uppercase leading-[0.95] tracking-tight text-cream sm:text-6xl lg:text-[4.4rem]">
                    Dé Duitse occasions<br>van Rijswijk
                </h1>
                <p class="mt-7 max-w-xl text-lg leading-relaxed text-cream/80">
                    Zorgvuldig geselecteerde occasions, van Volkswagen en Audi tot premium
                    toppers van Mercedes, BMW en Porsche. BOVAG-garantie, geen afleverkosten,
                    inruil en financiering mogelijk.
                </p>
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

            {{-- Uitgeknipte wagen --}}
            @if ($hero)
                <div class="min-w-0 lg:col-span-6">
                    <div class="relative">
                        {{-- grondschaduw onder de wielen: ankert de wagen zodat 'ie
                             niet als losse sticker zweeft --}}
                        <div class="pointer-events-none absolute inset-x-[10%] bottom-[2%] h-[10%] rounded-[50%] bg-scrim/80 blur-2xl lg:inset-x-[18%] lg:-translate-x-[4%]"></div>
                        <img src="{{ $hero->cutoutUrl() }}" alt="{{ $hero->title() }}"
                             fetchpriority="high" loading="eager" decoding="async"
                             class="relative w-full drop-shadow-[0_30px_38px_rgba(0,0,0,0.55)] lg:w-[128%] lg:max-w-none lg:-translate-x-[4%]">
                    </div>
                    <a href="{{ route('cars.show', $hero) }}"
                       class="group mt-5 inline-flex items-center gap-3 text-sm text-cream/70 transition hover:text-brass-300 lg:mt-3 lg:pl-[8%]">
                        <span class="font-mono text-[0.6rem] uppercase tracking-[0.25em] text-brass-300">Uitgelicht</span>
                        <span class="font-display font-semibold text-cream">{{ $hero->shortTitle() }}</span>
                        <span class="tabular font-display font-bold text-brass-300">{{ $hero->formattedPrice() }}</span>
                        <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                    </a>
                </div>
            @endif
        </div>
    </section>

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

    {{-- ═══════════ CTA ═══════════ --}}
    <section class="container-x pb-24">
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
            <div class="flex gap-3 lg:col-span-4 lg:justify-end">
                <a href="{{ route('cars.index') }}" class="btn btn-primary">Doorzoek aanbod</a>
                <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline">Bel ons</a>
            </div>
        </div>
    </section>
</x-layouts.public>
