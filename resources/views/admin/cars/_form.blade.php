@php
    $isEdit = $car->exists;
    $action = $isEdit ? route('admin.cars.update', $car) : route('admin.cars.store');
    // Echte serverlimieten (verschillen per hosting), zodat de upload vóór het
    // versturen al kan waarschuwen i.p.v. een foutpagina na het versturen.
    $maxFileBytes = min(12 * 1024 * 1024, ini_parse_quantity(ini_get('upload_max_filesize')));
    $maxPostBytes = ini_parse_quantity(ini_get('post_max_size'));
@endphp

{{-- Validatie-samenvatting --}}
@if ($errors->any())
    <div class="mb-6 rounded-[4px] border border-rose-500/30 bg-rose-500/10 p-4">
        <p class="flex items-center gap-2 text-sm font-medium text-rose-200"><x-icon name="x" class="h-4 w-4" /> Controleer de gemarkeerde velden.</p>
    </div>
@endif

<form method="POST" action="{{ $action }}" enctype="multipart/form-data" class="grid gap-8 lg:grid-cols-12"
      x-data="{ uploadProblem: '' }"
      @submit="if (uploadProblem) { $event.preventDefault(); document.getElementById('photo-upload').scrollIntoView({ behavior: 'smooth', block: 'center' }); }">
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
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/65" />
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
            <p class="mt-1 text-sm text-cream/60">Alle velden zijn optioneel. Laat leeg wat niet van toepassing is.</p>
            <div class="mt-5 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach (\App\Models\Car::SPEC_FIELDS as $key => $label)
                    <div>
                        <label class="field-label" for="spec_{{ $key }}">{{ $label }}</label>
                        <input id="spec_{{ $key }}" name="specs[{{ $key }}]" value="{{ old('specs.'.$key, $car->specs[$key] ?? '') }}" class="field-input">
                    </div>
                @endforeach
            </div>
        </section>

        {{-- Uitrusting / opties: aanvinken uit de bestaande lijst i.p.v. typen. --}}
        @php
            $selectedOptions = array_values(old('options', $car->options ?? []));
            // Bekende opties (frequentie-gesorteerd) + eigen selecties die er nog
            // niet in staan vooraan, zodat alles zichtbaar en aan te vinken is.
            $allOptions = array_values(array_unique(array_merge($selectedOptions, \App\Models\Car::knownOptions())));
        @endphp
        <section class="surface p-6"
                 x-data="{
                    q: '',
                    all: @js($allOptions),
                    selected: @js($selectedOptions),
                    custom: '',
                    get filtered() {
                        const q = this.q.trim().toLowerCase();
                        return q ? this.all.filter(o => o.toLowerCase().includes(q)) : this.all;
                    },
                    addCustom() {
                        const v = this.custom.trim();
                        if (v) {
                            if (!this.all.includes(v)) this.all.unshift(v);
                            if (!this.selected.includes(v)) this.selected.push(v);
                        }
                        this.custom = '';
                        this.q = '';
                    }
                 }">
            <div class="flex flex-wrap items-center justify-between gap-2">
                <h2 class="font-display text-lg font-semibold text-cream">Uitrusting</h2>
                <span class="font-mono text-xs uppercase tracking-wider text-cream/60">
                    <span x-text="selected.length">0</span> aangevinkt
                </span>
            </div>
            <p class="mt-1 text-sm text-cream/60">Vink de aanwezige opties aan. Staat een optie er niet bij? Voeg 'm onderaan toe.</p>

            {{-- Zoeken --}}
            <div class="relative mt-4">
                <x-icon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/65" />
                <input type="text" x-model="q" placeholder="Zoek een optie…" aria-label="Zoek een optie"
                       class="field-input pl-9" @keydown.enter.prevent>
            </div>

            {{-- Aanvink-lijst --}}
            <div class="mt-4 max-h-80 overflow-y-auto rounded-[4px] border border-hairline bg-graphite-800 p-2 [scrollbar-width:thin]">
                <div class="grid gap-x-4 sm:grid-cols-2">
                    <template x-for="opt in filtered" :key="opt">
                        <label class="flex cursor-pointer items-center gap-2.5 rounded-[3px] px-2 py-1.5 text-sm text-cream/85 transition hover:bg-graphite-700">
                            <input type="checkbox" name="options[]" :value="opt" x-model="selected"
                                   class="h-4 w-4 shrink-0 rounded border-hairline bg-graphite-700 text-brass-500 focus:ring-brass-500/40">
                            <span x-text="opt"></span>
                        </label>
                    </template>
                    <p x-show="!filtered.length" class="px-2 py-3 text-sm text-cream/60">Geen optie gevonden voor "<span x-text="q"></span>".</p>
                </div>
            </div>

            {{-- Eigen optie toevoegen --}}
            <div class="mt-3 flex gap-2">
                <input type="text" x-model="custom" placeholder="Eigen optie toevoegen…" aria-label="Eigen optie toevoegen"
                       class="field-input" @keydown.enter.prevent="addCustom">
                <button type="button" @click="addCustom"
                        class="btn btn-outline shrink-0" x-bind:disabled="!custom.trim()">
                    <x-icon name="plus" class="h-4 w-4" /> Toevoegen
                </button>
            </div>

            @error('options') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
            @error('options.*') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
        </section>

        {{-- Foto's uploaden --}}
        <section class="surface p-6">
            <h2 class="font-display text-lg font-semibold text-cream">Foto's toevoegen</h2>
            <div class="mt-4" id="photo-upload"
                 x-data="{
                    files: [],
                    maxFile: {{ $maxFileBytes }},
                    maxPost: {{ $maxPostBytes }},
                    pick(list) { this.files = Array.from(list).map(f => ({ name: f.name, size: f.size, url: URL.createObjectURL(f) })); },
                    mb(b) { return (b / 1048576).toLocaleString('nl-NL', { maximumFractionDigits: 1 }) + ' MB'; },
                    get total() { return this.files.reduce((sum, f) => sum + f.size, 0); },
                    get problem() {
                        const big = this.files.filter(f => f.size > this.maxFile);
                        if (big.length) return `${big.length === 1 ? 'Eén foto is' : big.length + ' foto’s zijn'} te groot (max ${this.mb(this.maxFile)} per foto).`;
                        if (this.files.length > 12) return 'Maximaal 12 foto’s per keer. Upload de rest daarna.';
                        if (this.total > this.maxPost * 0.95) return `Samen ${this.mb(this.total)}: te veel in één keer (max ${this.mb(this.maxPost)}). Upload ze in twee keer.`;
                        return '';
                    },
                 }"
                 x-effect="uploadProblem = problem">
                <label @dragover.prevent @drop.prevent="$refs.input.files = $event.dataTransfer.files; $refs.input.dispatchEvent(new Event('change'))"
                       class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-[4px] border border-dashed border-hairline bg-graphite-800 px-6 py-10 text-center transition hover:border-brass-500/50">
                    <x-icon name="image" class="h-8 w-8 text-cream/65" />
                    <span class="text-sm text-cream/70">Sleep foto's hierheen of <span class="text-brass-300">blader</span></span>
                    <span class="font-mono text-[0.7rem] uppercase tracking-wider text-cream/70">
                        JPG, PNG of WebP · max {{ round($maxFileBytes / 1048576) }} MB per foto · wordt automatisch verkleind
                    </span>
                    <input x-ref="input" type="file" name="images[]" multiple accept="image/jpeg,image/png,image/webp" class="sr-only"
                           @change="pick($event.target.files)">
                </label>

                <p x-show="problem" x-cloak x-text="problem" role="alert"
                   class="mt-3 rounded-[3px] border border-rose-500/30 bg-rose-500/10 px-3 py-2 text-sm text-rose-200"></p>

                <div x-show="files.length" x-cloak class="mt-4 grid grid-cols-3 gap-3 sm:grid-cols-4">
                    <template x-for="(f, idx) in files" :key="idx">
                        <figure class="overflow-hidden rounded-[3px] border"
                                :class="f.size > maxFile ? 'border-rose-500' : 'border-hairline'">
                            <img :src="f.url" alt="" class="aspect-[4/3] h-auto w-full object-cover">
                            <figcaption class="truncate px-2 py-1 font-mono text-[0.65rem]"
                                        :class="f.size > maxFile ? 'text-rose-300' : 'text-cream/65'"
                                        x-text="mb(f.size)"></figcaption>
                        </figure>
                    </template>
                </div>
                @error('images') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
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
                    <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/65" />
                </div>
            </div>

            <label class="mt-5 flex cursor-pointer items-start gap-3">
                <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $car->is_featured))
                       class="mt-0.5 h-4 w-4 rounded-[3px] border-graphite-500 bg-graphite-800 text-brass-500 focus:ring-brass-500 focus:ring-offset-ink">
                <span>
                    <span class="block text-sm font-medium text-cream">Uitlichten</span>
                    <span class="block text-xs text-cream/60">Toon deze auto prominent op de homepage.</span>
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

{{-- Bestaande foto's beheren (alleen bij bewerken). De eerste foto is de omslag. --}}
@if ($isEdit && $car->images->isNotEmpty())
    <section class="surface mt-8 p-6">
        <h2 class="font-display text-lg font-semibold text-cream">Huidige foto's</h2>
        <p class="mt-1 text-sm text-cream/60">De eerste foto is de omslag. Verschuif met de pijltjes of zet een foto direct vooraan.</p>
        <ol class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($car->images as $image)
                @php $first = $loop->first; $last = $loop->last; @endphp
                <li class="overflow-hidden rounded-[4px] border {{ $first ? 'border-brass-500' : 'border-hairline' }}">
                    <div class="relative aspect-[4/3] overflow-hidden bg-graphite-800">
                        <img src="{{ $image->thumbUrl() }}" alt="Foto {{ $loop->iteration }}" loading="lazy" class="h-full w-full object-cover">
                        <span class="absolute left-2 top-2 rounded-[3px] px-2 py-0.5 font-mono text-[0.65rem] uppercase tracking-wider
                                     {{ $first ? 'bg-brass-500 text-cream' : 'bg-scrim/70 text-onscrim' }}">
                            {{ $first ? 'Omslag' : $loop->iteration }}
                        </span>
                    </div>
                    {{-- Altijd zichtbaar: hover bestaat niet op telefoon/tablet --}}
                    <div class="flex items-center gap-1 border-t border-hairline bg-graphite-800 px-1.5 py-1.5">
                        <form method="POST" action="{{ route('admin.cars.images.move', [$car, $image]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direction" value="left">
                            <button type="submit" @disabled($first) aria-label="Foto {{ $loop->iteration }} naar voren"
                                    class="rounded-[3px] p-1.5 text-cream/70 hover:bg-white/5 hover:text-cream disabled:opacity-25"><x-icon name="chevron-left" class="h-4 w-4" /></button>
                        </form>
                        <form method="POST" action="{{ route('admin.cars.images.move', [$car, $image]) }}">
                            @csrf @method('PATCH')
                            <input type="hidden" name="direction" value="right">
                            <button type="submit" @disabled($last) aria-label="Foto {{ $loop->iteration }} naar achteren"
                                    class="rounded-[3px] p-1.5 text-cream/70 hover:bg-white/5 hover:text-cream disabled:opacity-25"><x-icon name="chevron-right" class="h-4 w-4" /></button>
                        </form>
                        @unless ($first)
                            <form method="POST" action="{{ route('admin.cars.images.primary', [$car, $image]) }}">
                                @csrf @method('PATCH')
                                <button type="submit" class="px-1.5 py-1 font-mono text-[0.65rem] uppercase tracking-wider text-brass-300 hover:text-brass-200">Omslag</button>
                            </form>
                        @endunless
                        <form method="POST" action="{{ route('admin.cars.images.destroy', [$car, $image]) }}" class="ml-auto"
                              onsubmit="return confirm('Deze foto verwijderen?');">
                            @csrf @method('DELETE')
                            <button type="submit" aria-label="Foto {{ $loop->iteration }} verwijderen" class="rounded-[3px] p-1.5 text-rose-300 hover:bg-rose-500/10 hover:text-rose-200"><x-icon name="trash" class="h-4 w-4" /></button>
                        </form>
                    </div>
                </li>
            @endforeach
        </ol>
    </section>
@endif
