<x-layouts.public title="Volkswagen Specialist"
    description="Autobedrijf Rijswijk is Volkswagen- en VAG-specialist: diepgaande kennis van Volkswagen, Audi, SEAT, CUPRA en Škoda. Occasions met garantie.">

    <x-page-hero kicker="Volkswagen Specialist"
        title="Specialist in Volkswagen & het VAG-concern"
        intro="Volkswagen, Audi, SEAT, CUPRA en Škoda: we kennen deze modellen door en door. Die kennis vertaalt zich in scherp geselecteerde occasions en eerlijk advies." />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-7">
                <h2 class="font-display text-2xl font-bold text-cream">Waarom een specialist?</h2>
                <div class="mt-4 space-y-4 leading-relaxed text-cream/70">
                    <p>
                        Als VAG-specialist weten we precies waar je bij deze modellen op moet letten — van de
                        bekende motoren en distributie tot software en uitrusting. Elke occasion die we inkopen
                        beoordelen we met die kennis, zodat jij een auto koopt die klopt.
                    </p>
                    <p>
                        Onze voorraad bestaat grotendeels uit Volkswagens en premium Duitse occasions. Van een
                        zuinige Polo of Golf tot een CUPRA Formentor, Audi of Tiguan met complete uitrusting —
                        allemaal met BOVAG-garantie, APK en servicebeurt.
                    </p>
                </div>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('cars.index', ['brand' => 'Volkswagen']) }}" class="btn btn-primary">
                        Bekijk de Volkswagens <x-icon name="arrow-right" class="h-4 w-4" />
                    </a>
                    <a href="{{ route('cars.index') }}" class="btn btn-outline">Volledig aanbod</a>
                </div>
            </div>

            <div class="lg:col-span-5">
                @php
                    $brands = ['Volkswagen', 'Audi', 'SEAT', 'CUPRA', 'Škoda'];
                @endphp
                <div class="rounded-[4px] border border-hairline bg-graphite-700/50 p-6">
                    <p class="kicker">Onze merken</p>
                    <ul class="mt-4 space-y-3">
                        @foreach ($brands as $b)
                            <li class="flex items-center justify-between border-b border-hairline pb-3 last:border-0 last:pb-0">
                                <span class="font-display font-semibold text-cream">{{ $b }}</span>
                                <a href="{{ route('cars.index', ['brand' => $b]) }}" class="inline-flex items-center gap-1.5 font-mono text-xs uppercase tracking-wider text-brass-300 transition hover:text-brass-200">
                                    Bekijken <x-icon name="arrow-right" class="h-3.5 w-3.5" />
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
