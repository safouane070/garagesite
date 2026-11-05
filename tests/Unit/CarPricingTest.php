<?php

namespace Tests\Unit;

use App\Models\Car;
use Tests\TestCase;

class CarPricingTest extends TestCase
{
    public function test_monthly_lease_indication_is_a_sane_annuity(): void
    {
        $car = new Car(['price' => 20000]);
        $monthly = $car->monthlyFrom();

        // 72 mnd, 8,99%, 10% aan, 15% slot: ruim onder prijs/72 plus rente, boven nul.
        $this->assertGreaterThan(200, $monthly);
        $this->assertLessThan(350, $monthly);
        $this->assertGreaterThan($monthly, (new Car(['price' => 40000]))->monthlyFrom());
    }

    public function test_no_lease_indication_for_cheap_cars(): void
    {
        $this->assertNull((new Car(['price' => 3500]))->monthlyFrom());
    }

    public function test_btw_marker_is_detected_and_stripped(): void
    {
        $car = new Car(['variant' => '60 TFSI e quattro Competition BTW']);
        $this->assertTrue($car->isVatDeductible());
        $this->assertSame('60 TFSI e quattro Competition', $car->cleanVariant());
        $this->assertFalse((new Car(['variant' => '1.4 TSI Highline']))->isVatDeductible());
    }
}
