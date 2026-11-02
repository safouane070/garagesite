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

{{-- Eén echte review groot en volledig, i.p.v. een rail met afgekapte kaartjes.
     De rest staat op Google; daar linkt de score naartoe. --}}
@php $quote = $testimonials[0] ?? null; @endphp

<section {{ $attributes->merge(['class' => 'container-x py-16 lg:py-24']) }}>
    <h2 class="font-display text-3xl font-bold uppercase tracking-tight text-white sm:text-4xl">
        Wat klanten zeggen
    </h2>

    @if ($quote)
        <figure class="mt-8 max-w-3xl">
            <span class="flex gap-0.5">{!! $stars((int) ($quote['rating'] ?? 5)) !!}</span>
            <blockquote class="mt-5 font-display text-xl font-medium leading-snug text-cream sm:text-3xl">
                “{{ $quote['text'] }}”
            </blockquote>
            <figcaption class="mt-5 flex items-center gap-2 text-sm text-cream/60">
                {!! $googleG !!} {{ $quote['name'] }}
            </figcaption>
        </figure>
    @endif

    {{-- Google-score --}}
    <a href="{{ $reviewUrl }}" target="_blank" rel="noopener"
       class="group mt-10 inline-flex flex-wrap items-center gap-x-3 gap-y-1 text-sm text-cream/70 transition hover:text-cream">
        <span class="font-display text-xl font-bold text-white tabular">{{ $ratingNl }}</span>
        <span class="relative inline-block">
            <span class="flex gap-0.5 text-cream/20">{!! $starsRow !!}</span>
            <span class="absolute inset-0 flex gap-0.5 overflow-hidden text-brass-400" style="width: {{ $fillPct }}%">{!! $starsRow !!}</span>
        </span>
        <span>Lees alle {{ $count }} reviews op Google</span>
        <x-icon name="arrow-up-right" class="h-4 w-4 transition group-hover:-translate-y-0.5 group-hover:translate-x-0.5" />
    </a>
</section>
