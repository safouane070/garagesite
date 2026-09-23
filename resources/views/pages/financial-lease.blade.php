<x-layouts.public title="Financial Lease"
    description="Financial lease bij Autobedrijf Rijswijk: rijd nu in je droomauto en word direct eigenaar. Bekijk ons actuele financial-lease-aanbod.">

    <x-page-hero kicker="Financial Lease"
        title="Jouw droomauto binnen handbereik"
        intro="Met financial lease spreid je de aanschaf over een vaste looptijd én ben je vanaf dag één eigenaar. Ideaal voor ondernemers en zzp'ers." />

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

                <div class="mt-8 rounded-[4px] border border-hairline bg-graphite-700/50 p-6">
                    <p class="text-sm leading-relaxed text-cream/70">
                        Elke occasion is ook via financial lease te rijden. Het exacte maandbedrag hangt af van looptijd,
                        aanbetaling en slottermijn. Bekijk hiernaast een indicatie of vraag een persoonlijk voorstel aan.
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
                <div class="overflow-hidden rounded-[4px] border border-hairline bg-white">
                    <iframe
                        title="Financial-lease-aanbod van Autobedrijf Rijswijk"
                        src="https://iframe.financiallease.nl/lease?stock_id={{ config('brand.finance.financiallease_stock_id') }}&primary_color=d90429&secondary_color=d90429"
                        class="w-full" style="height: 1500px; border: 0" loading="lazy"></iframe>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
