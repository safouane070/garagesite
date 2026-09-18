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

    {{-- Voorraad + filter --}}
    @if ($cars->isEmpty())
        <div class="mt-8 flex flex-col items-center justify-center rounded-[4px] border border-hairline px-6 py-20 text-center">
            <span class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-cream/40"><x-icon name="car" class="h-6 w-6" /></span>
            <h3 class="mt-5 font-display text-xl font-semibold text-cream">Nog geen auto's</h3>
            <p class="mt-2 max-w-sm text-sm text-cream/65">Voeg je eerste occasion toe om te beginnen.</p>
            <a href="{{ route('admin.cars.create') }}" class="btn btn-primary mt-6"><x-icon name="plus" class="h-4 w-4" /> Nieuwe auto</a>
        </div>
    @else
        <div x-data="{
                q: '',
                status: '',
                items: @js($items),
                match(t, s) {
                    const term = this.q.toLowerCase().trim();
                    return (term === '' || t.includes(term)) && (this.status === '' || this.status === s);
                },
                get matched() { return this.items.filter(i => this.match(i.t, i.s)).length; },
                get filtering() { return this.q.trim() !== '' || this.status !== ''; },
                reset() { this.q = ''; this.status = ''; },
             }"
             class="mt-8 overflow-hidden rounded-[4px] border border-hairline">

            {{-- Toolbar --}}
            <div class="flex flex-col gap-2.5 border-b border-hairline bg-graphite-800/40 p-3 lg:flex-row lg:items-center">
                {{-- Zoekveld: filtert direct terwijl je typt --}}
                <label class="flex flex-1 items-center gap-2 rounded-[3px] border border-hairline bg-graphite-800 px-3 transition focus-within:border-brass-500 focus-within:ring-1 focus-within:ring-brass-500">
                    <x-icon name="search" class="h-4 w-4 shrink-0 text-cream/40" />
                    <input type="search" x-model="q" placeholder="Zoek op merk, model, bouwjaar…"
                           class="w-full border-0 bg-transparent py-2 text-sm text-cream placeholder:text-cream/35 focus:outline-none focus:ring-0">
                    <button type="button" x-show="q" @click="q = ''" title="Wis zoekopdracht"
                            class="shrink-0 text-cream/40 transition hover:text-cream"><x-icon name="x" class="h-4 w-4" /></button>
                </label>

                <div class="flex flex-wrap items-center gap-2">
                    {{-- Status --}}
                    <div class="relative min-w-[150px] flex-1 lg:flex-none">
                        <select x-model="status"
                                class="w-full appearance-none rounded-[3px] border border-hairline bg-graphite-800 py-2 pl-3 pr-9 text-sm text-cream focus:border-brass-500 focus:ring-1 focus:ring-brass-500">
                            <option value="">Alle statussen</option>
                            @foreach (\App\Enums\CarStatus::cases() as $s)
                                <option value="{{ $s->value }}">{{ $s->label() }}</option>
                            @endforeach
                        </select>
                        <x-icon name="chevron-down" class="pointer-events-none absolute right-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-cream/40" />
                    </div>

                    <button type="button" x-show="filtering" x-cloak @click="reset()"
                            class="btn btn-outline shrink-0 px-4 py-2">Wissen</button>
                </div>
            </div>

            {{-- Live telling --}}
            <p x-show="filtering" x-cloak class="border-b border-hairline px-4 py-2.5 font-mono text-xs text-cream/45">
                <span x-text="matched"></span> van {{ $cars->count() }} auto's
            </p>

            {{-- Tabel --}}
            <div class="overflow-x-auto" x-show="matched > 0">
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
                            <tr data-search="{{ $car->searchText() }}" data-status="{{ $car->status->value }}"
                                x-show="match($el.dataset.search, $el.dataset.status)"
                                class="group transition hover:bg-graphite-800/50">
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

            {{-- Geen resultaat voor de huidige filter --}}
            <div x-show="matched === 0" x-cloak class="flex flex-col items-center justify-center px-6 py-20 text-center">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-hairline text-cream/40"><x-icon name="search" class="h-6 w-6" /></span>
                <h3 class="mt-5 font-display text-xl font-semibold text-cream">Geen auto's gevonden</h3>
                <p class="mt-2 max-w-sm text-sm text-cream/65">Geen resultaten voor deze zoekopdracht of status.</p>
                <button type="button" @click="reset()" class="btn btn-ghost mt-6">Filter wissen</button>
            </div>
        </div>
    @endif
</x-layouts.admin>
