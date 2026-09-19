<x-layouts.public title="Contact"
    description="Bezoek de showroom van Autobedrijf Rijswijk aan de Poldermeesterstraat 16 in Rijswijk. Bel, WhatsApp of stuur een bericht — we helpen je graag.">

    <x-page-hero kicker="Contact"
        title="Kom langs of neem contact op"
        intro="Vragen over een occasion, inruil of financiering? Bel of app ons even, of loop binnen in de showroom in Rijswijk." />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-10 lg:grid-cols-2 lg:gap-14">
            {{-- Gegevens --}}
            <div>
                <h2 class="font-display text-2xl font-bold text-cream">Showroom</h2>

                <ul class="mt-6 space-y-5 text-cream/80">
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="map-pin" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-medium text-cream">Adres</p>
                            <p class="mt-0.5 text-sm text-cream/70">{{ config('brand.contact.address') }}</p>
                            <a href="https://www.google.com/maps/dir/?api=1&destination={{ urlencode(config('brand.maps.query')) }}"
                               target="_blank" rel="noopener"
                               class="mt-1 inline-flex items-center gap-1.5 text-sm text-brass-300 transition hover:text-brass-200">
                                Plan je route <x-icon name="arrow-up-right" class="h-3.5 w-3.5" />
                            </a>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="phone" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-medium text-cream">Telefonisch</p>
                            <p class="mt-0.5 text-sm">
                                <a href="tel:{{ config('brand.contact.phone_href') }}" class="text-cream/70 transition hover:text-brass-300">{{ config('brand.contact.phone') }}</a>
                                <span class="text-cream/30"> · </span>
                                <a href="tel:{{ config('brand.contact.mobile_href') }}" class="text-cream/70 transition hover:text-brass-300">{{ config('brand.contact.mobile') }}</a>
                            </p>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="mail" class="h-5 w-5" /></span>
                        <div>
                            <p class="font-medium text-cream">E-mail</p>
                            <a href="mailto:{{ config('brand.contact.email') }}" class="mt-0.5 block text-sm text-cream/70 transition hover:text-brass-300">{{ config('brand.contact.email') }}</a>
                        </div>
                    </li>
                    <li class="flex items-start gap-4">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="clock" class="h-5 w-5" /></span>
                        <div class="min-w-0">
                            <p class="font-medium text-cream">Openingstijden</p>
                            <dl class="mt-1.5 space-y-1 text-sm">
                                @foreach (config('brand.opening_hours.days') as $d)
                                    <div class="flex justify-between gap-6">
                                        <dt class="text-cream/70">{{ $d['label'] }}</dt>
                                        <dd class="font-mono text-cream/80 tabular">{{ $d['value'] }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                            <p class="mt-2 text-xs text-cream/60">{{ config('brand.opening_hours.note') }}</p>
                        </div>
                    </li>
                </ul>

                <a href="https://wa.me/{{ config('brand.contact.whatsapp') }}" target="_blank" rel="noopener"
                   class="btn btn-primary mt-8">
                    <x-icon name="phone" class="h-4 w-4" /> Direct via WhatsApp
                </a>
            </div>

            {{-- Kaart --}}
            <div class="min-h-[320px] overflow-hidden rounded-[4px] border border-hairline">
                <iframe
                    title="Route naar {{ config('app.name') }}"
                    src="https://www.google.com/maps?q={{ urlencode(config('brand.maps.query')) }}&output=embed"
                    class="h-full min-h-[320px] w-full"
                    style="border:0" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
            </div>
        </div>
    </section>

    {{-- Contactformulier --}}
    <section class="container-x border-t border-hairline py-14">
        <div class="mx-auto max-w-2xl">
            <x-lead-form type="vraag"
                title="Stuur ons een bericht"
                intro="Laat je gegevens achter, dan reageren we meestal binnen één werkdag." />
        </div>
    </section>
</x-layouts.public>
