<?php

namespace App\Support;

use App\Models\Car;

/**
 * Beschrijving opgebouwd uit de échte gegevens van één auto (specs + uitrusting),
 * zodat elke detailpagina unieke tekst heeft zonder iets te verzinnen. Wat de
 * beheerder zelf schrijft, gaat altijd voor (zie cars:describe).
 */
class CarDescription
{
    /** Zin waaraan de oude, generieke seeder-tekst herkenbaar is. */
    public const LEGACY_MARKER = 'Afgeleverd met onderhoudshistoriek, keuringsattest en BOVAG-garantie.';

    private const HIGHLIGHTS = 6;

    public static function for(Car $car): string
    {
        $km = number_format((int) $car->mileage, 0, ',', '.');
        $pk = (int) ($car->specs['vermogen_pk'] ?? 0);

        $kenmerken = array_filter([
            $pk ? "{$pk} pk" : null,
            mb_strtolower((string) $car->fuel_type),
            $car->transmission === 'Automaat' ? 'automaat' : 'handgeschakeld',
            $car->body_type ? mb_strtolower($car->body_type) : null,
            $car->color && $car->color !== 'Onbekend' ? 'in het ' . mb_strtolower($car->color) : null,
        ]);

        $zinnen = [
            "Deze {$car->title()} uit {$car->year} heeft {$km} km op de teller.",
            ucfirst(implode(', ', $kenmerken)) . '.',
        ];

        $options = array_slice(array_values($car->options ?? []), 0, self::HIGHLIGHTS);
        if ($options !== []) {
            $zinnen[] = 'Uitgerust met onder meer ' . self::list($options) . '.';
        }

        $zinnen[] = 'Geleverd met BOVAG-garantie, zonder afleverkosten. Inruil en financiering zijn mogelijk.';

        return implode(' ', $zinnen);
    }

    /** Leeg of nog de generieke seeder-tekst → mag vervangen worden. */
    public static function isReplaceable(?string $description): bool
    {
        return blank($description) || str_contains($description, self::LEGACY_MARKER);
    }

    /** "a, b en c" */
    private static function list(array $items): string
    {
        // "Navigatie" → "navigatie", maar afkortingen blijven: "LED-koplampen", "BOSE".
        $items = array_map(
            fn ($o) => preg_match('/^\p{Lu}\p{Ll}/u', $o) ? mb_strtolower(mb_substr($o, 0, 1)) . mb_substr($o, 1) : $o,
            $items
        );
        $last = array_pop($items);

        return $items ? implode(', ', $items) . ' en ' . $last : $last;
    }
}
