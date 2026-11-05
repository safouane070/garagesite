{{-- priority: eerste kaart bovenaan de pagina (de grootste zichtbare foto, LCP) meteen laden. --}}
@props(['car', 'priority' => false])

@php
    use App\Enums\CarStatus;
    $cover = $car->primaryImage;
    $isSold = $car->status === CarStatus::Sold;
    $searchKey = \Illuminate\Support\Str::lower($car->brand . ' ' . $car->model . ' ' . $car->variant);
@endphp

{{-- Geen kader: foto en tekst staan direct op de achtergrond. Een rij omkaderde kaarten
     (met een lijn erin en een pijl-vakje) maakte van elke sectie een raster van dozen. --}}
<a href="{{ route('cars.show', $car) }}"
   data-car
   data-name="{{ $searchKey }}"
   data-brand="{{ $car->brand }}"
   data-fuel="{{ $car->fuel_type }}"
   data-body="{{ $car->body_type }}"
   data-transmission="{{ $car->transmission }}"
   data-status="{{ $car->status->value }}"
   data-price="{{ (int) $car->price }}"
   data-year="{{ $car->year }}"
   data-mileage="{{ $car->mileage }}"
   data-featured="{{ $car->is_featured ? 1 : 0 }}"
   data-created="{{ optional($car->created_at)->timestamp ?? $car->id }}"
   class="group relative flex flex-col rounded-[4px] transition-transform duration-200 ease-premium active:scale-[0.98] focus-visible:ring-2 focus-visible:ring-offset-4 focus-visible:ring-offset-ink">

    {{-- Foto --}}
    <div class="relative aspect-[4/3] overflow-hidden rounded-[4px] bg-graphite-800">
        @if ($cover)
            {{-- Miniatuur; de browser pakt zelf de grote foto op een scherp scherm. --}}
            <img src="{{ $cover->thumbUrl() }}"
                 @if ($cover->srcset()) srcset="{{ $cover->srcset() }}" sizes="(min-width: 1280px) 400px, (min-width: 640px) 50vw, calc(100vw - 2rem)" @endif
                 @if ($cover->width) width="{{ $cover->width }}" height="{{ $cover->height }}" @endif
                 alt="{{ $car->title() }} · {{ $car->year }}, {{ $car->color }}"
                 @if ($priority) fetchpriority="high" @else loading="lazy" @endif decoding="async"
                 class="photo-fx h-full w-full object-cover group-hover:scale-[1.02] {{ $isSold ? 'opacity-60 saturate-[0.4]' : '' }}">
        @else
            <div class="flex h-full w-full flex-col items-center justify-center gap-2 text-cream/25">
                <x-icon name="car" class="h-8 w-8" />
                <span class="text-xs">Geen foto</span>
            </div>
        @endif

        {{-- Alleen een status tonen als die afwijkt: "Beschikbaar" op elke kaart zegt niets. --}}
        @if ($car->status !== CarStatus::Available)
            <div class="pointer-events-none absolute left-3 top-3">
                <x-status-badge :status="$car->status" class="backdrop-blur-md" />
            </div>
        @endif
    </div>

    {{-- Merk/model + uitvoering --}}
    <h3 class="mt-3 truncate font-display text-[1.05rem] font-semibold leading-tight text-cream">
        {{ $car->brand }} {{ $car->model }}
    </h3>
    <p class="mt-0.5 truncate text-sm text-cream/60">{{ $car->cleanVariant() ?: $car->body_type }}</p>

    {{-- Kernspecs waarop gekozen wordt: leesbaar, niet als klein grijs bijschrift. --}}
    <p class="mt-2 truncate text-sm text-cream/80 tabular">
        {{ $car->year }} · {{ number_format($car->mileage, 0, ',', '.') }} km · {{ $car->transmission }} · {{ $car->fuel_type }}
    </p>

    {{-- Prijs + indicatief maandbedrag (zoals elke dealer in de regio het toont) --}}
    <div class="mt-2.5 flex flex-wrap items-baseline gap-x-3 gap-y-1">
        <span class="font-display text-xl font-bold tracking-tight text-white tabular">{{ $car->formattedPrice() }}</span>
        @if (! $isSold && ($monthly = $car->monthlyFrom()))
            {{-- "Lease": financial lease is zakelijk; "of € x p/m" las als consumentenkrediet. --}}
            <span class="text-sm text-cream/60 tabular">Lease v.a. € {{ number_format($monthly, 0, ',', '.') }} p/m</span>
        @endif
        @if ($car->isVatDeductible())
            <span class="rounded-[3px] border border-hairline px-1.5 py-0.5 text-[11px] font-medium uppercase tracking-wide text-cream/80">BTW</span>
        @endif
    </div>
</a>
