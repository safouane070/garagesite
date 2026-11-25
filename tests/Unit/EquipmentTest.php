<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Support\CarDescription;
use App\Support\Equipment;
use Tests\TestCase;

/** Uitrusting zoals de dealerfeed hem levert (74 opties bij een Audi Q3), leesbaar gemaakt. */
class EquipmentTest extends TestCase
{
    /** Wettelijk verplicht of geen uitrusting: alleen ruis tussen de echte opties. */
    public function test_noise_is_dropped(): void
    {
        $this->assertSame(['Navigatie full map'], Equipment::clean([
            'Bovag', 'Startblokkering', 'Anti Blokkeer Systeem (ABS)', 'Electronic Stability Program (ESP)',
            'Electronic Brake Distribution (EBD)', 'Tractie Controle Systeem (TCS)', 'LED', 'Premium kleur',
            'Bandenspanningscontrolesysteem', 'Navigatie full map',
        ]));
    }

    public function test_airbags_become_one_line(): void
    {
        $this->assertSame(['Airbags: bestuurder, passagier, hoofd, zij en knie'], Equipment::clean([
            'Airbag bestuurder', 'Airbag passagier', 'Airbag(s) hoofd voor', 'Airbag(s) hoofd achter',
            'Airbag(s) side voor', 'Airbag(s) knie',
        ]));
    }

    /** De algemene variant verdwijnt als de specifiekere er ook staat. */
    public function test_general_variant_gives_way_to_the_specific_one(): void
    {
        $this->assertSame(
            ['Cruise control adaptief met Stop&Go', 'Keyless-pakket', 'Elektrisch bedienbare achterklep met sensorsturing', 'Matrix LED koplampen', 'Audio installatie premium'],
            Equipment::clean([
                'Cruise control', 'Cruise control adaptief', 'Cruise control adaptief met Stop&Go',
                'Keyless entry', 'Keyless start', 'Keyless-pakket',
                'Elektrisch bedienbare achterklep', 'Elektrisch bedienbare achterklep met sensorsturing',
                'LED koplampen', 'Matrix LED koplampen', 'Audio installatie', 'Audio installatie premium',
            ]),
        );
    }

    public function test_pairs_merge_into_one_line(): void
    {
        $this->assertSame(['Parkeersensoren voor en achter', 'Elektrische ramen voor en achter', 'Keyless entry en start'], Equipment::clean([
            'Parkeersensor voor', 'Parkeersensor achter', 'Elektrische ramen voor', 'Elektrische ramen achter', 'Keyless entry', 'Keyless start',
        ]));
        $this->assertSame(['Parkeersensoren voor en achter'], Equipment::clean(['Parkeersensor achter', 'Parkeersensor voor en achter']));
    }

    public function test_feed_spelling_is_fixed(): void
    {
        $this->assertSame(
            ['Elektronische klimaatregeling', 'Botsherkenning en -activatie', 'Botswaarschuwingssysteem', 'Vermoeidheidsherkenning', 'Zijwindassistent'],
            Equipment::clean(['Electronic climate controle', 'Bots herkenning en activatie', 'Bots waarschuwing systeem', 'Vermoeidheids herkenning', 'zijwind assistent']),
        );
    }

    public function test_options_are_grouped_by_what_buyers_look_for(): void
    {
        $groups = Equipment::groups([
            'Verwarmde voorstoelen', 'Achteruitrijcamera', 'Navigatie full map', 'Matrix LED koplampen',
            'Lichtmetalen velgen 19"', 'Winter-pakket', 'Panoramadak', 'Cruise control adaptief', 'Iets heel nieuws',
        ]);

        $this->assertSame(['Panoramadak', 'Verwarmde voorstoelen'], $groups['Comfort & interieur']);
        $this->assertSame(['Achteruitrijcamera', 'Cruise control adaptief'], $groups['Veiligheid & assistentie']); // alfabetisch
        $this->assertSame(['Navigatie full map'], $groups['Multimedia & navigatie']);
        $this->assertSame(['Matrix LED koplampen'], $groups['Licht & zicht']);
        $this->assertSame(['Lichtmetalen velgen 19"'], $groups['Exterieur & techniek']);
        $this->assertSame(['Winter-pakket'], $groups['Pakketten']);
        $this->assertSame(['Iets heel nieuws'], $groups['Overig']); // nooit iets kwijt
        $this->assertSame(Equipment::ORDER, array_keys($groups), 'vaste volgorde');
        $this->assertArrayNotHasKey('Overig', Equipment::groups(['Panoramadak']), 'lege groepen weg');
    }

    /** "ruit" in achteruitrijcamera is geen ruit; sfeerverlichting is interieur, geen koplamp. */
    public function test_tricky_names_land_in_the_right_group(): void
    {
        $g = Equipment::groups(['Achteruitrijcamera', 'Sfeerverlichting', 'Grootlicht-assistent', 'Warmtewerende voorruit', 'Lederen/stoffen bekleding', 'S Line exterieur']);

        $this->assertSame(['Achteruitrijcamera'], $g['Veiligheid & assistentie']);
        $this->assertSame(['Lederen/stoffen bekleding', 'Sfeerverlichting'], $g['Comfort & interieur']);
        $this->assertSame(['Grootlicht-assistent', 'Warmtewerende voorruit'], $g['Licht & zicht']);
        $this->assertSame(['S Line exterieur'], $g['Exterieur & techniek']);
    }

    /** "Acc" in "Accident Avoidance System" is geen adaptieve cruise control. */
    public function test_accident_avoidance_is_not_mistaken_for_adaptive_cruise(): void
    {
        $car = new Car(['options' => ['Accident Avoidance System', 'Panoramadak', 'Cruise control adaptief', 'Achteruitrijcamera']]);

        $this->assertSame(['Panoramadak', 'Cruise control adaptief', 'Achteruitrijcamera', 'Accident Avoidance System'], $car->equipment()['highlights']);
    }

    /** Niets verdwijnt stil: elke ingevoerde optie zit in een groep, is samengevoegd of bewust weggelaten. */
    public function test_the_real_q3_list_stays_complete_and_shrinks(): void
    {
        $q3 = ['Elektrisch glazen schuifdak', 'Panoramadak', 'LED koplampen', 'Matrix LED koplampen', 'Accident Avoidance System', 'Lederen/stoffen bekleding', 'Lederen sportstuur', 'Navigatie-systeem full map + hard disk', 'Keyless-pakket', 'Keyless entry', 'Keyless start', 'Lichtmetalen velgen 5-spaaks 20"', 'Achterbank in delen neerklapbaar', 'Airbag(s) hoofd achter', 'Airbag(s) hoofd voor', 'Airbag(s) side voor', 'Airbag bestuurder', 'Airbag passagier', 'Alarmsysteem', 'Anti Blokkeer Systeem (ABS)', 'Bovag', 'Cruise control', 'Cruise control adaptief', 'Parkeersensor achter', 'Parkeersensor voor en achter', 'Startblokkering', 'Verwarmde voorstoelen'];
        $car = new Car(['options' => $q3]);
        $e = $car->equipment();

        $this->assertLessThan(count($q3), $e['count']);
        $this->assertSame($e['count'], array_sum(array_map('count', $e['groups'])));
        $this->assertContains('Verwarmde voorstoelen', $e['groups']['Comfort & interieur']);
        $this->assertCount(6, $e['highlights']);
    }

    /** De beschrijving gebruikt dezelfde opgeschoonde lijst: geen "Accident Avoidance System" als hoogtepunt meer. */
    public function test_description_uses_the_cleaned_highlights(): void
    {
        $car = new Car(['brand' => 'Audi', 'model' => 'Q3', 'year' => 2023, 'mileage' => 1000, 'fuel_type' => 'Benzine', 'transmission' => 'Automaat',
            'options' => ['Accident Avoidance System', 'Bovag', 'Panoramadak', 'Cruise control', 'Cruise control adaptief']]);

        $text = CarDescription::for($car);
        $this->assertStringContainsString('panoramadak, cruise control adaptief en Accident Avoidance System', $text);
        $this->assertStringNotContainsString('Bovag', $text);
    }
}
