<x-layouts.public title="Financial Lease"
    description="Financial lease bij Autobedrijf Rijswijk: rijd nu in je droomauto en word direct eigenaar. Bekijk ons actuele financial-lease-aanbod.">

    <x-page-hero title="Jouw droomauto binnen handbereik"
        intro="Met financial lease spreid je de aanschaf over een vaste looptijd én ben je vanaf dag één eigenaar. Ideaal voor ondernemers en zzp'ers."
        image="images/site/interieur.webp" alt="Interieur van een Audi RS3 uit het aanbod" />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
            {{-- Voordelen --}}
            <div>
                <h2 class="font-display text-2xl font-bold text-white">Waarom financial lease?</h2>
                @php
                    $points = [
                        ['t' => 'Direct eigenaar', 'd' => 'De auto staat meteen op jouw naam; na de laatste termijn is hij volledig van jou.'],
                        ['t' => 'Vaste maandlasten', 'd' => 'Een vast bedrag per maand over de looptijd — overzichtelijk en voorspelbaar.'],
                        ['t' => 'Fiscaal aantrekkelijk', 'd' => 'Voor ondernemers: rente en afschrijving zijn doorgaans aftrekbaar. Vraag je adviseur.'],
                        ['t' => 'Flexibele looptijd', 'd' => 'Kies zelf de looptijd en aanbetaling die bij je situatie past.'],
                    ];
                @endphp
                <ul class="mt-6 space-y-5">
                    @foreach ($points as $p)
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 flex h-8 w-8 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="check" class="h-4 w-4" /></span>
                            <div>
                                <p class="font-medium text-white">{{ $p['t'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-cream/70">{{ $p['d'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>

                {{-- Rekenvoorbeeld met dezelfde rekenregels als de calculator van de leasepartner
                     (Car::monthlyFrom). Een getal zegt meer dan "vaste maandlasten". --}}
                @php
                    $lease = config('brand.finance.lease');
                @endphp
                <div class="mt-8 rounded-[4px] border border-hairline bg-graphite-700/50 p-6">
                    <h3 class="font-display text-lg font-semibold text-white">Rekenvoorbeeld</h3>
                    <p class="mt-1 text-sm text-cream/65">
                        {{ $lease['months'] }} maanden, geen aanbetaling, € {{ number_format($lease['closing'], 0, ',', '.') }} slottermijn.
                    </p>
                    <dl class="mt-4 divide-y divide-hairline border-y border-hairline">
                        @foreach ([20000, 35000, 60000] as $amount)
                            <div class="flex items-baseline justify-between py-2.5 text-sm">
                                <dt class="text-cream/70">Leasebedrag € {{ number_format($amount, 0, ',', '.') }}</dt>
                                <dd class="font-semibold text-white tabular">€ {{ number_format((new \App\Models\Car(['price' => $amount]))->monthlyFrom(), 0, ',', '.') }} p/m</dd>
                            </div>
                        @endforeach
                    </dl>
                    <p class="mt-3 text-xs leading-relaxed text-cream/55">
                        Indicatie op basis van de calculator van FinancialLease.nl. Je persoonlijke voorstel kan afwijken.
                    </p>
                    <a href="{{ route('contact', ['onderwerp' => 'financiering']) }}#contact" class="btn btn-primary mt-5">
                        Vraag een voorstel aan <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                </div>
            </div>

            {{-- Echt financial-lease-aanbod (widget van onze leasepartner) --}}
            <div>
                <p class="kicker">Actueel aanbod</p>
                <p class="mt-2 mb-4 text-sm text-cream/60">Live maandbedragen, verzorgd door onze leasepartner FinancialLease.nl.</p>
                <x-consent-embed
                    src="https://iframe.financiallease.nl/lease?stock_id={{ config('brand.finance.financiallease_stock_id') }}&primary_color=d90429&secondary_color=d90429"
                    title="Financial-lease-aanbod van {{ config('app.name') }}" provider="FinancialLease.nl" height="1500px"
                    button="Toon het lease-aanbod" icon="repeat" class="bg-white"
                    fallback-url="https://www.financiallease.nl/" fallback-label="FinancialLease.nl">
                    <p class="font-display text-lg font-semibold text-cream">Live maandbedragen per auto</p>
                </x-consent-embed>
            </div>
        </div>
    </section>

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
                 'a' => 'Ja. Volgens onze leasepartner kun je altijd eerder aflossen of inruilen.'],
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
</x-layouts.public>
