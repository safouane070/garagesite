@props(['floating' => false, 'text' => null])

@php
    // Optioneel vooraf ingevuld bericht (bv. op een detailpagina: welke auto).
    $href = 'https://wa.me/' . preg_replace('/\D/', '', (string) config('brand.contact.whatsapp'))
        . ($text ? '?text=' . rawurlencode($text) : '');
    $size = $floating ? 'h-7 w-7' : 'h-5 w-5';
@endphp

@if ($floating)
    {{-- Wijkt uit (1) zolang iets met [data-fab-avoid] in beeld is (hero-zoekbalk, footer) en
         (2) terwijl je naar beneden scrolt: met de compacte autorijen lag hij anders steeds op
         prijs en lease. Omhoog scrollen (of bovenaan staan) brengt hem terug, zoals in apps. --}}
    <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="Chat via WhatsApp"
       x-data="{ hidden: false, seen: new Set(), down: false, last: 0, get off() { return this.hidden || this.down } }"
       x-init="const io = new IntersectionObserver(es => { es.forEach(e => e.isIntersecting ? seen.add(e.target) : seen.delete(e.target)); hidden = seen.size > 0 }, { rootMargin: '0px 0px -40px 0px' });
               document.querySelectorAll('[data-fab-avoid]').forEach(el => io.observe(el))"
       @scroll.window.throttle.80ms="const y = scrollY; if (Math.abs(y - last) > 8) { down = y > last && y > 160; last = y }"
       :class="off && 'pointer-events-none translate-y-2 scale-95 opacity-0'"
       :tabindex="off ? -1 : 0" :aria-hidden="off"
       {{ $attributes->merge(['class' => 'fixed bottom-4 right-4 z-40 flex h-12 w-12 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/40 transition-[transform,opacity] duration-150 ease-out motion-reduce:transform-none [@media(hover:hover)]:hover:scale-105 active:scale-95 sm:bottom-5 sm:right-5 sm:h-14 sm:w-14 focus-visible:ring-2 focus-visible:ring-white/70']) }}>
        <x-icon name="whatsapp" :class="$size" />
    </a>
@else
    <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="Chat via WhatsApp"
       {{ $attributes->merge(['class' => 'flex h-9 w-9 items-center justify-center rounded-full text-[#25D366] transition hover:bg-[#25D366]/10']) }}>
        <x-icon name="whatsapp" :class="$size" />
    </a>
@endif
