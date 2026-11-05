<?php

namespace App\Support;

use App\Models\Car;

/**
 * Beschrijving opgebouwd uit de échte gegevens van één auto (specs + uitrusting),
 * zodat elke detailpagina unieke tekst heeft zonder iets te verzinnen. Wat de
 * beheerder zelf schrijft, gaat altijd voor.
 */
class CarDescription
{
    /** Zin waaraan de oude, generieke seeder-tekst herkenbaar is. */
    public const LEGACY_MARKER = 'Afgeleverd met onderhoudshistoriek, keuringsattest en BOVAG-garantie.';

    /** Slotzin van elke gegenereerde tekst: zo herkennen we die en mag hij opnieuw opgebouwd worden. */
    public const GENERATED_MARKER = 'Geleverd met BOVAG-garantie, zonder afleverkosten. Inruil en financiering zijn mogelijk.';

    private const HIGHLIGHTS = 6;

    /**
     * Opties waar kopers op letten, in volgorde van belang. De optielijst van de
     * dealer is alfabetisch; zonder deze voorkeur noemden we "aanhanger-assistent".
     */
    private const PRIORITY = [
        'panorama', 'pano', 'head-up', 'matrix', 'virtual cockpit', 'adaptive cruise', 'adaptieve cruise',
        'leder', 'stoelverwarming', 'stoelventilatie', 'massage', 'bang & olufsen', 'b&o', 'bose', 'harman',
        '360', 'camera', 'navigatie', 'trekhaak', 'luchtvering', 'elektrische achterklep', 'keyless',
        'sfeerverlichting', 'carplay', 'parkeersensor',
    ];

    public static function for(Car $car): string
    {
        $km = number_format((int) $car->mileage, 0, ',', '.');
        $pk = (int) ($car->specs['vermogen_pk'] ?? 0);
        $color = trim((string) $car->color);

        $kenmerken = array_filter([
            $pk ? "{$pk} pk" : null,
            mb_strtolower((string) $car->fuel_type),
            $car->transmission === 'Automaat' ? 'automaat' : 'handgeschakeld',
            $car->body_type ? mb_strtolower($car->body_type) : null,
            // De feed geeft soms "Zilver of Grijs": dat is een kleurgroep, geen kleur.
            $color && $color !== 'Onbekend' ? 'kleur ' . mb_strtolower(str_ireplace(' of ', '/', $color)) : null,
        ]);

        $title = trim($car->shortTitle() . ' ' . $car->cleanVariant());
        $zinnen = [
            "Deze {$title} uit {$car->year} heeft {$km} km op de teller.",
            ucfirst(implode(', ', $kenmerken)) . '.',
        ];

        $options = self::highlights(array_values($car->options ?? []));
        if ($options !== []) {
            $zinnen[] = 'Uitgerust met onder meer ' . self::list($options) . '.';
        }

        $zinnen[] = self::GENERATED_MARKER;

        return implode(' ', $zinnen);
    }

    /** Leeg, de oude seeder-tekst of door ons gegenereerd → mag (opnieuw) opgebouwd worden. */
    public static function isReplaceable(?string $description): bool
    {
        return blank($description)
            || str_contains($description, self::LEGACY_MARKER)
            || str_contains($description, self::GENERATED_MARKER);
    }

    /** Wat de pagina toont: tekst van de beheerder, anders altijd de actuele opbouw. */
    public static function display(Car $car): string
    {
        return self::isReplaceable($car->description) ? self::for($car) : $car->description;
    }

    /** De belangrijkste opties eerst, aangevuld in de originele volgorde. */
    private static function highlights(array $options): array
    {
        $picked = [];
        foreach (self::PRIORITY as $needle) {
            foreach ($options as $o) {
                if (count($picked) < self::HIGHLIGHTS && ! in_array($o, $picked, true) && str_contains(mb_strtolower($o), $needle)) {
                    $picked[] = $o;
                    break;
                }
            }
        }
        foreach ($options as $o) {
            if (count($picked) >= self::HIGHLIGHTS) {
                break;
            }
            if (! in_array($o, $picked, true)) {
                $picked[] = $o;
            }
        }

        return $picked;
    }

    /** "a, b en c" */
    private static function list(array $items): string
    {
        // "Navigatie" → "navigatie"; namen met meer hoofdletters ("Accident Avoidance
        // System", "LED-koplampen", "BOSE") blijven zoals ze zijn.
        $items = array_map(
            fn ($o) => preg_match('/^\p{Lu}\p{Ll}/u', $o) && ! preg_match('/\s\p{Lu}/u', $o)
                ? mb_strtolower(mb_substr($o, 0, 1)) . mb_substr($o, 1)
                : $o,
            $items
        );
        $last = array_pop($items);

        return $items ? implode(', ', $items) . ' en ' . $last : $last;
    }
}
