<x-layouts.public title="Over ons"
    description="Autobedrijf Rijswijk: ontstaan uit een passie voor auto's. BOVAG-erkend, Volkswagen-specialist en gericht op eerlijke, persoonlijke service.">

    <x-page-hero kicker="Over ons"
        title="Ontstaan uit passie voor auto's"
        intro="Wat begon als een gedeelde liefde voor mooie Duitse auto's, groeide uit tot een BOVAG-erkend autobedrijf in Rijswijk." />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-7">
                <h2 class="font-display text-2xl font-bold text-cream">Hoe het begon</h2>
                <div class="mt-4 space-y-4 leading-relaxed text-cream/70">
                    <p>
                        Bij ons autobedrijf in Rijswijk staat betrouwbaarheid voorop. Ontstaan uit een diepe
                        passie voor auto's en versterkt door vriendschap en klantvriendelijkheid, richten we
                        ons op wat we het beste kennen: kwalitatieve Volkswagen-, Audi- en premium Duitse occasions.
                    </p>
                    <p>
                        Als specialist bieden we uitzonderlijke service, inclusief BOVAG-garantie, waarmee we onze
                        expertise en het vertrouwen in elk voertuig onderstrepen. Elke auto in ons aanbod wordt
                        geleverd met APK, een servicebeurt en garantie — zodat je zorgeloos kunt genieten van je aankoop.
                    </p>
                    <p>
                        We geloven in eerlijke prijzen zonder afleverkosten en in persoonlijk contact. Geen
                        verkooppraatjes, maar een eerlijk advies dat past bij wat jij zoekt.
                    </p>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary">Bekijk het aanbod <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    <a href="{{ route('contact') }}" class="btn btn-outline">Kom langs</a>
                </div>
            </div>

            {{-- Waarden --}}
            <div class="lg:col-span-5">
                @php
                    $values = [
                        ['icon' => 'shield-check', 't' => 'BOVAG-erkend', 'd' => 'Garantie, keuring en een aangesloten, gecontroleerd bedrijf.'],
                        ['icon' => 'badge-check', 't' => 'Volkswagen-specialist', 'd' => 'Diepgaande kennis van de VAG-modellen die we verkopen.'],
                        ['icon' => 'tag', 't' => 'Geen afleverkosten', 'd' => 'De prijs die je ziet, is de prijs die je betaalt.'],
                        ['icon' => 'repeat', 't' => 'Inruil & financiering', 'd' => 'Een eerlijke inruilprijs en passende financiering.'],
                    ];
                @endphp
                <div class="divide-y divide-hairline rounded-[4px] border border-hairline">
                    @foreach ($values as $v)
                        <div class="flex items-start gap-4 p-5">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-brass-500/35 text-brass-400"><x-icon name="{{ $v['icon'] }}" class="h-5 w-5" /></span>
                            <div>
                                <p class="font-display font-semibold text-cream">{{ $v['t'] }}</p>
                                <p class="mt-1 text-sm leading-relaxed text-cream/65">{{ $v['d'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    <x-home.stats />
</x-layouts.public>
