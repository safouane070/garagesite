{{-- Filterpaneel · bindt aan de Alpine-component carCatalog(). --}}
<div class="space-y-7">
    {{-- Zoeken --}}
    <div>
        <label class="field-label">Zoeken</label>
        <div class="relative">
            <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/30" />
            <input type="search" x-model="search" @input="apply()" placeholder="Merk of model…"
                   class="field-input pl-10">
        </div>
    </div>

    {{-- Merk --}}
    <div>
        <label class="field-label">Merk</label>
        <div class="relative">
            <select x-model="brand" @change="apply()" class="field-input appearance-none pr-10">
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
        <label class="field-label">Carrosserie</label>
        <div class="relative">
            <select x-model="body" @change="apply()" class="field-input appearance-none pr-10">
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
        <label class="field-label">Brandstof</label>
        <div class="flex flex-wrap gap-2">
            @foreach ($fuelTypes as $f)
                <button type="button" @click="toggleFuel('{{ $f }}')"
                        :class="fuels.includes('{{ $f }}') ? 'border-brass-500 bg-brass-500/15 text-brass-300' : 'border-hairline text-cream/70 hover:border-cream/30 hover:text-cream/80'"
                        class="rounded-[3px] border px-3 py-1.5 font-mono text-[0.7rem] uppercase tracking-wider transition">
                    {{ $f }} ({{ $fuelCounts[$f] ?? 0 }})
                </button>
            @endforeach
        </div>
    </div>

    {{-- Prijs (dual-range slider) --}}
    <div>
        <div class="flex items-center justify-between">
            <label class="field-label mb-0">Prijs</label>
            <span class="font-mono text-[0.7rem] text-brass-300 tabular" x-text="'€ '+fmt(price[0])+' – € '+fmt(price[1])"></span>
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
            <label class="field-label mb-0">Bouwjaar</label>
            <span class="font-mono text-[0.7rem] text-brass-300 tabular" x-text="year[0]+' – '+year[1]"></span>
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
