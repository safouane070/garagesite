@props(['kicker' => null, 'title', 'intro' => null])

<section class="border-b border-hairline">
    <div class="container-x py-14 lg:py-20">
        @if ($kicker)
            <p class="kicker flex items-center gap-3">
                <span class="h-px w-10 bg-brass-500"></span> {{ $kicker }}
            </p>
        @endif
        <h1 class="mt-5 max-w-3xl font-display text-4xl font-bold uppercase leading-[1.02] tracking-tight text-cream sm:text-5xl">
            {{ $title }}
        </h1>
        @if ($intro)
            <p class="mt-5 max-w-2xl text-lg leading-relaxed text-cream/70">{{ $intro }}</p>
        @endif
        {{ $slot }}
    </div>
</section>
