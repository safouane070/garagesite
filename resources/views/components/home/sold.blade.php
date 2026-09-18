@props(['cars'])

@if ($cars->isNotEmpty())
    <section {{ $attributes->merge(['class' => 'border-y border-hairline bg-graphite-800/60']) }}>
        <div class="container-x py-16 lg:py-20">
            <div class="flex flex-wrap items-end justify-between gap-4 border-b border-hairline pb-6">
                <div>
                    <p class="kicker">Met trots verkocht</p>
                    <h2 class="mt-3 font-display text-3xl font-bold uppercase tracking-tight text-cream sm:text-4xl">
                        Deze klanten waren je voor
                    </h2>
                </div>
                <a href="{{ route('cars.index') }}" class="group hidden items-center gap-2 text-sm text-cream/70 transition hover:text-brass-300 sm:inline-flex">
                    Actueel aanbod <x-icon name="arrow-right" class="h-4 w-4 transition group-hover:translate-x-0.5" />
                </a>
            </div>

            <div class="mt-10 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($cars as $car)
                    <x-car-card :car="$car" />
                @endforeach
            </div>
        </div>
    </section>
@endif
