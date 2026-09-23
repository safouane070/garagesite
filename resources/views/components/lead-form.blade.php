@props([
    'car' => null,        // App\Models\Car of null (algemeen contact)
    'type' => 'vraag',    // voorgeselecteerd onderwerp
    'title' => 'Interesse of een vraag?',
    'intro' => 'Laat je gegevens achter, dan nemen we snel contact met je op.',
])

@php
    // Onderwerp voorselecteren: eerder ingevuld (na een fout) > ?onderwerp=… in de
    // link (bv. vanaf de lease-pagina) > standaard van deze plek op de site.
    $requested = request()->query('onderwerp');
    $initialType = old('type', is_string($requested) && array_key_exists($requested, \App\Models\Lead::TYPES) ? $requested : $type);

    $defaultMessage = $car
        ? 'Ik heb interesse in de ' . $car->title() . '. Kunnen jullie mij meer informatie geven?'
        : '';
@endphp

<section id="contact" {{ $attributes->merge(['class' => 'scroll-mt-28']) }}>
    <div class="surface p-6 sm:p-8">
        @if (session('lead_sent'))
            {{-- Bevestiging na verzenden --}}
            <div class="flex flex-col items-center gap-4 py-8 text-center" role="status">
                <span class="flex h-14 w-14 items-center justify-center rounded-full border border-brass-500/40 text-brass-400">
                    <x-icon name="check" class="h-7 w-7" />
                </span>
                <h2 class="font-display text-2xl font-bold text-cream">Bedankt, we hebben je aanvraag ontvangen</h2>
                <p class="max-w-md text-sm leading-relaxed text-cream/65">
                    We nemen zo snel mogelijk contact met je op. Liever direct?
                    Bel <a href="tel:{{ config('brand.contact.phone_href') }}" class="text-brass-300 underline-offset-2 hover:underline">{{ config('brand.contact.phone') }}</a>.
                </p>
            </div>
        @else
            <div class="mb-6">
                <h2 class="font-display text-2xl font-bold text-cream">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-cream/65">{{ $intro }}</p>
            </div>

            @if ($errors->any())
                <div class="mb-6 rounded-[4px] border border-rose-500/30 bg-rose-500/10 p-4">
                    <p class="flex items-center gap-2 text-sm font-medium text-rose-200">
                        <x-icon name="x" class="h-4 w-4" /> Controleer de gemarkeerde velden.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('leads.store') }}"
                  x-data x-init="$store.lead.type = @js($initialType)"
                  class="grid gap-5 sm:grid-cols-2">
                @csrf
                @if ($car)
                    <input type="hidden" name="car_id" value="{{ $car->id }}">
                @endif

                {{-- Honeypot: verborgen voor mensen, aantrekkelijk voor bots. --}}
                <div class="hidden" aria-hidden="true">
                    <label>Laat dit veld leeg
                        <input type="text" name="website" tabindex="-1" autocomplete="off">
                    </label>
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="lead-type">Onderwerp</label>
                    <select id="lead-type" name="type" x-model="$store.lead.type" class="field-input">
                        @foreach (\App\Models\Lead::TYPES as $value => $label)
                            <option value="{{ $value }}" @selected($initialType === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('type') <p class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                {{-- Voorkeursdatum: alleen bij afspraak-onderwerpen (proefrit/bezichtiging). --}}
                <div class="sm:col-span-2" x-show="{{ \Illuminate\Support\Js::from(\App\Models\Lead::DATE_TYPES) }}.includes($store.lead.type)" x-cloak>
                    <label class="field-label" for="lead-date">Voorkeursdatum</label>
                    <input id="lead-date" @error('preferred_date') aria-invalid="true" aria-describedby="lead-date-error" @enderror name="preferred_date" type="date" value="{{ old('preferred_date') }}"
                           min="{{ now()->toDateString() }}" class="field-input"
                           :disabled="! {{ \Illuminate\Support\Js::from(\App\Models\Lead::DATE_TYPES) }}.includes($store.lead.type)">
                    @error('preferred_date') <p id="lead-date-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="lead-name">Naam</label>
                    <input id="lead-name" @error('name') aria-invalid="true" aria-describedby="lead-name-error" @enderror name="name" value="{{ old('name') }}" class="field-input" required autocomplete="name">
                    @error('name') <p id="lead-name-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="field-label" for="lead-email">E-mailadres</label>
                    <input id="lead-email" @error('email') aria-invalid="true" aria-describedby="lead-email-error" @enderror name="email" type="email" value="{{ old('email') }}" class="field-input" required autocomplete="email">
                    @error('email') <p id="lead-email-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="lead-phone">Telefoonnummer <span class="text-cream/30">(optioneel)</span></label>
                    <input id="lead-phone" @error('phone') aria-invalid="true" aria-describedby="lead-phone-error" @enderror name="phone" value="{{ old('phone') }}" class="field-input" autocomplete="tel" inputmode="tel">
                    @error('phone') <p id="lead-phone-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="lead-message">Bericht <span class="text-cream/30">(optioneel)</span></label>
                    <textarea id="lead-message" @error('message') aria-invalid="true" aria-describedby="lead-message-error" @enderror name="message" rows="4" class="field-input">{{ old('message', $defaultMessage) }}</textarea>
                    @error('message') <p id="lead-message-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
                    <button type="submit" class="btn btn-primary">
                        Verstuur aanvraag <x-icon name="arrow-right" class="h-4 w-4" />
                    </button>
                    <p class="text-xs text-cream/60">
                        We gebruiken je gegevens alleen om te reageren op je aanvraag.
                        <a href="{{ route('privacy') }}" class="underline underline-offset-2 hover:text-brass-300">Privacybeleid</a>
                    </p>
                </div>
            </form>
        @endif
    </div>
</section>

@once
    <script>
        document.addEventListener('alpine:init', () => {
            if (! Alpine.store('lead')) {
                Alpine.store('lead', { type: 'vraag' });
            }
        });
    </script>
@endonce
