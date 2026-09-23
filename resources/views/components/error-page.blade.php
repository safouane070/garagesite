@props(['code', 'title', 'message'])

{{-- Gedeelde opmaak voor 4xx-foutpagina's: in de site-layout, met een weg terug. --}}
<x-layouts.public :title="$title">
    <section class="container-x flex min-h-[60vh] flex-col items-start justify-center py-20">
        <p class="kicker flex items-center gap-3">
            <span class="h-px w-10 bg-brass-500"></span> Foutcode {{ $code }}
        </p>
        <h1 class="mt-5 max-w-2xl font-display text-4xl font-bold uppercase leading-[1.02] tracking-tight text-cream sm:text-5xl">
            {{ $title }}
        </h1>
        <p class="mt-5 max-w-xl text-lg leading-relaxed text-cream/75">{{ $message }}</p>

        <div class="mt-9 flex flex-wrap items-center gap-3">
            {{ $slot }}
        </div>
    </section>
</x-layouts.public>
