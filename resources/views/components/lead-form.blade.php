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
    {{-- Geen kader om het formulier: de velden zelf zijn al omlijnd. --}}
    <div>
        @php
            // Demo zonder balk lijkt de echte zaak: zeg vóór én na het versturen dat het niet aankomt.
            $demoNote = config('brand.demo') ? 'Aanvragen via dit formulier komen niet aan: dit is een voorbeeldwebsite. Bel of app Autobedrijf Rijswijk, of ga naar de officiële site.' : null;
            $reachDealer = '<a href="tel:' . e(config('brand.contact.phone_href')) . '" class="font-medium text-cream underline underline-offset-2 hover:text-brass-300">' . e(config('brand.contact.phone')) . '</a> · '
                . '<a href="https://wa.me/' . e(preg_replace('/\D/', '', (string) config('brand.contact.whatsapp'))) . '" target="_blank" rel="noopener" class="font-medium text-cream underline underline-offset-2 hover:text-brass-300">WhatsApp</a> · '
                . '<a href="https://autobedrijfrijswijk.nl" class="font-medium text-cream underline underline-offset-2 hover:text-brass-300">autobedrijfrijswijk.nl</a>';
        @endphp
        @if (session('lead_demo'))
            <div class="flex flex-col items-center gap-3 py-8 text-center" role="status">
                <h2 class="font-display text-2xl font-bold text-cream">Niet verstuurd</h2>
                <p class="max-w-md text-sm leading-relaxed text-cream/75">{{ $demoNote }}</p>
                <p class="text-sm">{!! $reachDealer !!}</p>
            </div>
        @elseif (session('lead_sent'))
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

            @if ($demoNote)
                {{-- Vóór het invullen, niet pas erna: niemand hoort tijd te verliezen aan een formulier dat niet aankomt. --}}
                <div class="mb-6 rounded-[4px] border border-brass-500/40 bg-brass-500/[0.08] p-4 text-sm leading-relaxed text-cream/85" role="note">
                    <p>{{ $demoNote }}</p>
                    <p class="mt-1.5">{!! $reachDealer !!}</p>
                </div>
            @endif

            @if ($errors->any())
                <div class="mb-6 rounded-[4px] border border-rose-500/30 bg-rose-500/10 p-4">
                    <p class="flex items-center gap-2 text-sm font-medium text-rose-200">
                        <x-icon name="x" class="h-4 w-4" /> Controleer de gemarkeerde velden.
                    </p>
                </div>
            @endif

            <form method="POST" action="{{ route('leads.store') }}" data-fab-avoid
                  x-data="{ busy: false }" x-init="$store.lead.type = @js($initialType)"
                  @submit="busy = true"
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
                    <label class="field-label" for="lead-phone">Telefoonnummer <span class="text-cream/60">(optioneel)</span></label>
                    <input id="lead-phone" @error('phone') aria-invalid="true" aria-describedby="lead-phone-error" @enderror name="phone" value="{{ old('phone') }}" class="field-input" autocomplete="tel" inputmode="tel">
                    @error('phone') <p id="lead-phone-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2">
                    <label class="field-label" for="lead-message">Bericht <span class="text-cream/60">(optioneel)</span></label>
                    <textarea id="lead-message" @error('message') aria-invalid="true" aria-describedby="lead-message-error" @enderror name="message" rows="4" class="field-input">{{ old('message', $defaultMessage) }}</textarea>
                    @error('message') <p id="lead-message-error" class="field-hint text-rose-300">{{ $message }}</p> @enderror
                </div>

                <div class="sm:col-span-2 flex flex-wrap items-center gap-4">
                    {{-- Na klikken: niet nog eens versturen, en laten zien dát er iets gebeurt. --}}
                    <button type="submit" class="btn btn-primary" :disabled="busy" :aria-busy="busy">
                        <span x-show="!busy" class="inline-flex items-center gap-2">Verstuur aanvraag <x-icon name="arrow-right" class="h-4 w-4" /></span>
                        <span x-show="busy" x-cloak class="inline-flex items-center gap-2">
                            <span class="h-4 w-4 animate-spin rounded-full border-2 border-cream/30 border-t-cream motion-reduce:animate-none" aria-hidden="true"></span>
                            Versturen…
                        </span>
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
