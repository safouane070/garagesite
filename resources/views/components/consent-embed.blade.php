@props([
    'src',
    'title',
    'provider',                 // bv. "Google Maps" — genoemd in de privacy-uitleg
    'height' => '320px',        // hoogte van het venster zelf
    'button' => 'Laden',
    'icon' => 'map-pin',
    'fallbackUrl' => null,      // werkt ook zonder JS / zonder laden
    'fallbackLabel' => null,
])

{{-- Externe inhoud (kaart, partner-widget) laadt pas na een klik: zonder
     toestemming geen bezoekersgegevens naar derden (AVG). --}}
<div x-data="{ load: false }" {{ $attributes->merge(['class' => 'overflow-hidden rounded-[4px] border border-hairline']) }}>
    <template x-if="load">
        <iframe src="{{ $src }}" title="{{ $title }}" class="block w-full"
                style="height: {{ $height }}; border: 0" referrerpolicy="no-referrer-when-downgrade"></iframe>
    </template>

    <div x-show="! load" class="flex min-h-[320px] flex-col items-center justify-center gap-4 bg-graphite-800 p-8 text-center">
        <span class="flex h-12 w-12 items-center justify-center rounded-full border border-brass-500/35 text-brass-400">
            <x-icon :name="$icon" class="h-6 w-6" />
        </span>
        @if (trim($slot))
            <div class="max-w-md text-cream/80">{{ $slot }}</div>
        @endif
        <p class="max-w-md text-xs leading-relaxed text-cream/60">
            Dit laadt {{ $provider }}. Daarbij deelt je browser gegevens, zoals je IP-adres, met {{ $provider }}.
        </p>
        <div class="flex flex-wrap justify-center gap-3">
            <button type="button" @click="load = true" class="btn btn-primary">{{ $button }}</button>
            @if ($fallbackUrl)
                <a href="{{ $fallbackUrl }}" target="_blank" rel="noopener" class="btn btn-outline">
                    {{ $fallbackLabel }} <x-icon name="arrow-up-right" class="h-4 w-4" />
                </a>
            @endif
        </div>
    </div>
</div>
