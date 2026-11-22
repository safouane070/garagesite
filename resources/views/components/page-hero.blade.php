@props(['title', 'intro' => null, 'image' => null, 'alt' => '', 'position' => 'center'])

{{-- Geen label boven de kop: de kop draagt de pagina zelf. Met een foto (de eigen beelden van de
     zaak) staat die rechts op groot scherm en onder de intro op een telefoon: de subpagina's waren
     tot nu toe alleen tekst op donker. --}}
<section class="border-b border-hairline">
    <div @class(['container-x py-14 lg:py-20', 'grid items-center gap-10 lg:grid-cols-12 lg:gap-14' => $image])>
        <div @class(['lg:col-span-6' => $image])>
            <h1 class="max-w-3xl font-display text-4xl font-bold uppercase leading-[1.02] tracking-tight text-cream sm:text-5xl">
                {{ $title }}
            </h1>
            @if ($intro)
                <p class="mt-5 max-w-2xl text-lg leading-relaxed text-cream/70">{{ $intro }}</p>
            @endif
            {{ $slot }}
        </div>
        @if ($image)
            <div class="lg:col-span-6">
                <img src="{{ asset($image) }}" alt="{{ $alt }}" width="1000" height="750" fetchpriority="high" decoding="async"
                     style="object-position: {{ $position }}"
                     class="photo-fx aspect-[4/3] w-full rounded-[4px] object-cover shadow-[0_24px_60px_-28px_rgba(0,0,0,0.85)]">
            </div>
        @endif
    </div>
</section>
