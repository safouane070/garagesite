{{-- Filterpaneel · bindt aan de Alpine-component carCatalog(). --}}
@php
    $idp = $idp ?? 'f';
@endphp
<div class="space-y-7">
    {{-- Zoeken --}}
    <div>
        <label class="field-label" for="{{ $idp }}-flt-q">Zoeken</label>
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/30" />
            <input id="{{ $idp }}-flt-q" type="search" x-model="search" @input="apply()" placeholder="Merk of model…"
                   aria-label="Zoeken op merk of model" class="field-input pl-10">
        </div>
    </div>

    {{-- Merk --}}
    <div>
        <label class="field-label" for="{{ $idp }}-flt-brand">Merk</label>
        <div class="relative">
            <select id="{{ $idp }}-flt-brand" x-model="brand" @change="apply()" aria-label="Filter op merk" class="field-input appearance-none pr-10">
                <option value="">Alle merken</option>
                @foreach ($brands as $b)
                    <option value="{{ $b }}">{{ $b }} ({{ $brandCounts[$b] ?? 0 }})</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/70" />
        </div>
    </div>

    {{-- Carrosserie --}}
    <div>
        <label class="field-label" for="{{ $idp }}-flt-body">Carrosserie</label>
        <div class="relative">
            <select id="{{ $idp }}-flt-body" x-model="body" @change="apply()" aria-label="Filter op carrosserie" class="field-input appearance-none pr-10">
                <option value="">Alle carrosserieën</option>
                @foreach ($bodyTypes as $bt)
                    <option value="{{ $bt }}">{{ $bt }} ({{ $bodyCounts[$bt] ?? 0 }})</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/70" />
        </div>
    </div>

    {{-- Brandstof (meervoudige keuze als chips) --}}
    <div>
        <span class="field-label" id="{{ $idp }}-flt-fuel">Brandstof</span>
        <div class="flex flex-wrap gap-2" role="group" aria-labelledby="{{ $idp }}-flt-fuel">
            @foreach ($fuelTypes as $f)
                <button type="button" @click="toggleFuel('{{ $f }}')" :aria-pressed="fuels.includes('{{ $f }}')"
                        :class="fuels.includes('{{ $f }}') ? 'border-brass-500 bg-brass-500/15 text-brass-300' : 'border-hairline text-cream/70 [@media(hover:hover)]:hover:border-cream/30 [@media(hover:hover)]:hover:text-cream/80'"
                        class="filter-chip">
                    {{ $f }} ({{ $fuelCounts[$f] ?? 0 }})
                </button>
            @endforeach
        </div>
    </div>

    {{-- Transmissie: één keuze; nogmaals tikken zet hem uit. --}}
    <div>
        <span class="field-label" id="{{ $idp }}-flt-trans">Transmissie</span>
        <div class="flex flex-wrap gap-2" role="group" aria-labelledby="{{ $idp }}-flt-trans">
            @foreach ($transmissions as $t)
                <button type="button" @click="transmission = transmission === '{{ $t }}' ? '' : '{{ $t }}'; apply()" :aria-pressed="transmission === '{{ $t }}'"
                        :class="transmission === '{{ $t }}' ? 'border-brass-500 bg-brass-500/15 text-brass-300' : 'border-hairline text-cream/70 [@media(hover:hover)]:hover:border-cream/30 [@media(hover:hover)]:hover:text-cream/80'"
                        class="filter-chip">
                    {{ $t }} ({{ $transmissionCounts[$t] ?? 0 }})
                </button>
            @endforeach
        </div>
    </div>

    {{-- Kilometerstand: maximum --}}
    <div>
        <label class="field-label" for="{{ $idp }}-flt-km">Kilometerstand</label>
        <div class="relative">
            <select id="{{ $idp }}-flt-km" x-model.number="kmMax" @change="apply()" class="field-input appearance-none pr-10">
                <option value="0">Elke kilometerstand</option>
                @foreach ([25000, 50000, 75000, 100000, 150000, 200000] as $km)
                    <option value="{{ $km }}">Tot {{ number_format($km, 0, ',', '.') }} km</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/70" />
        </div>
    </div>

    {{-- Prijs (dual-range slider) --}}
    <div>
        <div class="flex items-center justify-between">
            <span class="field-label mb-0">Prijs</span>
            <span class="text-xs font-medium text-brass-300 tabular" x-text="'€ '+fmt(price[0])+' – € '+fmt(price[1])"></span>
        </div>
        <div class="relative mt-5 h-5">
            <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-graphite-500"></div>
            <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brass-500"
                 :style="`left:${(price[0]-bounds.priceMin)/(bounds.priceMax-bounds.priceMin)*100}%;right:${100-(price[1]-bounds.priceMin)/(bounds.priceMax-bounds.priceMin)*100}%`"></div>
            <input type="range" class="dual-range" :min="bounds.priceMin" :max="bounds.priceMax" step="500"
                   x-model.number="price[0]" @input="price[0]=Math.min(price[0],price[1]); apply()" aria-label="Minimumprijs">
            <input type="range" class="dual-range" :min="bounds.priceMin" :max="bounds.priceMax" step="500"
                   x-model.number="price[1]" @input="price[1]=Math.max(price[1],price[0]); apply()" aria-label="Maximumprijs">
        </div>
    </div>

    {{-- Bouwjaar (dual-range slider) --}}
    <div>
        <div class="flex items-center justify-between">
            <span class="field-label mb-0">Bouwjaar</span>
            <span class="text-xs font-medium text-brass-300 tabular" x-text="year[0]+' – '+year[1]"></span>
        </div>
        <div class="relative mt-5 h-5">
            <div class="absolute inset-x-0 top-1/2 h-1 -translate-y-1/2 rounded-full bg-graphite-500"></div>
            <div class="absolute top-1/2 h-1 -translate-y-1/2 rounded-full bg-brass-500"
                 :style="`left:${(year[0]-bounds.yearMin)/(bounds.yearMax-bounds.yearMin)*100}%;right:${100-(year[1]-bounds.yearMin)/(bounds.yearMax-bounds.yearMin)*100}%`"></div>
            <input type="range" class="dual-range" :min="bounds.yearMin" :max="bounds.yearMax" step="1"
                   x-model.number="year[0]" @input="year[0]=Math.min(year[0],year[1]); apply()" aria-label="Vroegste bouwjaar">
            <input type="range" class="dual-range" :min="bounds.yearMin" :max="bounds.yearMax" step="1"
                   x-model.number="year[1]" @input="year[1]=Math.max(year[1],year[0]); apply()" aria-label="Laatste bouwjaar">
        </div>
    </div>
</div>
