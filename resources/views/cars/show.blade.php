@php
    $images = $car->images;
    // Volledige specificatielijst: kern + extra (JSON).
    $coreSpecs = [
        'Bouwjaar' => $car->year,
        'Kilometerstand' => $car->formattedMileage(),
        'Brandstof' => $car->fuel_type,
        'Transmissie' => $car->transmission,
        'Carrosserie' => $car->body_type,
        'Kleur' => str_ireplace(' of ', ' / ', (string) $car->color),
    ];
    $extraSpecs = [];
    foreach (($car->specs ?? []) as $key => $value) {
        if ($value === null || $value === '') continue;
        // Nederlandse notatie: 25,6 i.p.v. 25.6.
        if (is_numeric($value) && str_contains((string) $value, '.')) $value = str_replace('.', ',', (string) $value);
        $extraSpecs[\App\Models\Car::SPEC_FIELDS[$key] ?? ucfirst(str_replace('_', ' ', $key))] = $value;
    }

    // Per-auto meta-description: échte specs i.p.v. één generieke zin.
    $metaDescription = trim(sprintf(
        '%s uit %s · %s · %s · %s. Te koop bij %s voor %s.',
        $car->title(), $car->year, $car->formattedMileage(),
        $car->fuel_type, $car->transmission, config('app.name'), $car->formattedPrice()
    ));
    $ogImage = $car->primaryImage?->url();
    $forSale = $car->status !== \App\Enums\CarStatus::Sold;
    $whatsappText = 'Hallo, ik heb interesse in de ' . $car->title() . ' (' . $car->formattedPrice() . '): ' . route('cars.show', $car);

    $vehicleLd = [
        '@context' => 'https://schema.org',
        '@type' => 'Vehicle',
        'name' => $car->title(),
        'brand' => ['@type' => 'Brand', 'name' => $car->brand],
        'model' => $car->model,
        'vehicleModelDate' => (string) $car->year,
        'productionDate' => (string) $car->year,
        'color' => $car->color,
        'bodyType' => $car->body_type,
        'fuelType' => $car->fuel_type,
        'vehicleTransmission' => $car->transmission,
        'mileageFromOdometer' => ['@type' => 'QuantitativeValue', 'value' => $car->mileage, 'unitCode' => 'KMT'],
        'image' => $images->map->url()->values(),
        'description' => \App\Support\CarDescription::display($car),
        'offers' => [
            '@type' => 'Offer',
            'price' => number_format((float) $car->price, 2, '.', ''),
            'priceCurrency' => 'EUR',
            'availability' => $car->status === \App\Enums\CarStatus::Sold
                ? 'https://schema.org/SoldOut'
                : 'https://schema.org/InStock',
            'url' => route('cars.show', $car),
        ],
    ];
@endphp

<x-layouts.public :title="$car->title()" :description="$metaDescription" :og-image="$ogImage"
    :whatsapp-text="$whatsappText" :action-bar="$forSale">
    @push('head')
        <script type="application/ld+json">
            {!! json_encode($vehicleLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endpush

    {{-- Kruimelpad --}}
    <div class="container-x pt-6">
        <nav class="flex items-center gap-2 font-mono text-xs uppercase tracking-wider text-cream/70" aria-label="Kruimelpad">
            <a href="{{ route('home') }}" class="transition hover:text-brass-300">Home</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <a href="{{ route('cars.index') }}" class="transition hover:text-brass-300">Aanbod</a>
            {{-- De autonaam staat er direct onder als h1; op een telefoon liep dit over twee regels. --}}
            <x-icon name="chevron-right" class="h-3 w-3 max-sm:hidden" />
            <span class="text-cream/70 max-sm:hidden">{{ $car->title() }}</span>
        </nav>
    </div>

    <div class="container-x grid gap-10 py-8 lg:grid-cols-12 lg:gap-12">
        {{-- ═══ Galerij ═══ --}}
        {{-- min-w-0: anders rekt de fotostrook (tientallen miniaturen) de gridkolom op mobiel op. --}}
        <div class="min-w-0 lg:col-span-7"
             x-data="gallery({ images: @js($images->map->url()->values()), srcsets: @js($images->map->srcset()->values()) })">
            <div class="relative aspect-[16/10] overflow-hidden rounded-[4px] border border-hairline bg-graphite-800 focus:outline-none focus-visible:ring-2"
                 tabindex="0" role="group" aria-roledescription="carrousel" aria-label="Fotogalerij (pijltjestoetsen of vegen)"
                 @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()"
                 @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
                @if ($images->isNotEmpty())
                    {{-- Eerste foto staat al in de HTML: zichtbaar zonder JS en direct
                         vindbaar voor de browser (snellere eerste weergave). --}}
                    {{-- srcset: een telefoon laadt de 1024 px-versie i.p.v. de 2000 px-foto. --}}
                    <img src="{{ $images->first()->url() }}" alt="{{ $car->title() }} · foto 1"
                         @if ($images->first()->srcset()) srcset="{{ $images->first()->srcset() }}" @endif
                         sizes="(min-width: 1024px) 58vw, calc(100vw - 2rem)"
                         :src="current" :srcset="srcsets[i] || ''" :alt="{{ \Illuminate\Support\Js::from($car->title() . ' · foto ') }} + (i + 1)"
                         fetchpriority="high" decoding="async" @click="open()"
                         class="photo-fx h-full w-full cursor-zoom-in object-cover">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-scrim/30 to-transparent"></div>

                    {{-- Schermvullend bekijken (ook met toetsenbord) --}}
                    <button type="button" @click="open()" aria-label="Foto's schermvullend bekijken"
                            class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-[3px] bg-scrim/70 px-3 py-2 font-mono text-[0.7rem] uppercase tracking-wider text-onscrim backdrop-blur-md transition hover:bg-scrim/90">
                        <x-icon name="maximize" class="h-4 w-4" /> Vergroot
                    </button>

                    {{-- Teller --}}
                    <div class="absolute right-4 top-4 rounded-[3px] bg-scrim/70 px-2.5 py-1 font-mono text-xs text-onscrim/80 backdrop-blur-md">
                        <span x-text="String(i + 1).padStart(2, '0')"></span> / <span x-text="String(images.length).padStart(2, '0')"></span>
                    </div>

                    {{-- Navigatie --}}
                    <template x-if="images.length > 1">
                        <div>
                            <button @click="prev()" type="button" aria-label="Vorige foto"
                                    class="group absolute left-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-hairline bg-scrim/60 text-onscrim backdrop-blur-md transition hover:border-brass-500/60 hover:bg-scrim/80">
                                <x-icon name="chevron-left" class="h-5 w-5" />
                            </button>
                            <button @click="next()" type="button" aria-label="Volgende foto"
                                    class="group absolute right-3 top-1/2 flex h-10 w-10 -translate-y-1/2 items-center justify-center rounded-full border border-hairline bg-scrim/60 text-onscrim backdrop-blur-md transition hover:border-brass-500/60 hover:bg-scrim/80">
                                <x-icon name="chevron-right" class="h-5 w-5" />
                            </button>
                        </div>
                    </template>
                @else
                    <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-cream/25">
                        <x-icon name="car" class="h-10 w-10" /><span class="font-mono text-xs uppercase tracking-widest">Geen foto</span>
                    </div>
                @endif
            </div>

            {{-- Lightbox: naar <body> verplaatst zodat de rest "inert" kan worden --}}
            @if ($images->isNotEmpty())
                <template x-teleport="body">
                    <div x-show="full" x-cloak x-transition.opacity.duration.200ms
                         role="dialog" aria-modal="true" aria-label="Foto's van {{ $car->title() }}"
                         class="fixed inset-0 z-[60] flex flex-col bg-black/95"
                         @keydown.escape.window="close()"
                         @keydown.arrow-left.window="full && prev()" @keydown.arrow-right.window="full && next()"
                         @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
                        <div class="flex items-center justify-between px-4 py-3 font-mono text-xs text-white/75">
                            <span><span x-text="i + 1"></span> / <span x-text="images.length"></span> · {{ $car->title() }}</span>
                            <button type="button" x-ref="closeFull" @click="close()" aria-label="Sluiten"
                                    class="flex h-11 w-11 items-center justify-center rounded-full text-white transition hover:bg-white/10">
                                <x-icon name="x" class="h-6 w-6" />
                            </button>
                        </div>
                        <div class="relative flex min-h-0 flex-1 items-center justify-center px-2 pb-6" @click.self="close()">
                            <img :src="current" :alt="{{ \Illuminate\Support\Js::from($car->title() . ' · foto ') }} + (i + 1)"
                                 class="max-h-full max-w-full select-none object-contain">
                            <template x-if="images.length > 1">
                                <div>
                                    <button type="button" @click="prev()" aria-label="Vorige foto"
                                            class="absolute left-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                                        <x-icon name="chevron-left" class="h-6 w-6" />
                                    </button>
                                    <button type="button" @click="next()" aria-label="Volgende foto"
                                            class="absolute right-3 top-1/2 flex h-12 w-12 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20">
                                        <x-icon name="chevron-right" class="h-6 w-6" />
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            @endif

            {{-- Thumbnails --}}
            @if ($images->count() > 1)
                {{-- Eén scrollbare rij: 30+ foto's duwen de specs anders ver naar beneden. --}}
                <div x-ref="thumbs" class="scroll-thin relative mt-3 flex snap-x gap-2 overflow-x-auto pb-2 sm:gap-3">
                    @foreach ($images as $index => $image)
                        <button type="button" @click="go({{ $index }})"
                                :class="i === {{ $index }} ? 'border-brass-500 ring-1 ring-brass-500' : 'border-hairline opacity-70 hover:opacity-100'"
                                class="aspect-[4/3] w-20 shrink-0 snap-start overflow-hidden rounded-[3px] border transition sm:w-24"
                                aria-label="Toon foto {{ $index + 1 }}">
                            <img src="{{ $image->xsUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══ Infopaneel ═══ --}}
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24">
                <h1 class="font-display text-3xl font-bold leading-tight tracking-tight text-cream sm:text-4xl">
                    {{ $car->brand }} {{ $car->model }}
                </h1>
                @if ($car->cleanVariant())
                    <p class="mt-1 text-lg text-cream/65">{{ $car->cleanVariant() }}</p>
                @endif

                {{-- Kernspecs als één regel (stond in een omkaderd 2×2-raster). --}}
                <dl class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 font-mono text-sm text-cream/70 tabular">
                    @foreach ([
                        'Bouwjaar' => $car->year,
                        'Kilometerstand' => $car->formattedMileage(),
                        'Brandstof' => $car->fuel_type,
                        'Transmissie' => $car->transmission,
                    ] as $label => $value)
                        <div class="flex items-center gap-2">
                            @unless ($loop->first)<span class="text-cream/25" aria-hidden="true">·</span>@endunless
                            <dt class="sr-only">{{ $label }}</dt>
                            <dd>{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>

                {{-- Prijs: groot en wit; alleen een status tonen als die afwijkt. --}}
                <div class="mt-6 flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="font-display text-4xl font-bold tracking-tight text-white tabular sm:text-5xl">
                        {{ $car->formattedPrice() }}
                    </span>
                    @if ($car->status !== \App\Enums\CarStatus::Available)
                        <x-status-badge :status="$car->status" />
                    @endif
                    @if ($car->isVatDeductible())
                        <span class="rounded-[3px] border border-hairline px-2 py-1 text-xs font-medium text-cream/80">BTW aftrekbaar</span>
                    @endif
                </div>
                @if ($forSale && ($monthly = $car->monthlyFrom()))
                    @php
                        $lease = config('brand.finance.lease');
                    @endphp
                    <p class="mt-2 text-sm text-cream/70">
                        <a href="{{ route('financial-lease') }}" class="text-cream underline decoration-cream/25 underline-offset-4 transition-colors hover:text-brass-300">Financial lease</a>
                        vanaf <span class="font-semibold text-cream tabular">€ {{ number_format($monthly, 0, ',', '.') }}</span> p/m
                    </p>
                    <p class="mt-1 text-xs leading-relaxed text-cream/50">
                        Indicatie: {{ $lease['months'] }} mnd, {{ (int) ($lease['down'] * 100) }}% aanbetaling,
                        {{ (int) ($lease['balloon'] * 100) }}% slottermijn, {{ str_replace('.', ',', (string) $lease['rate']) }}% rente. Alleen zakelijk.
                    </p>
                @elseif ($forSale)
                    <a href="{{ route('financial-lease') }}" class="mt-2 inline-flex text-sm text-cream/65 underline decoration-cream/25 underline-offset-4 transition hover:text-brass-300">
                        Ook mogelijk via financial lease
                    </a>
                @endif

                {{-- CTA: één hoofdactie; bellen/WhatsApp ernaast. Bezichtiging en inruil
                     kies je in het formulier (onderwerp), dat hoeft geen vier knoppen. --}}
                @if ($forSale)
                    {{-- Echte link naar het formulier (werkt ook zonder JS); met JS kiest de
                         knop meteen het onderwerp en zet de cursor in het naamveld. --}}
                    <div id="snelacties" class="mt-7 grid grid-cols-2 gap-3" x-data>
                        <a href="#contact" class="btn btn-primary col-span-2 py-3.5 text-base"
                           @click="$store.lead.type = 'proefrit'; $nextTick(() => document.getElementById('lead-name')?.focus({ preventScroll: true }))">
                            Proefrit aanvragen
                        </a>
                        <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline">
                            <x-icon name="phone" class="h-4 w-4" /> Bel ons
                        </a>
                        <a href="https://wa.me/{{ preg_replace('/\D/', '', (string) config('brand.contact.whatsapp')) }}?text={{ rawurlencode($whatsappText) }}"
                           target="_blank" rel="noopener" class="btn btn-outline">
                            WhatsApp
                        </a>
                    </div>
                @else
                    <p class="mt-6 text-sm text-cream/70">
                        Deze auto is verkocht. Bekijk ons <a href="{{ route('cars.index') }}" class="text-brass-300 underline underline-offset-4">actuele aanbod</a>.
                    </p>
                @endif
            </div>
        </div>
    </div>

    {{-- ═══ Beschrijving + volledige specs ═══ --}}
    <div class="container-x grid gap-12 border-t border-hairline py-14 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="font-display text-2xl font-bold text-cream">Over deze {{ $car->brand }}</h2>
            {{-- whitespace-pre-line: alinea's die de beheerder intypt blijven alinea's. --}}
            <p class="mt-4 whitespace-pre-line leading-relaxed text-cream/70">{{ \App\Support\CarDescription::display($car) }}</p>
        </div>

        <div class="lg:col-span-5">
            <h2 class="font-display text-2xl font-bold text-cream">Specificaties</h2>
            <dl class="mt-4 divide-y divide-hairline border-y border-hairline">
                @foreach ($coreSpecs as $label => $value)
                    @if ($value)
                        <div class="flex items-center justify-between py-2.5">
                            <dt class="text-sm text-cream/65">{{ $label }}</dt>
                            <dd class="font-mono text-sm text-cream tabular">{{ $value }}</dd>
                        </div>
                    @endif
                @endforeach
                @foreach ($extraSpecs as $label => $value)
                    <div class="flex items-center justify-between py-2.5">
                        <dt class="text-sm text-cream/65">{{ $label }}</dt>
                        <dd class="font-mono text-sm text-cream tabular">{{ $value }}</dd>
                    </div>
                @endforeach
            </dl>
        </div>
    </div>

    {{-- ═══ Uitrusting + aflevering ═══ --}}
    <div class="container-x border-t border-hairline py-14">
        <div class="grid gap-12 lg:grid-cols-12">
            @php $hasOptions = ! empty($car->options); @endphp

            @if ($hasOptions)
                {{-- Tot ±60 opties: eerst 12, de rest op verzoek (anders schermen vol vinkjes vóór het formulier). --}}
                <div class="lg:col-span-8" x-data="{ all: false }">
                    <h2 class="font-display text-2xl font-bold text-white">Uitrusting</h2>
                    <div class="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($car->options as $opt)
                            <div class="flex items-start gap-2 text-sm text-cream/80" @if ($loop->index >= 12) x-show="all" x-cloak @endif>
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brass-500/80" />
                                <span>{{ $opt }}</span>
                            </div>
                        @endforeach
                    </div>
                    @if (count($car->options) > 12)
                        <button @click="all = !all" type="button" class="btn btn-outline mt-6 w-full sm:w-auto" :aria-expanded="all">
                            <span x-text="all ? 'Toon minder' : 'Toon alle {{ count($car->options) }} opties'">Toon alle {{ count($car->options) }} opties</span>
                            <x-icon name="chevron-down" class="h-4 w-4 transition-transform duration-200" x-bind:class="all && 'rotate-180'" />
                        </button>
                    @endif
                </div>
            @endif

            <aside class="{{ $hasOptions ? 'lg:col-span-4' : 'lg:col-span-6 lg:col-start-4' }}">
                <div>
                    <h3 class="font-display text-lg font-bold text-white">Inbegrepen bij aflevering</h3>
                    <ul class="mt-4 space-y-3 text-sm text-cream/80">
                        @foreach ([
                            'BOVAG-garantie (' . config('brand.trust.warranty_months') . ' maanden)',
                            'Onderhoudsbeurt vóór aflevering',
                            'Nieuwe APK',
                            'Geen afleverkosten',
                            'Inruil & financiering mogelijk',
                        ] as $item)
                            <li class="flex items-start gap-2.5">
                                <x-icon name="shield-check" class="mt-0.5 h-4 w-4 shrink-0 text-brass-500/80" />
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </aside>
        </div>
    </div>

    {{-- ═══ Interesseformulier ═══ --}}
    <div class="container-x border-t border-hairline py-14">
        <div class="mx-auto max-w-2xl">
            <x-lead-form :car="$car" type="bezichtiging"
                title="Interesse in deze {{ $car->brand }}?"
                intro="Plan een bezichtiging of stel je vraag. We reageren meestal binnen één werkdag." />
        </div>
    </div>

    {{-- ═══ Vergelijkbaar ═══ --}}
    @if ($related->isNotEmpty())
        <section class="container-x border-t border-hairline py-14">
            <h2 class="font-display text-2xl font-bold tracking-tight text-cream sm:text-3xl">Misschien ook interessant</h2>
            {{-- Telefoon: swipe-rail, zoals op de homepage. --}}
            <div class="-mx-5 mt-8 flex snap-x snap-mandatory scroll-px-5 gap-4 overflow-x-auto px-5 pb-4 scroll-thin sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-x-6 sm:gap-y-10 sm:overflow-visible sm:px-0 sm:pb-0 lg:grid-cols-3">
                @foreach ($related as $item)
                    <div class="w-[280px] shrink-0 snap-start sm:w-auto">
                        <x-car-card :car="$item" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ═══ Vaste actiebalk (mobiel) ═══
         Komt in beeld zodra de snelacties bovenin weggescrold zijn, en gaat weg bij het
         formulier (daar staan de acties al). Vervangt op mobiel de zwevende WhatsApp-knop. --}}
    @if ($forSale)
        <div x-data="{ show: false }"
             x-init="
                const ctas = document.getElementById('snelacties'), form = document.getElementById('contact');
                let past = false, atForm = false;
                const update = () => show = past && !atForm;
                // Opgerekte observatiegebieden: zo is elke passage een overgang, ook bij een
                // sprong of snelle veeg (een gewoon gebied mist die als het element erover springt).
                // past: de snelacties zijn boven het scherm verdwenen.
                new IntersectionObserver(([e]) => { past = !e.isIntersecting; update(); },
                    { rootMargin: '0px 0px 99999px 0px' }).observe(ctas);
                // atForm: het formulier is in beeld gekomen of al gepasseerd.
                if (form) new IntersectionObserver(([e]) => { atForm = e.isIntersecting; update(); },
                    { rootMargin: '99999px 0px 0px 0px' }).observe(form);
             "
             :class="show ? 'translate-y-0' : 'translate-y-full'"
             :inert="!show" x-cloak
             class="fixed inset-x-0 bottom-0 z-40 border-t border-hairline bg-ink/90 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg transition-transform duration-200 ease-out motion-reduce:transition-none lg:hidden">
            <div class="container-x flex items-center gap-2 py-3">
                <div class="min-w-0 flex-1">
                    <p class="truncate text-xs text-cream/65">{{ $car->brand }} {{ $car->model }}</p>
                    <p class="font-display text-xl font-bold leading-tight text-white tabular">{{ $car->formattedPrice() }}</p>
                </div>
                <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline h-11 w-11 shrink-0 px-0" aria-label="Bel ons">
                    <x-icon name="phone" class="h-5 w-5" />
                </a>
                <x-whatsapp-button :text="$whatsappText" class="!h-11 !w-11 shrink-0 !rounded-[3px] border border-hairline" />
                <a href="#contact" class="btn btn-primary h-11 shrink-0"
                   @click="$store.lead.type = 'proefrit'; $nextTick(() => document.getElementById('lead-name')?.focus({ preventScroll: true }))">
                    Proefrit
                </a>
            </div>
        </div>
    @endif
</x-layouts.public>
