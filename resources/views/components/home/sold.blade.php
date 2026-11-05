@props(['cars'])

@if ($cars->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'border-y border-hairline bg-graphite-800/60']) }}>
        <div class="container-x py-12 lg:py-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                    Deze klanten waren je voor
                </h2>
                <a href="{{ route('cars.index') }}" class="group hidden items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300 sm:inline-flex">
                    Actueel aanbod <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </a>
            </div>

            {{-- Eén rij kleine kaarten: bewijs, geen tweede etalage van auto's die niet te koop zijn. --}}
            <div class="-mx-5 mt-8 flex snap-x snap-mandatory scroll-px-5 gap-4 overflow-x-auto px-5 pb-4 scroll-thin sm:mx-0 sm:grid sm:grid-cols-2 sm:gap-x-5 sm:gap-y-8 lg:grid-cols-4 sm:overflow-visible sm:px-0 sm:pb-0">
                @foreach ($cars as $car)
                    <div class="w-[220px] shrink-0 snap-start sm:w-auto">
                        <x-car-card :car="$car" />
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
