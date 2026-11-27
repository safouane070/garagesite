<x-layouts.public title="Over ons"
    description="Volkswagen-, Audi- en premium occasions bij Autobedrijf Rijswijk. Bezoek onze showroom aan de Poldermeesterstraat in Rijswijk.">

    <x-page-hero title="Autobedrijf Rijswijk"
        intro="Volkswagen-, Audi- en premium occasions aan de Poldermeesterstraat in Rijswijk."
        image="images/site/kantoor.webp" alt="Ontvangstruimte in de showroom, met het RDW-erkend-bord" />

    <section class="container-x py-12 lg:py-16">
        <div class="grid gap-12 lg:grid-cols-12 lg:gap-16">
            <div class="lg:col-span-7">
                <h2 class="font-display text-2xl font-bold text-cream">Bekijk de auto's in onze showroom</h2>
                <div class="mt-4 space-y-4 leading-relaxed text-cream/70">
                    <p>
                        Onze voorraad bestaat vooral uit Volkswagen-, Audi- en andere Duitse occasions.
                        Op de site vind je per auto de prijs, kilometerstand, uitrusting en foto's.
                        In de showroom kun je de auto bekijken en een proefrit maken.
                    </p>
                    <p>
                        We zijn BOVAG-erkend en rekenen geen afleverkosten. Je kunt bij ons ook
                        je huidige auto inruilen of de mogelijkheden voor financiering bespreken.
                    </p>
                    <p>
                        Bel even voordat je langskomt. Dan kunnen we controleren of de auto er nog is
                        en tijd vrijmaken voor je bezoek.
                    </p>
                </div>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ route('cars.index') }}" class="btn btn-primary">Bekijk het aanbod <x-icon name="arrow-right" class="h-4 w-4" /></a>
                    <a href="{{ route('contact') }}" class="btn btn-outline">Kom langs</a>
                </div>
            </div>

            <aside class="lg:col-span-5" aria-labelledby="showroom-details">
                <h2 id="showroom-details" class="font-display text-xl font-semibold text-cream">Bezoek de showroom</h2>
                <dl class="mt-5 divide-y divide-hairline border-y border-hairline text-sm">
                    <div class="py-4">
                        <dt class="text-cream/60">Adres</dt>
                        <dd class="mt-1 text-cream">{{ config('brand.contact.address') }}</dd>
                    </div>
                    <div class="py-4">
                        <dt class="text-cream/60">Openingstijden</dt>
                        <dd class="mt-1 text-cream">{{ config('brand.contact.hours') }}</dd>
                    </div>
                    <div class="py-4">
                        <dt class="text-cream/60">Bel voor een bezoek</dt>
                        <dd class="mt-1"><a href="tel:{{ config('brand.contact.phone_href') }}" class="text-brass-300 hover:underline">{{ config('brand.contact.phone') }}</a></dd>
                    </div>
                </dl>
            </aside>
        </div>
    </section>

    {{-- Echte klantstemmen i.p.v. een rij losse cijfers: zij noemen de mensen achter de zaak. --}}
    <x-home.reviews />
</x-layouts.public>
