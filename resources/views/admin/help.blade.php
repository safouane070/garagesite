<x-layouts.admin title="Hulp">
    @php
        $sync = filled(config('brand.dealer_site_url'));
        $steps = [
            [
                'icon' => 'plus', 'title' => 'Een auto toevoegen',
                'items' => array_filter([
                    $sync ? 'Zet je auto’s op de dealersite zoals je gewend bent? Dan verschijnen ze hier elke ochtend om 06:00 vanzelf, met foto’s, prijs en uitrusting. Je hoeft niets dubbel in te voeren.' : null,
                    'Zelf toevoegen: Voorraad → “Nieuwe auto”. Vul merk, model, bouwjaar, prijs en kilometerstand in en kies de uitrusting uit de lijst.',
                    'Laat je de beschrijving leeg, dan maakt de site er zelf een op basis van de gegevens.',
                ]),
            ],
            [
                'icon' => 'image', 'title' => 'Foto’s',
                'items' => [
                    'Foto’s mogen rechtstreeks van je telefoon: ze worden automatisch verkleind en rechtop gezet.',
                    'De eerste foto is de omslag (die zie je in het aanbod). Met ← en → verander je de volgorde, met “Omslag” zet je een foto meteen vooraan.',
                ],
            ],
            [
                'icon' => 'tag', 'title' => 'Verkocht of gereserveerd',
                'items' => array_filter([
                    'Gebruik de status in het overzicht: Beschikbaar, Gereserveerd of Verkocht. Een verkochte auto verdwijnt direct uit het aanbod.',
                    $sync ? 'Belangrijk: verwijder een auto van de dealersite níet, maar zet hem op Verkocht. Staat hij nog op de dealersite, dan komt een verwijderde auto de volgende ochtend terug.' : null,
                    $sync ? 'Verdwijnt een auto van de dealersite, dan zet de site hem vanzelf op Verkocht.' : null,
                    'Prijs aangepast? Wijzig hem hier' . ($sync ? ', of op de dealersite: bij de volgende sync wint de laatste wijziging op de dealersite.' : '.'),
                ]),
            ],
            [
                'icon' => 'inbox', 'title' => 'Aanvragen',
                'items' => [
                    'Elke aanvraag (proefrit, bezichtiging, inruil, vraag) komt per mail binnen én staat onder Aanvragen. Het rode getal is wat nog open staat.',
                    'De klant krijgt automatisch een bevestiging dat de aanvraag binnen is. Beantwoorden met de knop opent je mail met het goede onderwerp.',
                    'Klaar? “Markeer als afgehandeld”. Afgehandelde aanvragen worden na ' . \App\Models\Lead::KEEP_HANDLED_MONTHS . ' maanden automatisch gewist (privacywet).',
                ],
            ],
            [
                'icon' => 'star', 'title' => 'Homepage en inzicht',
                'items' => [
                    '“Uitlichten” bij een auto zet hem prominent op de homepage.',
                    'In het voorraadoverzicht zie je per auto hoe vaak hij bekeken is en hoeveel aanvragen hij kreeg. Veel bekeken en geen aanvragen? Kijk eens naar prijs of foto’s.',
                ],
            ],
            [
                'icon' => 'user', 'title' => 'Inloggen en wachtwoord',
                'items' => [
                    'Wachtwoord wijzigen: Profiel. Kies een lang wachtwoord dat je nergens anders gebruikt.',
                    'Wachtwoord kwijt? Op het inlogscherm “Wachtwoord vergeten”: je krijgt een link op ' . auth()->user()?->email . '.',
                ],
            ],
        ];
    @endphp

    <div>
        <p class="kicker">Beheer</p>
        <h1 class="mt-2 font-display text-3xl font-bold tracking-tight text-cream">Hulp</h1>
        <p class="mt-1 text-sm text-cream/70">Alles wat je nodig hebt om de site bij te houden, in het kort.</p>
    </div>

    <div class="mt-8 grid gap-4 md:grid-cols-2">
        @foreach ($steps as $step)
            <section class="rounded-[4px] border border-hairline bg-graphite-800/40 p-5">
                <h2 class="flex items-center gap-2.5 font-display text-lg font-semibold text-cream">
                    <x-icon name="{{ $step['icon'] }}" class="h-5 w-5 text-brass-400" /> {{ $step['title'] }}
                </h2>
                <ul class="mt-3 list-disc space-y-2 pl-5 text-sm leading-relaxed text-cream/75">
                    @foreach ($step['items'] as $item)
                        <li>{{ $item }}</li>
                    @endforeach
                </ul>
            </section>
        @endforeach
    </div>

    <p class="mt-8 text-sm text-cream/60">
        Klopt er iets niet op de site? Bij een fout krijgt de beheerder automatisch een mail; je hoeft niets te doen.
    </p>
</x-layouts.admin>
