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
    /**
     * Per begrip de schrijfwijzen die de feed gebruikt. Eén begrip levert hoogstens
     * één optie op: "elektrisch glazen panorama-dak" en "panoramadak" zijn hetzelfde dak.
     */
    private const PRIORITY = [
        'dak' => ['panorama', 'pano', 'schuifdak', 'open dak'],
        'hud' => ['head-up', 'head up', 'hud'],
        'licht' => ['matrix', 'laser', 'led-koplamp', 'led koplamp'],
        'cockpit' => ['virtual cockpit', 'digitaal dashboard', 'digital cockpit'],
        'acc' => ['adaptive cruise', 'adaptieve cruise', 'acc'],
        'bekleding' => ['leder', 'leer', 'alcantara'],
        'stoelen' => ['stoelverwarming', 'stoelventilatie', 'massage', 'memory'],
        'audio' => ['bang & olufsen', 'b&o', 'bose', 'harman', 'sonos', 'burmester', 'meridian'],
        'camera' => ['360', 'camera'],
        'navigatie' => ['navigatie'],
        'trekhaak' => ['trekhaak'],
        'vering' => ['luchtvering'],
        'achterklep' => ['elektrische achterklep', 'achterklep elektrisch'],
        'keyless' => ['keyless'],
        'sfeer' => ['sfeerverlichting'],
        'carplay' => ['carplay', 'android auto'],
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
        $conceptOf = function (string $option): ?string {
            $o = mb_strtolower($option);
            foreach (self::PRIORITY as $concept => $needles) {
                foreach ($needles as $needle) {
                    if (str_contains($o, $needle)) {
                        return $concept;
                    }
                }
            }

            return null;
        };

        // Eerst per begrip (in volgorde van belang) de eerste optie die erbij hoort.
        $picked = [];
        foreach (array_keys(self::PRIORITY) as $concept) {
            foreach ($options as $o) {
                if ($conceptOf($o) === $concept) {
                    $picked[] = $o;
                    break;
                }
            }
        }
        $picked = array_slice($picked, 0, self::HIGHLIGHTS);

        // Aanvullen met overige opties, maar nooit een tweede van een al gekozen begrip.
        $usedConcepts = array_map($conceptOf, $picked);
        foreach ($options as $o) {
            if (count($picked) >= self::HIGHLIGHTS) {
                break;
            }
            $concept = $conceptOf($o);
            if (! in_array($o, $picked, true) && ($concept === null || ! in_array($concept, $usedConcepts, true))) {
                $picked[] = $o;
                $usedConcepts[] = $concept;
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
