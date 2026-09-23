<x-layouts.public title="Aanbod" :description="'Het volledige aanbod geselecteerde occasions van ' . config('app.name') . ' · filter op merk, brandstof, prijs en bouwjaar.'">
<div x-data="carCatalog({
        priceMin: {{ $priceMin }}, priceMax: {{ $priceMax }},
        yearMin: {{ $yearMin }}, yearMax: {{ $yearMax }},
        brand: @js($initialBrand), fuel: @js($initialFuel), body: @js($initialBody),
        total: {{ $cars->count() }}
     })" x-init="init()">

    {{-- Paginakop --}}
    <section class="border-b border-hairline">
        <div class="container-x py-12 lg:py-16">
            <h1 class="max-w-3xl font-display text-4xl font-bold uppercase leading-[1.02] tracking-tight text-cream sm:text-5xl">
                Ons volledige aanbod
            </h1>
            <p class="mt-5 max-w-xl leading-relaxed text-cream/70">
                Elke wagen met BOVAG-garantie en een geldige keuring. Geen afleverkosten,
                inruil en financiering mogelijk. Filter op wat voor jou telt.
            </p>

            {{-- Live telling als fijne meta-regel, geen los "hero-getal" --}}
            <div class="mt-8 flex items-center gap-3 font-mono text-xs uppercase tracking-[0.15em] text-cream/70">
                <span class="inline-flex items-baseline gap-1.5">
                    <span class="text-lg font-bold text-cream tabular" x-text="count"></span>
                    <span x-text="count === 1 ? 'wagen' : 'wagens'"></span>
                </span>
                <span class="h-1 w-1 rounded-full bg-brass-500"></span>
                <span x-show="!hasActiveFilters">op voorraad</span>
                <span x-show="hasActiveFilters" x-cloak class="text-brass-300">gefilterd</span>
            </div>
        </div>
    </section>

    {{-- Mobiele actiebalk (sticky) --}}
    <div class="sticky top-[4.5rem] z-30 border-b border-hairline bg-ink/85 backdrop-blur-lg lg:hidden">
        <div class="container-x flex items-center gap-3 py-3">
            <button @click="drawerOpen=true" type="button" class="btn btn-outline flex-1">
                <x-icon name="sliders" class="h-4 w-4" /> Filters
                <span x-show="activeCount>0" x-text="activeCount"
                      class="ml-1 inline-flex h-5 min-w-5 items-center justify-center rounded-full bg-brass-500 px-1 text-[0.7rem] font-bold text-cream"></span>
            </button>
            <div class="flex-1">
                @include('cars.partials.sort-dropdown')
            </div>
        </div>
    </div>

    <div class="container-x grid gap-8 py-8 lg:grid-cols-[260px_1fr] lg:gap-10">
        {{-- Sidebar (desktop) --}}
        <aside class="hidden lg:block">
            <div class="sticky top-[6rem]">
                <div class="mb-5 flex items-center justify-between">
                    <span class="flex items-center gap-2 font-mono text-xs uppercase tracking-wider text-cream/70">
                        <x-icon name="sliders" class="h-4 w-4 text-brass-500" /> Filters
                    </span>
                    <button @click="reset()" x-show="hasActiveFilters" x-cloak type="button"
                            class="font-mono text-[0.7rem] uppercase tracking-wider text-brass-300 hover:text-brass-200">
                        Wissen
                    </button>
                </div>
                @include('cars.partials.filters')
            </div>
        </aside>

        {{-- Resultaten --}}
        <div>
            {{-- Actieve filter-chips + sorteren (desktop) --}}
            <div class="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div class="flex flex-wrap items-center gap-2" x-show="chips.length" x-cloak>
                    <template x-for="chip in chips" :key="chip.k">
                        <button @click="removeChip(chip.k)" type="button"
                                class="group inline-flex items-center gap-1.5 rounded-[3px] border border-hairline bg-graphite-700 px-2.5 py-1 text-xs text-cream/70 transition hover:border-brass-500/50 hover:text-cream">
                            <span x-text="chip.label"></span>
                            <x-icon name="x" class="h-3 w-3 text-cream/70 transition group-hover:text-brass-300" />
                        </button>
                    </template>
                    <button @click="reset()" type="button" class="font-mono text-[0.7rem] uppercase tracking-wider text-cream/70 hover:text-brass-300">
                        Alles wissen
                    </button>
                </div>

                <div class="ml-auto hidden w-64 lg:block">
                    @include('cars.partials.sort-dropdown')
                </div>
            </div>

            {{-- Grid met alle kaarten (Alpine toont/sorteert client-side) --}}
            <div x-ref="grid" class="grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($cars as $car)
                    <x-car-card :car="$car" />
                @endforeach
            </div>

            {{-- Nette empty state --}}
            <div x-show="count === 0" x-cloak class="flex flex-col items-center justify-center border border-dashed border-hairline px-6 py-20 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-cream/70">
                    <x-icon name="search" class="h-6 w-6" />
                </span>
                <h3 class="mt-5 font-display text-xl font-semibold text-cream">Geen auto's gevonden</h3>
                <p class="mt-2 max-w-sm text-sm text-cream/65">
                    Er zijn geen auto's die aan deze filters voldoen. Probeer de prijs- of
                    bouwjaargrenzen te verruimen, of wis een filter.
                </p>
                <button @click="reset()" type="button" class="btn btn-primary mt-6">Filters wissen</button>
            </div>
        </div>
    </div>

    {{-- Mobiele filter-drawer (bottom sheet) --}}
    <div x-show="drawerOpen" x-cloak class="fixed inset-0 z-50 lg:hidden" @keydown.escape.window="drawerOpen=false">
        <div x-show="drawerOpen" x-transition:enter="transition-opacity ease-out duration-200"
             x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
             x-transition:leave="transition-opacity ease-in duration-150"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             class="absolute inset-0 bg-scrim/55 backdrop-blur-sm" @click="drawerOpen=false"></div>

        <div x-show="drawerOpen"
             x-transition:enter="transition-transform ease-premium duration-300"
             x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
             x-transition:leave="transition-transform ease-in duration-200"
             x-transition:leave-start="translate-y-0" x-transition:leave-end="translate-y-full"
             class="absolute inset-x-0 bottom-0 flex max-h-[88vh] flex-col rounded-t-2xl border-t border-hairline bg-graphite-800">
            <div class="flex items-center justify-between border-b border-hairline px-5 py-4">
                <span class="flex items-center gap-2 font-mono text-xs uppercase tracking-wider text-cream/80">
                    <x-icon name="sliders" class="h-4 w-4 text-brass-500" /> Filters
                </span>
                <button @click="drawerOpen=false" type="button" class="btn btn-ghost -mr-2"><x-icon name="x" class="h-5 w-5" /></button>
            </div>
            <div class="overflow-y-auto px-5 py-6">
                @include('cars.partials.filters')
            </div>
            <div class="flex items-center gap-3 border-t border-hairline px-5 py-4">
                <button @click="reset()" type="button" class="btn btn-outline flex-1" x-show="hasActiveFilters">Wissen</button>
                <button @click="drawerOpen=false" type="button" class="btn btn-primary flex-1">
                    Toon <span x-text="count"></span> resultaten
                </button>
            </div>
        </div>
    </div>
</div>
</x-layouts.public>
