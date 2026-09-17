@props(['compact' => false])

{{-- Officieel logo van Autobedrijf Rijswijk: witte woordmerk + rode "R".
     Werkt zoals het is op de donkere achtergrond. --}}
<span {{ $attributes->merge(['class' => 'inline-flex items-center select-none']) }}>
    <img src="{{ asset('images/dealer-logo.svg') }}" alt="Autobedrijf Rijswijk"
         class="w-auto {{ $compact ? 'h-7 max-w-[2rem] object-cover object-left' : 'h-6 sm:h-7' }}">
</span>
