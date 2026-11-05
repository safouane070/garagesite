<?php

namespace Tests\Unit;

use App\Models\Car;
use App\Support\CarDescription;
use Tests\TestCase;

class CarDescriptionTest extends TestCase
{
    private function car(array $options): Car
    {
        return new Car([
            'brand' => 'Audi', 'model' => 'Q8', 'variant' => '60 TFSI e Competition BTW', 'year' => 2026,
            'mileage' => 20739, 'fuel_type' => 'Hybride', 'transmission' => 'Automaat',
            'body_type' => 'SUV', 'color' => 'Zilver of Grijs', 'options' => $options,
        ]);
    }

    /** Het Q8-voorbeeld uit de review: twee keer hetzelfde dak. */
    public function test_one_concept_is_named_once(): void
    {
        $text = CarDescription::for($this->car([
            'Aanhanger-assistent', 'Elektrisch glazen panorama-dak', 'Head-up display',
            'Matrix LED koplampen', 'Panoramadak', 'Stoelverwarming voor', 'Stoelventilatie',
        ]));

        $this->assertSame(1, substr_count(mb_strtolower($text), 'panorama'));
        $this->assertStringContainsString('elektrisch glazen panorama-dak', $text);
        $this->assertStringNotContainsString('stoelventilatie', mb_strtolower($text));
    }

    public function test_btw_marker_and_colour_group_read_naturally(): void
    {
        $text = CarDescription::for($this->car([]));

        $this->assertStringContainsString('Deze Audi Q8 60 TFSI e Competition uit 2026', $text);
        $this->assertStringContainsString('kleur zilver/grijs', $text);
    }

    public function test_falls_back_to_other_options_when_few_highlights(): void
    {
        $text = CarDescription::for($this->car(['Isofix', 'Navigatie', 'Regensensor']));

        $this->assertStringContainsString('navigatie, isofix en regensensor', $text);
    }
}
