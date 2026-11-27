<x-layouts.public title="Financial Lease"
    description="Financial lease voor ondernemers bij Autobedrijf Rijswijk: vaste maandlasten, de auto op jouw naam. Bekijk occasions met hun maandbedrag.">
    @php
        $lease = config('brand.finance.lease');
        $proposal = route('contact', ['onderwerp' => 'financiering']) . '#contact';
    @endphp

    {{-- Kop zegt wat het is en voor wie; de actie staat er meteen onder (niet pas halverwege). --}}
    <x-page-hero title="Financial lease voor ondernemers"
        intro="Spreid de aanschaf over een vaste looptijd en rijd de auto direct op jouw naam. Alleen zakelijk: voor ondernemers en zzp'ers met een KvK-inschrijving."
        image="images/site/interieur.webp" alt="Interieur van een Audi RS3 uit het aanbod">
        <div class="mt-7 flex flex-wrap gap-3">
            <a href="{{ $proposal }}" class="btn btn-primary">Vraag een voorstel aan <x-icon name="arrow-right" class="h-4 w-4" /></a>
            <a href="#lease-aanbod" class="btn btn-outline">Bekijk auto's met maandbedrag</a>
        </div>
    </x-page-hero>

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
            {{-- Voordelen --}}
            <div>
                <h2 class="font-display text-2xl font-bold text-white">Waarom financial lease?</h2>
                @php
                    $points = [
                        ['t' => 'Op jouw naam', 'd' => 'Het kenteken staat meteen op jouw naam; na de laatste termijn is de auto volledig van jou.'],
                        ['t' => 'Vaste maandlasten', 'd' => 'Een vast bedrag per maand over de hele looptijd. Overzichtelijk en voorspelbaar.'],
                        ['t' => 'Fiscaal aantrekkelijk', 'd' => 'Rente en afschrijving zijn doorgaans aftrekbaar. Vraag je adviseur wat dat voor jou betekent.'],
                        ['t' => 'Afgestemd op jou', 'd' => 'Looptijd, aanbetaling en slottermijn stem je af op je situatie en de leeftijd van de auto.'],
                    ];
                @endphp
                <ul class="mt-6 divide-y divide-hairline border-y border-hairline">
                    @foreach ($points as $p)
                        <li class="py-4">
                            <div>
                                <p class="font-medium text-white">{{ $p['t'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-cream/70">{{ $p['d'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Rekenvoorbeeld met dezelfde rekenregels als de calculator van de leasepartner
                 (Car::monthlyFrom). Voorwaarden staan erbij: rente en btw-basis horen bij een bedrag. --}}
            <div class="self-start rounded-[4px] border border-hairline bg-graphite-700/50 p-6">
                <h2 class="font-display text-lg font-semibold text-white">Rekenvoorbeeld</h2>
                <p class="mt-1 text-sm leading-relaxed text-cream/65">
                    Bijvoorbeeld: {{ $lease['months'] }} maanden, geen aanbetaling, € {{ number_format($lease['closing'], 0, ',', '.') }} slottermijn
                    en ca. {{ str_replace('.', ',', (string) $lease['rate']) }}% rente. Bedragen excl. btw.
                </p>
                <dl class="mt-4 divide-y divide-hairline border-y border-hairline">
                    @foreach ([20000, 35000, 60000] as $amount)
                        <div class="flex items-baseline justify-between py-2.5 text-sm">
                            <dt class="text-cream/70">Leasebedrag € {{ number_format($amount, 0, ',', '.') }}</dt>
                            <dd class="font-semibold text-white tabular">€ {{ number_format((new \App\Models\Car(['price' => $amount]))->monthlyFrom(), 0, ',', '.') }} p/m</dd>
                        </div>
                    @endforeach
                </dl>
                <p class="mt-3 text-xs leading-relaxed text-cream/60">
                    Indicatie volgens de calculator van onze leasepartner FinancialLease.nl. Je persoonlijke voorstel kan afwijken.
                    <a href="{{ (new \App\Models\Car(['price' => 20000]))->leaseCalculatorUrl() }}" target="_blank" rel="noopener"
                       class="text-cream/80 underline decoration-cream/25 underline-offset-4 transition-colors hover:text-brass-300">Reken zelf</a>
                </p>
                <a href="{{ $proposal }}" class="btn btn-primary mt-5">
                    Vraag een voorstel aan <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    {{-- Eigen voorraad met maandbedrag. Was een widget van de partner: op een telefoon een
         scroll-val (1500px venster op 5800px inhoud), blauwe vreemde UI, en "Bekijk deal"
         stuurde de klant naar de site van de partner in plaats van naar de dealer. --}}
    @if ($leaseCars->isNotEmpty())
        <section id="lease-aanbod" class="container-x scroll-mt-24 border-t border-hairline py-12 lg:py-16">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <h2 class="font-display text-2xl font-bold text-white">Occasions met maandbedrag</h2>
                <a href="{{ route('cars.index') }}" class="group inline-flex items-center gap-2 py-2 text-sm text-cream/70 transition-colors hover:text-brass-300">
                    Alle auto's met leaseprijs <x-icon name="arrow-right" class="h-4 w-4 transition-transform duration-200 ease-out [@media(hover:hover)]:group-hover:translate-x-0.5" />
                </a>
            </div>
            {{-- Telefoon: compacte rijen onder elkaar (zie car-card), zoals op de homepage. --}}
            <div class="mt-6 grid gap-y-5 sm:mt-8 sm:grid-cols-2 sm:gap-6 lg:grid-cols-3">
                @foreach ($leaseCars as $car)
                    <div>
                        <x-car-card :car="$car" />
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    {{-- Veelgestelde vragen. Antwoorden volgen de uitleg van de leasepartner zelf; geen eigen beloftes. --}}
    <section class="container-x border-t border-hairline py-12 lg:py-16">
        <h2 class="font-display text-2xl font-bold text-white">Veelgestelde vragen</h2>
        @php
            $faq = [
                ['q' => 'Kan ik als particulier financial lease afsluiten?',
                 'a' => 'Nee, financial lease is bedoeld voor ondernemers, starters en zzp\'ers. Ben je particulier? Bel of app ons, dan kijken we samen wat er wel kan.'],
                ['q' => 'Wat is een slottermijn?',
                 'a' => 'Het vaste restbedrag dat je aan het einde van het contract in één keer betaalt. Je lost het niet maandelijks af, maar betaalt er wel rente over. Hoe hoger de slottermijn, hoe lager je maandbedrag; vaak kies je hem rond de verwachte inruilwaarde.'],
                ['q' => 'Kan ik eerder aflossen of de auto tussentijds inruilen?',
                 'a' => 'Ja, volgens onze leasepartner kan dat. Vraag bij je voorstel naar de voorwaarden.'],
                ['q' => 'Hoe zit het met de btw?',
                 'a' => 'Je least hoogstens de prijs exclusief btw. De btw betaal je zelf of verreken je met je inruilauto; als ondernemer vorder je die terug bij de Belastingdienst.'],
            ];
        @endphp
        <div class="mt-6 max-w-3xl divide-y divide-hairline border-y border-hairline">
            @foreach ($faq as $item)
                <details class="group">
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-6 py-4 font-medium text-cream transition-colors hover:text-white [&::-webkit-details-marker]:hidden">
                        {{ $item['q'] }}
                        <x-icon name="chevron-down" class="h-4 w-4 shrink-0 text-cream/60 transition-transform duration-200 ease-out group-open:rotate-180" />
                    </summary>
                    <p class="pb-5 pr-10 text-sm leading-relaxed text-cream/70">{{ $item['a'] }}</p>
                </details>
            @endforeach
        </div>
    </section>

    {{-- Afsluiter: de pagina eindigde in de footer zonder volgende stap. --}}
    <section class="border-t border-hairline bg-graphite-800/60">
        <div class="container-x flex flex-col gap-6 py-12 lg:flex-row lg:items-center lg:justify-between lg:py-16">
            <div>
                <h2 class="font-display text-2xl font-bold text-white">Benieuwd naar jouw maandbedrag?</h2>
                <p class="mt-2 max-w-lg text-cream/70">Vraag een voorstel aan voor een auto uit ons aanbod. We reageren meestal binnen één werkdag.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ $proposal }}" class="btn btn-primary">Vraag een voorstel aan <x-icon name="arrow-right" class="h-4 w-4" /></a>
                <a href="tel:{{ config('brand.contact.phone_href') }}" class="btn btn-outline"><x-icon name="phone" class="h-4 w-4" /> Bel ons</a>
            </div>
        </div>
    </section>
</x-layouts.public>
