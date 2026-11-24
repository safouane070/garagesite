<x-layouts.public title="Volkswagen Specialist"
    description="Autobedrijf Rijswijk is Volkswagen- en VAG-specialist: diepgaande kennis van Volkswagen, Audi, SEAT, CUPRA en Škoda. Occasions met garantie.">

    <x-page-hero title="De Volkswagen-specialist van Rijswijk"
        intro="Volkswagen, Audi, SEAT, CUPRA en Škoda: we kennen deze modellen door en door. Die kennis vertaalt zich in scherp geselecteerde occasions en eerlijk advies."
        image="images/site/vw-specialist.webp" alt="Medewerker in een jas met het embleem Volkswagen Specialist" position="60% 40%" />

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
                    <h2 class="text-sm font-semibold text-cream">Onze merken</h2>
                    {{-- Hele rij is de link: een duim raakt de rij, niet het woord "Bekijken". --}}
                    <ul class="mt-2">
                        @foreach ($brands as $b)
                            <li class="border-b border-hairline last:border-0">
                                <a href="{{ route('cars.index', ['brand' => $b]) }}"
                                   class="group flex items-center justify-between py-3.5 transition-colors duration-150 ease-out active:bg-cream/[0.04]">
                                    <span class="font-display font-semibold text-cream">{{ $b }}</span>
                                    <span class="inline-flex items-center gap-1.5 text-sm text-brass-300 transition-colors group-hover:text-brass-200">
                                        Bekijken <x-icon name="arrow-right" class="h-3.5 w-3.5 transition-transform duration-200 ease-out [@media(hover:hover)]:group-hover:translate-x-0.5" />
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </section>
</x-layouts.public>
