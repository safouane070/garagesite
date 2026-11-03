@php
    ['rating' => $rating, 'count' => $count] = \App\Support\Reviews::summary();
    $reviewUrl = config('brand.reviews.url');
    $ratingNl = str_replace('.', ',', (string) $rating);
    $fillPct = max(0, min(100, $rating / 5 * 100));
    $testimonials = \App\Support\Reviews::all();

    $starPath = 'M11.5 2.8a.6.6 0 0 1 1 0l2.4 5a.6.6 0 0 0 .5.3l5.4.5a.6.6 0 0 1 .3 1l-4 3.6a.6.6 0 0 0-.2.6l1.2 5.3a.6.6 0 0 1-.9.6l-4.6-2.8a.6.6 0 0 0-.6 0l-4.6 2.8a.6.6 0 0 1-.9-.6l1.2-5.3a.6.6 0 0 0-.2-.6l-4-3.6a.6.6 0 0 1 .3-1l5.4-.5a.6.6 0 0 0 .5-.3z';
    $starsRow = str_repeat('<svg viewBox="0 0 24 24" fill="currentColor" class="h-5 w-5 shrink-0"><path d="' . $starPath . '"/></svg>', 5);

    // Sterrenrij voor één review: eerst $n gevuld (brass), rest gedimd.
    $stars = function (int $n) use ($starPath) {
        $out = '';
        for ($i = 1; $i <= 5; $i++) {
            $color = $i <= $n ? 'text-brass-400' : 'text-cream/20';
            $out .= '<svg viewBox="0 0 24 24" fill="currentColor" class="h-4 w-4 shrink-0 ' . $color . '"><path d="' . $starPath . '"/></svg>';
        }
        return $out;
    };

    $googleG = '<svg viewBox="0 0 48 48" class="h-4 w-4 shrink-0" aria-hidden="true"><path fill="#4285F4" d="M45.12 24.5c0-1.56-.14-3.06-.4-4.5H24v8.51h11.84c-.51 2.75-2.06 5.08-4.39 6.64v5.52h7.11c4.16-3.83 6.56-9.47 6.56-16.17z"/><path fill="#34A853" d="M24 46c5.94 0 10.92-1.97 14.56-5.33l-7.11-5.52c-1.97 1.32-4.49 2.1-7.45 2.1-5.73 0-10.58-3.87-12.31-9.07H4.34v5.7C7.96 41.07 15.4 46 24 46z"/><path fill="#FBBC05" d="M11.69 28.18C11.25 26.86 11 25.45 11 24s.25-2.86.69-4.18v-5.7H4.34C2.85 17.09 2 20.45 2 24s.85 6.91 2.34 9.88l7.35-5.7z"/><path fill="#EA4335" d="M24 10.75c3.23 0 6.13 1.11 8.41 3.29l6.31-6.31C34.91 4.18 29.93 2 24 2 15.4 2 7.96 6.93 4.34 14.12l7.35 5.7c1.73-5.2 6.58-9.07 12.31-9.07z"/></svg>';
@endphp

{{-- Meerdere echte reviews: het bewijs zit in "veel tevreden klanten", niet in één lang citaat.
     Telefoon: swipe-rail; desktop: de eerste drie naast elkaar. Lichte vlakken zonder rand. --}}
<section {{ $attributes->merge(['class' => 'py-16 lg:py-24']) }}>
    <div class="container-x flex flex-wrap items-end justify-between gap-x-8 gap-y-5">
        <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl">
            Wat klanten zeggen
        </h2>

        {{-- Google-score: de kern van het bewijs, dus groot en meteen bij de kop --}}
        <a href="{{ $reviewUrl }}" target="_blank" rel="noopener"
           class="group flex items-center gap-4 transition-opacity hover:opacity-90">
            <span class="font-display text-4xl font-bold leading-none text-white tabular">{{ $ratingNl }}</span>
            <span class="flex flex-col gap-1.5">
                <span class="relative inline-block">
                    <span class="flex gap-0.5 text-cream/20">{!! $starsRow !!}</span>
                    <span class="absolute inset-0 flex gap-0.5 overflow-hidden text-brass-400" style="width: {{ $fillPct }}%">{!! $starsRow !!}</span>
                </span>
                <span class="inline-flex items-center gap-1.5 text-sm text-cream/65 group-hover:text-cream">
                    {{ $count }} reviews op Google <x-icon name="arrow-up-right" class="h-3.5 w-3.5" />
                </span>
            </span>
        </a>
    </div>

    @if (count($testimonials))
        <div class="mt-10 flex snap-x snap-mandatory scroll-px-5 gap-4 overflow-x-auto px-5 pb-4 scroll-thin sm:scroll-px-8 sm:px-8 lg:mx-auto lg:grid lg:max-w-container lg:grid-cols-3 lg:gap-6 lg:overflow-visible lg:pb-0">
            @php
                // Eerst 5 sterren met een tekst die iets zegt (niet "Top!", niet een halve pagina);
                // de rest daarna. Zo staan er geen kale of scheve citaten vooraan.
                $fits = fn ($t) => (int) ($t['rating'] ?? 5) === 5 && mb_strlen($t['text']) >= 80 && mb_strlen($t['text']) <= 320;
                $ordered = array_merge(array_filter($testimonials, $fits), array_filter($testimonials, fn ($t) => ! $fits($t)));
            @endphp
            @foreach (array_slice($ordered, 0, 6) as $t)
                <figure @class([
                    'flex w-[85%] max-w-[22rem] shrink-0 snap-start flex-col rounded-[4px] bg-graphite-800 p-6 sm:w-[340px] lg:w-auto lg:max-w-none',
                    'lg:hidden' => $loop->index >= 3,
                ])>
                    <span class="flex gap-0.5">{!! $stars((int) ($t['rating'] ?? 5)) !!}</span>
                    <blockquote class="mt-4 flex-1 leading-relaxed text-cream/85 [display:-webkit-box] [-webkit-box-orient:vertical] [-webkit-line-clamp:6] overflow-hidden">
                        {{ $t['text'] }}
                    </blockquote>
                    <figcaption class="mt-5 flex items-center gap-2 text-sm text-cream/60">
                        {{-- Alleen namen die volledig in kleine letters binnenkomen opknappen; "MBM B.V." blijft zo. --}}
                        {!! $googleG !!} {{ mb_strtolower($t['name']) === $t['name'] ? \Illuminate\Support\Str::title($t['name']) : $t['name'] }}
                    </figcaption>
                </figure>
            @endforeach
        </div>
    @endif
</section>
