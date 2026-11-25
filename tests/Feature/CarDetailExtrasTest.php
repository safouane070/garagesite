<?php

namespace Tests\Feature;

use App\Enums\CarStatus;
use App\Models\Car;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class CarDetailExtrasTest extends TestCase
{
    use RefreshDatabase;

    /** De duurste auto heeft niets binnen ±30%: het blok vult aan i.p.v. leeg te blijven. */
    public function test_related_block_is_filled_even_for_the_most_expensive_car(): void
    {
        $top = Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Porsche', 'body_type' => 'Coupé', 'price' => 155000]);
        foreach ([20000, 25000, 30000, 35000] as $i => $price) {
            Car::factory()->create(['status' => CarStatus::Available, 'brand' => 'Volkswagen', 'body_type' => 'Hatchback', 'price' => $price, 'slug' => "golf-{$i}"]);
        }

        $related = $this->get(route('cars.show', $top))->assertOk()->viewData('related');

        $this->assertCount(3, $related);
        // Dichtstbijzijnde prijs eerst.
        $this->assertSame([35000, 30000, 25000], $related->map(fn ($c) => (int) $c->price)->all());
    }

    /** Eén specblok op één raster: "Algemeen" is gewoon de eerste groep, geen los tweede blok. */
    public function test_specs_are_one_section_with_general_as_first_group(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'specs' => ['vermogen_pk' => 150, 'cilinders' => 4]]);

        $html = $this->get(route('cars.show', $car))->assertOk()
            ->assertSeeInOrder(['Over deze', 'Specificaties', 'Algemeen', 'Bouwjaar', 'Motor &amp; aandrijving', 'Vermogen'], false)
            ->getContent();
        $this->assertSame(1, substr_count($html, '>Specificaties</h2>'));
    }

    /** Hoogtepunten bovenaan, de rest per categorie; geen ruis, geen vinkje voor elke regel. */
    public function test_equipment_shows_highlights_then_groups_without_noise(): void
    {
        $options = ['Panoramadak', 'Head-up display', 'Matrix LED koplampen', 'Cruise control adaptief', 'Achteruitrijcamera', 'Navigatie full map',
            'Verwarmde voorstoelen', 'Bovag', 'Anti Blokkeer Systeem (ABS)', 'Airbag bestuurder', 'Airbag passagier', 'Regensensor', 'Trekhaak', 'DAB-ontvanger', 'Winter-pakket', 'Stuurwiel verwarmd', 'Sfeerverlichting'];
        $car = Car::factory()->create(['status' => CarStatus::Available, 'options' => $options]);

        $html = $this->get(route('cars.show', $car))->assertOk()
            ->assertSeeInOrder(['Uitrusting', 'Panoramadak', 'Alle 14 opties per categorie', 'Comfort &amp; interieur', 'Veiligheid &amp; assistentie', 'Airbags voor'], false)
            ->assertDontSee('>Bovag<', false)
            ->assertDontSee('Anti Blokkeer')
            ->getContent();

        $section = Str::between($html, '>Uitrusting</h2>', 'Interesseformulier');
        $this->assertSame(1, substr_count(Str::before($section, 'Inbegrepen bij aflevering'), '<svg'), 'alleen het pijltje van de knop, geen icoon per optie');
    }

    /** Een kale lijst (≤ 12) toont gewoon de groepen: geen knop, geen dubbele hoogtepunten. */
    public function test_short_equipment_list_is_shown_grouped_without_toggle(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'options' => ['Navigatie full map', 'Trekhaak', 'Regensensor']]);

        $this->get(route('cars.show', $car))->assertOk()
            ->assertSee('Multimedia &amp; navigatie', false)
            ->assertDontSee('opties per categorie');
    }

    public function test_detail_shows_lease_with_terms_and_calculator_link(): void
    {
        $car = Car::factory()->create(['status' => CarStatus::Available, 'price' => 20000]);

        $this->get(route('cars.show', $car))
            ->assertOk()
            ->assertSee('Voor ondernemers')
            ->assertSee('€ 345', false)
            ->assertSee('financiallease.nl/dealer/calculator', false);
    }

    public function test_admin_can_mark_and_unmark_a_vat_car(): void
    {
        $user = User::factory()->create();
        $car = Car::factory()->create();
        $payload = fn (bool $vat) => array_merge(
            $car->only(['brand', 'model', 'variant', 'year', 'price', 'mileage', 'fuel_type', 'transmission', 'color', 'body_type', 'description']),
            ['status' => $car->status->value, 'vat_deductible' => $vat ? '1' : null]
        );

        $this->actingAs($user)->put(route('admin.cars.update', $car), $payload(true))->assertRedirect();
        $this->assertTrue($car->fresh()->vat_deductible);

        $this->actingAs($user)->put(route('admin.cars.update', $car), $payload(false))->assertRedirect();
        $this->assertFalse($car->fresh()->vat_deductible);
    }
}
