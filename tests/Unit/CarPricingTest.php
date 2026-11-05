<?php

namespace Tests\Unit;

use App\Models\Car;
use Tests\TestCase;

class CarPricingTest extends TestCase
{
    /** Bedragen zoals de calculator van FinancialLease.nl (id 1262) ze in nov 2026 gaf. */
    public function test_monthly_matches_the_lease_partners_calculator(): void
    {
        foreach ([20000 => 345, 65950 => 1209, 120000 => 2223] as $price => $expected) {
            $monthly = (new Car(['price' => $price]))->monthlyFrom();
            $this->assertEqualsWithDelta($expected, $monthly, 2, "prijs {$price}");
        }
    }

    public function test_btw_car_is_leased_excluding_vat(): void
    {
        $btw = new Car(['price' => 60500, 'vat_deductible' => true]);
        $this->assertSame(50000, $btw->leaseAmount());
        $this->assertLessThan((new Car(['price' => 60500]))->monthlyFrom(), $btw->monthlyFrom());
        $this->assertStringContainsString('leasePrice=50000', $btw->leaseCalculatorUrl());
    }

    public function test_no_lease_indication_for_cheap_cars(): void
    {
        $this->assertNull((new Car(['price' => 6500]))->monthlyFrom());
    }

    public function test_btw_marker_is_detected_and_stripped(): void
    {
        $car = new Car(['variant' => '60 TFSI e quattro Competition BTW']);
        $this->assertTrue(Car::mentionsVat('Audi Q8 55 TFSI e Competition BTW!'));
        $this->assertSame('60 TFSI e quattro Competition', $car->cleanVariant());
        $this->assertFalse(Car::mentionsVat('Golf 1.4 TSI Highline'));
        $this->assertFalse((new Car(['variant' => 'Competition BTW']))->isVatDeductible(), 'alleen het veld telt, zodat de beheerder het kan uitzetten');
    }
}
