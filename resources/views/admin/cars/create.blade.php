<x-layouts.admin title="Nieuwe auto">
    <div class="mb-8">
        <a href="{{ route('admin.dashboard') }}" class="inline-flex items-center gap-1.5 font-mono text-xs uppercase tracking-wider text-cream/70 transition hover:text-brass-300">
            <x-icon name="chevron-left" class="h-4 w-4" /> Terug naar voorraad
        </a>
        <h1 class="mt-3 font-display text-3xl font-bold tracking-tight text-cream">Nieuwe auto toevoegen</h1>
    </div>

    @include('admin.cars._form')
</x-layouts.admin>
