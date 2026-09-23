@php
    $images = $car->images;
    // Volledige specificatielijst: kern + extra (JSON).
    $coreSpecs = [
        'Bouwjaar' => $car->year,
        'Kilometerstand' => $car->formattedMileage(),
        'Brandstof' => $car->fuel_type,
        'Transmissie' => $car->transmission,
        'Carrosserie' => $car->body_type,
        'Kleur' => $car->color,
    ];
    $extraSpecs = [];
    foreach (($car->specs ?? []) as $key => $value) {
        if ($value === null || $value === '') continue;
        $extraSpecs[\App\Models\Car::SPEC_FIELDS[$key] ?? ucfirst(str_replace('_', ' ', $key))] = $value;
    }

    // Per-auto meta-description: échte specs i.p.v. één generieke zin.
    $metaDescription = trim(sprintf(
        '%s uit %s · %s · %s · %s. Te koop bij %s voor %s.',
        $car->title(), $car->year, $car->formattedMileage(),
        $car->fuel_type, $car->transmission, config('app.name'), $car->formattedPrice()
    ));
    $ogImage = $car->primaryImage?->url();

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
        'description' => $car->description ?: $metaDescription,
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
    :whatsapp-text="'Hallo, ik heb interesse in de ' . $car->title() . ' (' . $car->formattedPrice() . '): ' . route('cars.show', $car)">
    @push('head')
        <script type="application/ld+json">
            {!! json_encode($vehicleLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}
        </script>
    @endpush

    {{-- Kruimelpad --}}
    <div class="container-x pt-6">
        <nav class="flex items-center gap-2 font-mono text-[0.7rem] uppercase tracking-wider text-cream/70" aria-label="Kruimelpad">
            <a href="{{ route('home') }}" class="transition hover:text-brass-300">Home</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <a href="{{ route('cars.index') }}" class="transition hover:text-brass-300">Aanbod</a>
            <x-icon name="chevron-right" class="h-3 w-3" />
            <span class="text-cream/70">{{ $car->title() }}</span>
        </nav>
    </div>

    <div class="container-x grid gap-10 py-8 lg:grid-cols-12 lg:gap-12">
        {{-- ═══ Galerij ═══ --}}
        <div class="lg:col-span-7"
             x-data="gallery({ images: @js($images->map->url()->values()) })">
            <div class="relative aspect-[16/10] overflow-hidden rounded-[4px] border border-hairline bg-graphite-800 focus:outline-none focus-visible:ring-2"
                 tabindex="0" role="group" aria-roledescription="carrousel" aria-label="Fotogalerij (pijltjestoetsen of vegen)"
                 @keydown.arrow-left.prevent="prev()" @keydown.arrow-right.prevent="next()"
                 @touchstart.passive="touchStart($event)" @touchend.passive="touchEnd($event)">
                @if ($images->isNotEmpty())
                    {{-- Eerste foto staat al in de HTML: zichtbaar zonder JS en direct
                         vindbaar voor de browser (snellere eerste weergave). --}}
                    <img src="{{ $images->first()->url() }}" alt="{{ $car->title() }} · foto 1"
                         :src="current" :alt="{{ \Illuminate\Support\Js::from($car->title() . ' · foto ') }} + (i + 1)"
                         fetchpriority="high" decoding="async" @click="open()"
                         class="photo-fx h-full w-full cursor-zoom-in object-cover">
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-scrim/30 to-transparent"></div>

                    {{-- Schermvullend bekijken (ook met toetsenbord) --}}
                    <button type="button" @click="open()" aria-label="Foto's schermvullend bekijken"
                            class="absolute bottom-4 right-4 inline-flex items-center gap-2 rounded-[3px] bg-scrim/70 px-3 py-2 font-mono text-[0.7rem] uppercase tracking-wider text-onscrim backdrop-blur-md transition hover:bg-scrim/90">
                        <x-icon name="maximize" class="h-4 w-4" /> Vergroot
                    </button>

                    {{-- Statusbadge --}}
                    <div class="absolute left-4 top-4"><x-status-badge :status="$car->status" class="backdrop-blur-md" /></div>

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
                <div class="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-6">
                    @foreach ($images as $index => $image)
                        <button type="button" @click="go({{ $index }})"
                                :class="i === {{ $index }} ? 'border-brass-500 ring-1 ring-brass-500' : 'border-hairline opacity-70 hover:opacity-100'"
                                class="aspect-[4/3] overflow-hidden rounded-[3px] border transition"
                                aria-label="Toon foto {{ $index + 1 }}">
                            <img src="{{ $image->thumbUrl() }}" alt="" loading="lazy" class="h-full w-full object-cover">
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ═══ Infopaneel ═══ --}}
        <div class="lg:col-span-5">
            <div class="lg:sticky lg:top-24">
                <p class="kicker">{{ $car->body_type ?: 'Occasion' }} · {{ $car->year }}</p>
                <h1 class="mt-3 font-display text-3xl font-bold leading-tight tracking-tight text-cream sm:text-4xl">
                    {{ $car->brand }} {{ $car->model }}
                </h1>
                @if ($car->variant)
                    <p class="mt-1 text-lg text-cream/65">{{ $car->variant }}</p>
                @endif

                {{-- Prijs: ademt, in het wit zodat 'ie meteen opvalt --}}
                <div class="mt-6 border-y border-hairline py-5">
                    <div class="flex items-end justify-between">
                        <div>
                            <span class="kicker text-[0.6rem]">Vraagprijs</span>
                            <span class="mt-1 block font-display text-4xl font-bold tracking-tight text-white tabular sm:text-5xl">
                                {{ $car->formattedPrice() }}
                            </span>
                        </div>
                        <x-status-badge :status="$car->status" />
                    </div>

                    @if ($car->status !== \App\Enums\CarStatus::Sold)
                        <a href="{{ route('financial-lease') }}" class="mt-3 inline-flex items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300">
                            <x-icon name="repeat" class="h-4 w-4 text-brass-500/70" />
                            Ook mogelijk via <span class="font-semibold text-white">financial lease</span>
                        </a>
                    @endif
                </div>

                {{-- Kernspecs (highlight) --}}
                <dl class="mt-6 grid grid-cols-2 gap-px overflow-hidden rounded-[4px] border border-hairline bg-hairline">
                    @php
                        $highlights = [
                            ['icon' => 'calendar', 'label' => 'Bouwjaar', 'value' => $car->year],
                            ['icon' => 'gauge', 'label' => 'Kilometerstand', 'value' => $car->formattedMileage()],
                            ['icon' => 'fuel', 'label' => 'Brandstof', 'value' => $car->fuel_type],
                            ['icon' => 'gearbox', 'label' => 'Transmissie', 'value' => $car->transmission],
                        ];
                    @endphp
                    @foreach ($highlights as $h)
                        <div class="flex items-center gap-3 bg-graphite-800 p-4">
                            <x-icon name="{{ $h['icon'] }}" class="h-5 w-5 shrink-0 text-brass-500/70" />
                            <div class="min-w-0">
                                <dt class="font-mono text-[0.65rem] uppercase tracking-wider text-cream/70">{{ $h['label'] }}</dt>
                                <dd class="truncate text-sm font-medium text-white">{{ $h['value'] }}</dd>
                            </div>
                        </div>
                    @endforeach
                </dl>

                {{-- CTA: snelacties die het contactformulier-onderwerp kiezen --}}
                @if ($car->status !== \App\Enums\CarStatus::Sold)
                    {{-- Echte links naar het formulier (werkt ook zonder JS); met JS kiest de
                         knop meteen het onderwerp en zet de cursor in het naamveld. --}}
                    <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-2" x-data="{
                            pick(type) {
                                this.$store.lead.type = type;
                                this.$nextTick(() => document.getElementById('lead-name')?.focus({ preventScroll: true }));
                            },
                         }">
                        <a href="#contact" @click="pick('proefrit')" class="btn btn-primary">
                            <x-icon name="calendar" class="h-4 w-4" /> Proefrit aanvragen
                        </a>
                        <a href="#contact" @click="pick('bezichtiging')" class="btn btn-outline">
                            <x-icon name="check" class="h-4 w-4" /> Bezichtiging plannen
                        </a>
                        <a href="#contact" @click="pick('inruil')" class="btn btn-outline">
                            <x-icon name="repeat" class="h-4 w-4" /> Inruil bespreken
                        </a>
                        <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline">
                            <x-icon name="phone" class="h-4 w-4" /> Bel ons
                        </a>
                    </div>
                @else
                    <div class="mt-6 flex items-center gap-3 rounded-[4px] border border-hairline bg-graphite-800 p-4 text-sm text-cream/70">
                        <x-icon name="check" class="h-5 w-5 text-rose-400" />
                        Deze auto is verkocht. Bekijk ons <a href="{{ route('cars.index') }}" class="text-brass-300 underline-offset-2 hover:underline">actuele aanbod</a>.
                    </div>
                @endif

                {{-- Vertrouwensregel --}}
                <div class="mt-5 flex flex-wrap gap-x-5 gap-y-2 font-mono text-[0.7rem] uppercase tracking-wider text-cream/70">
                    <span class="inline-flex items-center gap-1.5"><x-icon name="shield-check" class="h-3.5 w-3.5 text-brass-500/70" /> BOVAG-garantie</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="file-text" class="h-3.5 w-3.5 text-brass-500/70" /> Volledige historiek</span>
                    <span class="inline-flex items-center gap-1.5"><x-icon name="repeat" class="h-3.5 w-3.5 text-brass-500/70" /> Inruil mogelijk</span>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══ Beschrijving + volledige specs ═══ --}}
    <div class="container-x grid gap-12 border-t border-hairline py-14 lg:grid-cols-12">
        <div class="lg:col-span-7">
            <h2 class="font-display text-2xl font-bold text-cream">Over deze {{ $car->brand }}</h2>
            {{-- whitespace-pre-line: alinea's die de beheerder intypt blijven alinea's. --}}
            <p class="mt-4 whitespace-pre-line leading-relaxed text-cream/70">{{ $car->description ?: 'Geen beschrijving beschikbaar.' }}</p>
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
                <div class="lg:col-span-8">
                    <h2 class="font-display text-2xl font-bold text-white">Uitrusting</h2>
                    <div class="mt-5 grid gap-x-6 gap-y-2.5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($car->options as $opt)
                            <div class="flex items-start gap-2 text-sm text-cream/80">
                                <x-icon name="check" class="mt-0.5 h-4 w-4 shrink-0 text-brass-500/80" />
                                <span>{{ $opt }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            <aside class="{{ $hasOptions ? 'lg:col-span-4' : 'lg:col-span-6 lg:col-start-4' }}">
                <div class="surface p-6">
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
            <div class="mb-8 flex items-end justify-between border-b border-hairline pb-6">
                <div>
                    <p class="kicker">Vergelijkbaar</p>
                    <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-cream sm:text-3xl">Misschien ook interessant</h2>
                </div>
            </div>
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($related as $item)
                    <x-car-card :car="$item" />
                @endforeach
            </div>
        </section>
    @endif
</x-layouts.public>
