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
            <div class="rounded-[4px] border border-hairline bg-graphite-700/50 p-5 text-sm">
                <p class="font-medium text-cream">Specifiek voor deze website</p>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-cream/75">
                    <li>Aanvragen via een formulier bewaren we tot {{ \App\Models\Lead::KEEP_HANDLED_MONTHS }} maanden na afhandeling, en nooit langer dan {{ \App\Models\Lead::KEEP_MAX_MONTHS }} maanden. Daarna worden ze automatisch gewist.</li>
                    <li>Lettertypen staan op onze eigen server; er gaan geen gegevens naar Google Fonts.</li>
                    <li>De kaart (Google Maps) en het lease-aanbod (FinancialLease.nl) laden pas nadat jij daarop klikt.</li>
                    <li>We tellen hoe vaak een auto bekeken wordt, zonder cookies en zonder je IP-adres op te slaan.</li>
                    <li>Doe je een aanvraag, dan bewaren we daarbij via welke website je bij ons kwam (bv. Google of Marktplaats) en op welke pagina's, zodat we weten welke kanalen werken. Dit wordt samen met je aanvraag gewist.</li>
                    @if (config('brand.analytics.plausible_domain'))
                        <li>Bezoekersaantallen meten we met Plausible: zonder cookies, zonder persoonsgegevens en zonder je te volgen over andere websites.</li>
                    @endif
                </ul>
            </div>
            <a href="{{ asset('docs/privacybeleid.pdf') }}" target="_blank" rel="noopener" class="btn btn-primary">
                Lees ons privacybeleid <x-icon name="arrow-up-right" class="h-4 w-4" />
            </a>
            <p class="text-sm text-cream/55">
                Vragen over je gegevens? Mail
                <a href="mailto:{{ config('brand.contact.email') }}" class="text-brass-300 hover:underline">{{ config('brand.contact.email') }}</a>.
            </p>
        </div>
    </section>
</x-layouts.public>
