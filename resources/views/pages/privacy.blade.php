<x-layouts.public title="Privacybeleid"
    description="Hoe Autobedrijf Rijswijk omgaat met je persoonsgegevens.">

    <x-page-hero kicker="Juridisch" title="Privacybeleid" />

    <section class="container-x py-12 lg:py-16">
        <div class="mx-auto max-w-2xl space-y-6 leading-relaxed text-cream/75">
            <p>
                We gaan zorgvuldig om met je persoonsgegevens en gebruiken de gegevens die je via een
                formulier achterlaat uitsluitend om je aanvraag te behandelen. De volledige, actuele tekst
                van ons privacybeleid vind je hier:
            </p>
            <a href="{{ config('brand.legal.privacy_url') }}" target="_blank" rel="noopener" class="btn btn-primary">
                Lees ons privacybeleid <x-icon name="arrow-up-right" class="h-4 w-4" />
            </a>
            <p class="text-sm text-cream/55">
                Vragen over je gegevens? Mail
                <a href="mailto:{{ config('brand.contact.email') }}" class="text-brass-300 hover:underline">{{ config('brand.contact.email') }}</a>.
            </p>
        </div>
    </section>
</x-layouts.public>
