<x-layouts.admin :title="'Bewerk ' . $car->title()">
    <div class="mb-8 flex flex-wrap items-start justify-between gap-4">
        <div>
            <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 font-mono text-xs uppercase tracking-wider text-cream/70 transition hover:text-brass-300">
                <x-icon name="chevron-left" class="h-4 w-4" /> Terug naar voorraad
            </a>
            <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-cream">{{ $car->title() }}</h1>
        </div>
        <a href="{{ route('cars.show', $car) }}" target="_blank" rel="noopener" class="btn btn-outline">
            <x-icon name="arrow-up-right" class="h-4 w-4" /> Bekijk op site
        </a>
    </div>

    @include('admin.cars._form')
</x-layouts.admin>
