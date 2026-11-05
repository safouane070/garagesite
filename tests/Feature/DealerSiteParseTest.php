<?php

namespace Tests\Feature;

use App\Support\DealerSite;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * De voertuigpagina van de dealer komt uit een paginabouwer en kan veranderen.
 * parse() moet dan een reden teruggeven, nooit halve gegevens.
 */
class DealerSiteParseTest extends TestCase
{
    private const COVER = 'https://autobedrijfrijswijk.nl/wp-content/uploads/2026/09/555-1.jpg';

    private function page(array $overrides = []): string
    {
        $v = array_merge(['price' => 24950, 'Bouwjaar' => 2021, 'Kilometerstand' => '50000 km', 'Brandstof' => 'B'], $overrides);
        $row = fn ($label, $value) => "<div class=\"ct-div-block table-row\" ><span>{$label}</span><div><span>{$value}</span></div></div></div>";

        $html = '<html><body>';
        foreach (['Bouwjaar', 'Kilometerstand', 'Brandstof'] as $label) {
            if ($v[$label] !== null) {
                $html .= $row($label, $v[$label]);
            }
        }
        if ($v['price'] !== null) {
            $html .= "<span id=\"span-375-134\" class=\"ct-span\" >{$v['price']}</span>";
        }

        return $html . '</body></html>';
    }

    public static function brokenPages(): array
    {
        return [
            'geen prijs' => [['price' => null], 'geen geldige prijs'],
            'prijs te laag' => [['price' => 100], 'geen geldige prijs'],
            'onzin-bouwjaar' => [['Bouwjaar' => 1900], 'geen geldig bouwjaar'],
            'geen kilometerstand' => [['Kilometerstand' => null], 'geen geldige kilometerstand'],
            'onbekende brandstof' => [['Brandstof' => 'X'], 'onbekende brandstof "X"'],
        ];
    }

    #[DataProvider('brokenPages')]
    public function test_unreliable_page_gives_a_reason_not_half_data(array $overrides, string $reason): void
    {
        $this->assertSame($reason, DealerSite::parse($this->page($overrides), 'Volkswagen Golf 1.5 TSI', self::COVER));
    }

    public function test_page_without_a_usable_cover_photo_is_refused(): void
    {
        $this->assertSame("geen foto's", DealerSite::parse($this->page(), 'Volkswagen Golf 1.5 TSI', null));
    }

    public static function fuelCodes(): array
    {
        return [
            ['B', 'Benzine'], ['D', 'Diesel'], ['E', 'Elektrisch'], ['B,E', 'Hybride'],
            ['Hybride (benzine)', 'Hybride'], ['Elektrisch', 'Elektrisch'], ['LPG', 'LPG'],
        ];
    }

    #[DataProvider('fuelCodes')]
    public function test_fuel_codes_and_words_map_to_our_fuel_types(string $dealer, string $ours): void
    {
        $this->assertSame($ours, DealerSite::parse($this->page(['Brandstof' => $dealer]), 'Volkswagen Golf 1.5 TSI', self::COVER)['fuel_type']);
    }

    /** "BTW" staat bij de dealer achteraan de titel, soms na een "/" die de variant afkapt. */
    public function test_btw_in_the_title_marks_a_vat_car(): void
    {
        $this->assertTrue(DealerSite::parse($this->page(), 'Audi Q8 55 TFSI e Competition/Pano BTW!', self::COVER)['vat_deductible']);
        $this->assertFalse(DealerSite::parse($this->page(), 'Volkswagen Golf 1.5 TSI', self::COVER)['vat_deductible']);
    }

    public function test_brand_spellings_are_normalised(): void
    {
        $this->assertSame(['MINI', 'Cooper', 'S'], DealerSite::splitTitle('Mini Cooper S'));
        $this->assertSame(['Škoda', 'Octavia', 'Combi'], DealerSite::splitTitle('Skoda Octavia Combi'));
        $this->assertSame(['Tesla', 'Model 3', 'Long Range'], DealerSite::splitTitle('Tesla Model 3 Long Range'));
    }
}
