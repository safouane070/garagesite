<x-layouts.public title="Algemene voorwaarden"
    description="De algemene voorwaarden van Autobedrijf Rijswijk.">

    <x-page-hero kicker="Juridisch" title="Algemene voorwaarden" />

    <section class="container-x py-12 lg:py-16">
        <div class="mx-auto max-w-2xl space-y-6 leading-relaxed text-cream/75">
            <p>
                Op al onze aanbiedingen en overeenkomsten zijn onze algemene voorwaarden van toepassing,
                inclusief de geldende BOVAG-voorwaarden. Je vindt de volledige, actuele tekst hier (pdf):
            </p>
            <div class="flex flex-wrap gap-3">
                <a href="{{ asset('docs/voorwaarden-particulier.pdf') }}" target="_blank" rel="noopener" class="btn btn-primary">
                    Voorwaarden particulier <x-icon name="arrow-up-right" class="h-4 w-4" />
                </a>
                <a href="{{ asset('docs/voorwaarden-zakelijk.pdf') }}" target="_blank" rel="noopener" class="btn btn-outline">
                    Voorwaarden zakelijk <x-icon name="arrow-up-right" class="h-4 w-4" />
                </a>
            </div>
            <p class="text-sm text-cream/55">
                Vragen hierover? Neem gerust <a href="{{ route('contact') }}" class="text-brass-300 hover:underline">contact</a> op.
            </p>
        </div>
    </section>
</x-layouts.public>
