@props(['floating' => false, 'text' => null])

@php
    // Optioneel vooraf ingevuld bericht (bv. op een detailpagina: welke auto).
    $href = 'https://wa.me/' . preg_replace('/\D/', '', (string) config('brand.contact.whatsapp'))
        . ($text ? '?text=' . rawurlencode($text) : '');
    $svg = '<svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" class="' . ($floating ? 'h-7 w-7' : 'h-5 w-5') . '"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.91-4.45 9.91-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2Zm0 1.8c2.17 0 4.2.85 5.74 2.38a8.06 8.06 0 0 1 2.38 5.73c0 4.47-3.64 8.11-8.12 8.11a8.1 8.1 0 0 1-4.13-1.13l-.3-.18-3.12.82.83-3.04-.19-.31a8.05 8.05 0 0 1-1.24-4.29c0-4.47 3.64-8.11 8.11-8.11Zm-2.87 4.37c-.15 0-.4.06-.6.28-.21.22-.8.78-.8 1.9 0 1.12.82 2.2.93 2.35.11.15 1.6 2.55 3.98 3.48 1.98.78 2.38.62 2.81.58.43-.04 1.38-.56 1.58-1.11.2-.55.2-1.02.14-1.11-.06-.1-.21-.16-.44-.28-.23-.11-1.38-.68-1.6-.76-.21-.08-.37-.11-.52.12-.15.22-.6.75-.73.9-.13.15-.27.17-.5.06-.23-.12-.98-.36-1.86-1.15-.69-.61-1.15-1.37-1.29-1.6-.13-.23-.01-.35.1-.47.1-.1.23-.27.34-.4.11-.14.15-.23.23-.39.08-.15.04-.29-.02-.4-.06-.12-.52-1.27-.72-1.73-.19-.46-.38-.4-.52-.4Z"/></svg>';
@endphp

@if ($floating)
    <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="Chat via WhatsApp"
       class="fixed bottom-5 right-5 z-40 flex h-14 w-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg shadow-black/40 transition hover:scale-105 focus-visible:ring-2 focus-visible:ring-white/70">
        {!! $svg !!}
    </a>
@else
    <a href="{{ $href }}" target="_blank" rel="noopener" aria-label="Chat via WhatsApp"
       {{ $attributes->merge(['class' => 'flex h-9 w-9 items-center justify-center rounded-full text-[#25D366] transition hover:bg-[#25D366]/10']) }}>
        {!! $svg !!}
    </a>
@endif
