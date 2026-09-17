<x-layouts.admin title="Dashboard">
    {{-- Kop --}}
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <p class="kicker">Beheer</p>
            <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-cream">Voorraad</h1>
        </div>
        <a href="{{ route('admin.cars.create') }}" class="btn btn-primary">
            <x-icon name="plus" class="h-4 w-4" /> Nieuwe auto
        </a>
    </div>

    {{-- Statistieken --}}
    <dl class="mt-8 grid grid-cols-2 gap-px overflow-hidden rounded-[4px] border border-hairline bg-hairline lg:grid-cols-4">
        @php
            $tiles = [
                ['label' => 'Totaal', 'value' => $stats['total'], 'accent' => 'text-cream'],
                ['label' => 'Beschikbaar', 'value' => $stats['available'], 'accent' => 'text-emerald-300'],
                ['label' => 'Gereserveerd', 'value' => $stats['reserved'], 'accent' => 'text-amber-300'],
                ['label' => 'Verkocht', 'value' => $stats['sold'], 'accent' => 'text-rose-300'],
            ];
        @endphp
        @foreach ($tiles as $t)
            <div class="bg-graphite-800 p-5">
                <dt class="font-mono text-[0.7rem] uppercase tracking-wider text-cream/40">{{ $t['label'] }}</dt>
                <dd class="mt-2 font-display text-3xl font-bold tabular {{ $t['accent'] }}">{{ $t['value'] }}</dd>
            </div>
        @endforeach
    </dl>

    {{-- Autolijst --}}
    <div class="mt-8 overflow-hidden rounded-[4px] border border-hairline">
        @if ($cars->count())
            <div class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-left">
                    <thead>
                        <tr class="border-b border-hairline bg-graphite-800 font-mono text-[0.7rem] uppercase tracking-wider text-cream/40">
                            <th class="px-4 py-3 font-medium">Auto</th>
                            <th class="px-4 py-3 font-medium">Bouwjaar</th>
                            <th class="px-4 py-3 font-medium">Prijs</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 text-right font-medium">Acties</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-hairline">
                        @foreach ($cars as $car)
                            <tr class="group transition hover:bg-graphite-800/50">
                                {{-- Auto (foto + naam) --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-3">
                                        <div class="h-12 w-16 shrink-0 overflow-hidden rounded-[3px] border border-hairline bg-graphite-800">
                                            @if ($car->primaryImage)
                                                <img src="{{ $car->primaryImage->url() }}" alt="" class="h-full w-full object-cover">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-cream/25"><x-icon name="car" class="h-5 w-5" /></div>
                                            @endif
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium text-cream">{{ $car->brand }} {{ $car->model }}</p>
                                            <p class="truncate font-mono text-xs text-cream/45">{{ $car->variant ?: $car->body_type }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 font-mono text-sm text-cream/70 tabular">{{ $car->year }}</td>
                                <td class="px-4 py-3 font-display font-semibold text-brass-400 tabular">{{ $car->formattedPrice() }}</td>

                                {{-- Snelle statuswijziging --}}
                                <td class="px-4 py-3">
                                    <form method="POST" action="{{ route('admin.cars.status', $car) }}" x-data>
                                        @csrf @method('PATCH')
                                        <div class="relative inline-block">
                                            <select name="status" @change="$el.form.submit()"
                                                    class="appearance-none rounded-[3px] border border-hairline bg-graphite-800 py-1.5 pl-3 pr-8 text-xs text-cream focus:border-brass-500 focus:ring-1 focus:ring-brass-500">
                                                @foreach (\App\Enums\CarStatus::cases() as $status)
                                                    <option value="{{ $status->value }}" @selected($car->status === $status)>{{ $status->label() }}</option>
                                                @endforeach
                                            </select>
                                            <x-icon name="chevron-down" class="pointer-events-none absolute right-2 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-cream/40" />
                                        </div>
                                    </form>
                                </td>

                                {{-- Acties --}}
                                <td class="px-4 py-3">
                                    <div class="flex items-center justify-end gap-1">
                                        <a href="{{ route('cars.show', $car) }}" target="_blank" rel="noopener"
                                           class="flex h-8 w-8 items-center justify-center rounded-[3px] text-cream/65 transition hover:bg-white/5 hover:text-cream" title="Bekijk op site">
                                            <x-icon name="arrow-up-right" class="h-4 w-4" />
                                        </a>
                                        <a href="{{ route('admin.cars.edit', $car) }}"
                                           class="flex h-8 w-8 items-center justify-center rounded-[3px] text-cream/65 transition hover:bg-white/5 hover:text-brass-300" title="Bewerken">
                                            <x-icon name="pencil" class="h-4 w-4" />
                                        </a>
                                        <form method="POST" action="{{ route('admin.cars.destroy', $car) }}"
                                              onsubmit="return confirm('“{{ $car->title() }}” definitief verwijderen?');">
                                            @csrf @method('DELETE')
                                            <button type="submit" title="Verwijderen"
                                                    class="flex h-8 w-8 items-center justify-center rounded-[3px] text-cream/65 transition hover:bg-rose-500/10 hover:text-rose-300">
                                                <x-icon name="trash" class="h-4 w-4" />
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-cream/40"><x-icon name="car" class="h-6 w-6" /></span>
                <h3 class="mt-5 font-display text-xl font-semibold text-cream">Nog geen auto's</h3>
                <p class="mt-2 max-w-sm text-sm text-cream/65">Voeg je eerste occasion toe om te beginnen.</p>
                <a href="{{ route('admin.cars.create') }}" class="btn btn-primary mt-6"><x-icon name="plus" class="h-4 w-4" /> Nieuwe auto</a>
            </div>
        @endif
    </div>

    @if ($cars->hasPages())
        <div class="mt-6">{{ $cars->links() }}</div>
    @endif
</x-layouts.admin>
