<x-layouts.public title="Financial Lease"
    description="Financial lease bij Autobedrijf Rijswijk: rijd nu in je droomauto en word direct eigenaar. Bereken vrijblijvend een indicatief maandbedrag.">

    <x-page-hero kicker="Financial Lease"
        title="Jouw droomauto binnen handbereik"
        intro="Met financial lease spreid je de aanschaf over een vaste looptijd én ben je vanaf dag één eigenaar. Ideaal voor ondernemers en zzp'ers." />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-2 lg:gap-16">
            {{-- Voordelen --}}
            <div>
                <h2 class="font-display text-2xl font-bold text-cream">Waarom financial lease?</h2>
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
                                <p class="font-medium text-cream">{{ $p['t'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-cream/70">{{ $p['d'] }}</p>
                            </div>
                        </li>
                    @endforeach
                </ul>
            </div>

            {{-- Rekenhulp --}}
            <div x-data="leaseCalc({
                    rate: {{ config('brand.finance.annual_interest_pct') }},
                    term: {{ config('brand.finance.default_term_months') }},
                    downPct: {{ config('brand.finance.min_down_pct') }}
                 })" class="surface p-6 sm:p-8">
                <h2 class="font-display text-xl font-bold text-cream">Indicatief maandbedrag</h2>
                <p class="mt-1 text-sm text-cream/60">Schuif en reken direct mee. Puur ter indicatie.</p>

                <div class="mt-6 space-y-6">
                    <div>
                        <div class="flex items-center justify-between">
                            <label class="field-label mb-0" for="lc-price">Aankoopbedrag</label>
                            <span class="font-mono text-sm text-brass-300 tabular" x-text="euro(price)"></span>
                        </div>
                        <input id="lc-price" type="range" min="5000" max="100000" step="500"
                               x-model.number="price" class="mt-3 w-full accent-brass-500">
                    </div>

                    <div>
                        <div class="flex items-center justify-between">
                            <label class="field-label mb-0" for="lc-down">Aanbetaling</label>
                            <span class="font-mono text-sm text-brass-300 tabular"><span x-text="downPct"></span>% · <span x-text="euro(down)"></span></span>
                        </div>
                        <input id="lc-down" type="range" min="0" max="50" step="5"
                               x-model.number="downPct" class="mt-3 w-full accent-brass-500">
                    </div>

                    <div>
                        <label class="field-label" for="lc-term">Looptijd</label>
                        <div class="relative">
                            <select id="lc-term" x-model.number="term" class="field-input appearance-none pr-10">
                                @foreach ([24, 36, 48, 60, 72, 84] as $m)
                                    <option value="{{ $m }}">{{ $m }} maanden</option>
                                @endforeach
                            </select>
                            <x-icon name="chevron-down" class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-cream/70" />
                        </div>
                    </div>
                </div>

                <div class="mt-7 flex items-end justify-between border-t border-hairline pt-6">
                    <div>
                        <span class="kicker text-[0.6rem]">Vanaf ± per maand</span>
                        <span class="mt-1 block font-display text-4xl font-bold text-white tabular" x-text="euro(monthly)"></span>
                    </div>
                    <span class="text-right font-mono text-[0.7rem] text-cream/45">
                        {{ config('brand.finance.annual_interest_pct') }}% rente<br>indicatief
                    </span>
                </div>

                <p class="mt-4 text-xs leading-relaxed text-cream/45">
                    Dit is een indicatieve berekening, geen kredietaanbod. Het werkelijke maandbedrag hangt af
                    van looptijd, slottermijn en acceptatie. Vraag een persoonlijk voorstel aan.
                </p>

                <a href="{{ route('contact') }}" class="btn btn-primary mt-6 w-full">
                    Vraag een voorstel aan <x-icon name="arrow-right" class="h-4 w-4" />
                </a>
            </div>
        </div>
    </section>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('leaseCalc', (cfg) => ({
                price: 25000,
                downPct: cfg.downPct,
                term: cfg.term,
                rate: cfg.rate,
                get down() { return Math.round(this.price * this.downPct / 100); },
                get monthly() {
                    const financed = this.price - this.down;
                    const r = this.rate / 100 / 12;
                    const n = this.term;
                    if (financed <= 0) return 0;
                    const m = r > 0 ? financed * r / (1 - Math.pow(1 + r, -n)) : financed / n;
                    return Math.round(m);
                },
                euro(n) { return '€ ' + Number(n).toLocaleString('nl-NL'); },
            }));
        });
    </script>
</x-layouts.public>
