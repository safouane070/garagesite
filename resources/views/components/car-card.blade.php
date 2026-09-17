@props(['car'])

@php
    use App\Enums\CarStatus;
    $cover = $car->primaryImage;
    $isSold = $car->status === CarStatus::Sold;
    $searchKey = \Illuminate\Support\Str::lower($car->brand . ' ' . $car->model . ' ' . $car->variant);
@endphp

<a href="{{ route('cars.show', $car) }}"
   data-car
   data-name="{{ $searchKey }}"
   data-brand="{{ $car->brand }}"
   data-fuel="{{ $car->fuel_type }}"
   data-transmission="{{ $car->transmission }}"
   data-status="{{ $car->status->value }}"
   data-price="{{ (int) $car->price }}"
   data-year="{{ $car->year }}"
   data-mileage="{{ $car->mileage }}"
   data-featured="{{ $car->is_featured ? 1 : 0 }}"
   data-created="{{ optional($car->created_at)->timestamp ?? $car->id }}"
   class="group relative flex flex-col overflow-hidden rounded-[4px] border border-hairline bg-graphite-700/50
          transition duration-300 ease-premium hover:-translate-y-1 hover:border-brass-500/40
          hover:bg-graphite-700 focus-visible:ring-2">

    {{-- Foto --}}
    <div class="relative aspect-[4/3] overflow-hidden bg-graphite-800">
        @if ($cover)
            <img src="{{ $cover->url() }}"
                 alt="{{ $car->title() }} · {{ $car->year }}, {{ $car->color }}"
                 loading="lazy"
                 class="photo-fx h-full w-full object-cover group-hover:scale-[1.05] {{ $isSold ? 'opacity-60 saturate-[0.4]' : '' }}">
        @else
            <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-cream/25">
                <x-icon name="car" class="h-8 w-8" />
                <span class="font-mono text-[0.7rem] uppercase tracking-widest">Geen foto</span>
            </div>
        @endif

        <div class="pointer-events-none absolute inset-x-0 top-0 flex items-start justify-between p-3">
            <x-status-badge :status="$car->status" class="backdrop-blur-md" />
            @if ($car->is_featured && ! $isSold)
                <span class="flex h-7 w-7 items-center justify-center rounded-full bg-scrim/70 text-brass-300 ring-1 ring-inset ring-brass-500/30 backdrop-blur-md">
                    <x-icon name="star" class="h-3.5 w-3.5" />
                </span>
            @endif
        </div>
    </div>

    {{-- Kop: merk/model + bouwjaar --}}
    <div class="flex items-start justify-between gap-3 px-4 pt-4">
        <div class="min-w-0">
            <h3 class="truncate font-display text-[1.05rem] font-semibold leading-tight text-cream">
                {{ $car->brand }} {{ $car->model }}
            </h3>
            <p class="mt-0.5 truncate text-sm text-cream/70">{{ $car->variant ?: $car->body_type }}</p>
        </div>
        <span class="shrink-0 font-mono text-xs text-cream/70">'{{ substr($car->year, 2) }}</span>
    </div>

    {{-- Specs in mono (secundair) --}}
    <div class="mt-3 flex items-center gap-3 px-4 font-mono text-[0.7rem] text-cream/65">
        <span class="inline-flex items-center gap-1.5"><x-icon name="gauge" class="h-3.5 w-3.5 text-cream/30" />{{ number_format($car->mileage, 0, ',', '.') }}</span>
        <span class="text-cream/15">/</span>
        <span class="inline-flex items-center gap-1.5"><x-icon name="fuel" class="h-3.5 w-3.5 text-cream/30" />{{ $car->fuel_type }}</span>
        <span class="text-cream/15">/</span>
        <span class="inline-flex items-center gap-1.5"><x-icon name="gearbox" class="h-3.5 w-3.5 text-cream/30" />{{ $car->transmission === 'Automaat' ? 'Aut.' : 'Hand.' }}</span>
    </div>

    {{-- Prijs: ademt, met duidelijke actie --}}
    <div class="mt-4 flex items-end justify-between border-t border-hairline px-4 py-3.5">
        <div>
            <span class="kicker text-[0.6rem]">Vraagprijs</span>
            <span class="mt-1 block font-display text-2xl font-bold tracking-tight text-cream tabular">{{ $car->formattedPrice() }}</span>
        </div>
        <span class="mb-1 inline-flex h-9 w-9 items-center justify-center rounded-[3px] border border-hairline text-cream/65
                     transition duration-300 ease-premium group-hover:border-brass-500 group-hover:bg-brass-500 group-hover:text-cream"
              aria-hidden="true">
            <x-icon name="arrow-right" class="h-4 w-4" />
        </span>
    </div>
</a>
