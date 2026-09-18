@php
    $isEdit = $car->exists;
    $action = $isEdit ? route('admin.cars.update', $car) : route('admin.cars.store');
@endphp

{{-- Validatie-samenvatting --}}
@if ($errors->any())
    <div class="mb-6 rounded-[4px] border border-rose-500/30 bg-rose-500/10 p-4">
        <p class="flex items-center gap-2 text-sm font-medium text-rose-200"><x-icon name="x" class="h-4 w-4" /> Controleer de gemarkeerde velden.</p>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-12">
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    {{-- ══ Hoofdkolom ══ --}}
    <div class="space-y-8 lg:col-span-8">
        {{-- Kerngegevens --}}
        <section class="surface p-6">
            <h2 class="font-display text-lg font-semibold text-cream">Kerngegevens</h2>
            <div class="mt-5 grid gap-5 sm:grid-cols-2">
                <div>
                    <label class="field-label" for="brand">Merk</label>
                    <input id="brand" name="brand" value="{{ old('brand', $car->brand) }}" class="field-input" required>
                    @error('brand') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="model">Model</label>
                    <input id="model" name="model" value="{{ old('model', $car->model) }}" class="field-input" required>
                    @error('model') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div class="sm:col-span-2">
                    <label class="field-label" for="variant">Uitvoering <span class="text-cream/30">(optioneel)</span></label>
                    <input id="variant" name="variant" value="{{ old('variant', $car->variant) }}" class="field-input" placeholder="bv. M Sport">
                    @error('variant') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="year">Bouwjaar</label>
                    <input id="year" name="year" type="number" value="{{ old('year', $car->year) }}" class="field-input tabular" min="1950" max="{{ date('Y') }}" required>
                    @error('year') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="price">Prijs (€)</label>
                    <input id="price" name="price" type="number" step="1" value="{{ old('price', $car->price ? (int) $car->price : '') }}" class="field-input tabular" min="0" required>
                    @error('price') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="mileage">Kilometerstand</label>
                    <input id="mileage" name="mileage" type="number" value="{{ old('mileage', $car->mileage) }}" class="field-input tabular" min="0" required>
                    @error('mileage') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label class="field-label" for="color">Kleur</label>
                    <input id="color" name="color" value="{{ old('color', $car->color) }}" class="field-input" required>
                    @error('color') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                @php
                    $selects = [
                        ['name' => 'fuel_type', 'label' => 'Brandstof', 'options' => \App\Models\Car::FUEL_TYPES, 'value' => old('fuel_type', $car->fuel_type), 'placeholder' => null],
                        ['name' => 'transmission', 'label' => 'Transmissie', 'options' => \App\Models\Car::TRANSMISSIONS, 'value' => old('transmission', $car->transmission), 'placeholder' => null],
                        ['name' => 'body_type', 'label' => 'Carrosserie', 'options' => \App\Models\Car::BODY_TYPES, 'value' => old('body_type', $car->body_type), 'placeholder' => '·'],
                    ];
                @endphp
                @foreach ($selects as $s)
                    <div>
                        <label class="field-label" for="{{ $s['name'] }}">{{ $s['label'] }}</label>
                        <div class="relative">
                            <select id="{{ $s['name'] }}" name="{{ $s['name'] }}" class="field-input appearance-none pr-10">
                                @if ($s['placeholder'])<option value="">{{ $s['placeholder'] }}</option>@endif
                                @foreach ($s['options'] as $opt)
                                    <option value="{{ $opt }}" @selected($s['value'] === $opt)>{{ $opt }}</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/40" />
                        </div>
                        @error($s['name']) <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                    </div>
                @endforeach
            </div>

            <div class="mt-5">
                <label class="field-label" for="description">Beschrijving</label>
                <textarea id="description" name="description" rows="5" class="field-input" placeholder="Vertel het verhaal van deze auto…">{{ old('description', $car->description) }}</textarea>
                @error('description') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- Specificaties --}}
        <section class="surface p-6">
            <h2 class="font-display text-lg font-semibold text-cream">Specificaties</h2>
            <p class="mt-1 text-sm text-cream/45">Alle velden zijn optioneel. Laat leeg wat niet van toepassing is.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Models\Car::SPEC_FIELDS as $key => $label)
                    <div>
                        <label class="field-label" for="spec_{{ $key }}">{{ $label }}</label>
                        <input id="spec_{{ $key }}" name="specs[{{ $key }}]" value="{{ old('specs.'.$key, $car->specs[$key] ?? '') }}" class="field-input">
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Uitrusting / opties --}}
        <section class="surface p-6">
            <h2 class="font-display text-lg font-semibold text-cream">Uitrusting</h2>
            <p class="mt-1 text-sm text-cream/45">Eén optie per regel (bv. Panoramadak, Navigatiesysteem, Achteruitrijcamera). Verschijnt als lijst op de detailpagina.</p>
            <div class="mt-4">
                <label class="field-label sr-only" for="options">Opties</label>
                <textarea id="options" name="options" rows="8" class="field-input font-mono text-sm"
                          placeholder="Panoramadak&#10;Navigatiesysteem&#10;Adaptieve cruise control">{{ old('options', implode("\n", $car->options ?? [])) }}</textarea>
                @error('options') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
            </div>
        </section>

        {{-- Foto's uploaden --}}
        <section class="surface p-6">
            <h2 class="font-display text-lg font-semibold text-cream">Foto's toevoegen</h2>
            <div class="mt-4" x-data="{ files: [] }">
                <label @dragover.prevent @drop.prevent="$refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change'))"
                       class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-[4px] border border-dashed border-hairline bg-graphite-800 px-6 py-10 text-center transition hover:border-brass-500/50">
                    <x-icon name="image" class="h-8 w-8 text-cream/40" />
                    <span class="text-sm text-cream/70">Sleep foto's hierheen of <span class="text-brass-300">blader</span></span>
                    <span class="font-mono text-[0.7rem] uppercase tracking-wider text-cream/70">JPG, PNG of WebP · max 4MB</span>
                    <input x-ref="input" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only"
                           @change="files = Array.from($event.target.files).map(f => ({ name: f.name, url: URL.createObjectURL(f) }))">
                </label>

                <div x-show="files.length" x-cloak class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    <template x-for="(f, idx) in files" :key="idx">
                        <div class="aspect-[4/3] overflow-hidden rounded-[3px] border border-hairline">
                            <img :src="f.url" alt="" class="h-full w-full object-cover">
                        </div>
                    </template>
                </div>
                @error('images.*') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
            </div>
        </section>
    </div>

    {{-- ══ Zijkolom ══ --}}
    <div class="space-y-8 lg:col-span-4">
        <section class="surface p-6 lg:sticky lg:top-24">
            <h2 class="font-display text-lg font-semibold text-cream">Publicatie</h2>

            <div class="mt-5">
                <label class="field-label" for="status">Status</label>
                <div class="relative">
                    <select id="status" name="status" class="field-input appearance-none pr-10">
                        @foreach (\App\Enums\CarStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $car->status?->value ?? 'available') === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/40" />
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $car->is_featured))
                       class="mt-0.5 h-4 w-4 rounded-[3px] border-graphite-500 bg-graphite-800 text-brass-500 focus:ring-brass-500 focus:ring-offset-ink">
                <span>
                    <span class="block text-sm font-medium text-cream">Uitlichten</span>
                    <span class="block text-xs text-cream/45">Toon deze auto prominent op de homepage.</span>
                </span>
            </label>

            <div class="mt-6 flex flex-col gap-3 border-t border-hairline pt-6">
                <button type="submit" class="btn btn-primary w-full">
                    <x-icon name="check" class="h-4 w-4" /> {{ $isEdit ? 'Wijzigingen opslaan' : 'Auto toevoegen' }}
                </button>
                <a href="{{ route('admin.dashboard') }}" class="btn btn-ghost w-full">Annuleren</a>
            </div>
        </section>
    </div>
</form>

{{-- Bestaande foto's beheren (alleen bij bewerken) --}}
@if ($isEdit && $car->images->isNotEmpty())
    <section class="surface mt-8 p-6">
        <h2 class="font-display text-lg font-semibold text-cream">Huidige foto's</h2>
        <p class="mt-1 text-sm text-cream/45">Stel de omslagfoto in of verwijder foto's.</p>
        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($car->images as $image)
                <div class="group relative overflow-hidden rounded-[4px] border {{ $image->is_primary ? 'border-brass-500' : 'border-hairline' }}">
                    <div class="aspect-[4/3] overflow-hidden bg-graphite-800">
                        <img src="{{ $image->url() }}" alt="" class="h-full w-full object-cover">
                    </div>
                    @if ($image->is_primary)
                        <span class="absolute left-2 top-2 rounded-[3px] bg-brass-500 px-2 py-0.5 font-mono text-[0.65rem] uppercase tracking-wider text-cream">Omslag</span>
                    @endif
                    <div class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-2 bg-ink/80 p-2 opacity-0 backdrop-blur-sm transition group-hover:opacity-100">
                        @unless ($image->is_primary)
                            <form method="POST" action="{{ route('admin.cars.images.primary', [$car, $image]) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="font-mono text-[0.65rem] uppercase tracking-wider text-brass-300 hover:text-brass-200">Als omslag</button>
                            </form>
                        @else
                            <span></span>
                        @endunless
                        <form method="POST" action="{{ route('admin.cars.images.destroy', [$car, $image]) }}"
                              onsubmit="return confirm('Deze foto verwijderen?');">
                            @csrf @method('DELETE')
                            <button type="submit" class="text-rose-300 hover:text-rose-200"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>
    </section>
@endif
