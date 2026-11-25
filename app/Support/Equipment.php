<?php

namespace App\Support;

use App\Models\Car;

/**
 * Uitrusting uit de dealerfeed leesbaar gemaakt: zonder ruis en dubbelingen, en per groep
 * zoals kopers zoeken (stoelverwarming? trekhaak?). De feed levert een alfabetische lijst
 * van soms 74 regels, met verplichte systemen (ABS), "Bovag" en tikfouten ertussen.
 */
class Equipment
{
    /** Volgorde waarin de groepen getoond worden. */
    public const ORDER = ['Comfort & interieur', 'Veiligheid & assistentie', 'Multimedia & navigatie', 'Licht & zicht', 'Exterieur & techniek', 'Pakketten', 'Overig'];

    /** Indeling: eerste regel die past wint. Specifiek vóór algemeen (sfeerverlichting is interieur, geen koplamp). */
    private const RULES = [
        '/ambi[ae]n|sfeerverlicht/' => 'Comfort & interieur',
        '/grootlicht|dimlicht|koplamp|mistlamp|xenon|\bled\b|achterlicht|dagrijverlicht|lichtpakket|licht en zicht/' => 'Licht & zicht',
        // "\bruit": niet de "ruit" in achteruitrijcamera.
        '/spiegel|\bruit|voorruit|achterruit|zijruit|glas\b|getint|warmtewerend|geluid|regensensor|zonnescherm|wis-/' => 'Licht & zicht',
        '/airbag|assist|cruise|camera|parkeer|parking|rijstrook|dodehoek|bots|verkeer|vermoeid|uitwijk|hill hold|afdaal|nachtzicht|alarm|isofix|brake|snelheidsbegrenz|accident|rondomzicht|achteruitrij|stuurhulp|zijwind|copilot|safety|file-|anti slip|stability|driving/' => 'Veiligheid & assistentie',
        '/navigatie|audio|radio|dab|bluetooth|telefoon|carplay|android|connect|wifi|multimedia|spraak|head-up|instrumentenpaneel|cockpit|boordcomputer|hard disk|\bcd|dvd|entertainment|oplaad|draadloze|usb|media|bang|bose|harman|burmester|sonos/' => 'Multimedia & navigatie',
        '/velg|\blak\b|metallic|spoiler|skirt|dakrail|trekhaak|onderstel|vering|demping|niveauregeling|differentieel|line|amg|aerodynami|bumper|chroom|exterieur|verlaagd|treeplank|wiel besturing|keramisch|stuurbekrachtiging|stuuroverbrenging|start\/stop|black style|night|adaptive drive|veersysteem|cornering/' => 'Exterieur & techniek',
        '/stoel|bekleding|leder|alcantara|velours|stof|interieur|stuurwiel|sportstuur|stuur verstelbaar|stuurkolom|schakelmogelijkheid|versnellingspook|airco|clima|klimaat|armsteun|achterbank|ramen|keyless|deurvergrendeling|sluitbekrachtiging|achterklep|lendesteun|hoofdsteun|panorama|schuif|kanteldak|open dak|sky lounge|bagage|laadruimte|skiluik|tussenschot|comfort|memory|massage|ventilatie|houtafwerking|pianolak/' => 'Comfort & interieur',
        '/pakket|pack|package/' => 'Pakketten',
    ];

    /** Verplicht op elke moderne auto of geen uitrusting (kleur staat al bij de specs). */
    private const NOISE = [
        'bovag', 'startblokkering', 'anti blokkeer systeem', 'anti blokkeer systeem (abs)', 'electronic stability program',
        'electronic stability program (esp)', 'electronic brake distribution', 'electronic brake distribution (ebd)',
        'tractie controle systeem (tcs)', 'bandenspanningscontrolesysteem', 'led', 'premium kleur', 'speciale kleur',
        'kleur wit', 'toerenteller', 'buitentemperatuurmeter', 'achterruitverwarming', 'stuurbekrachtiging',
    ];

    /** Tikfouten en vreemde schrijfwijzen uit de feed. */
    private const RENAME = [
        'electronic climate controle' => 'Elektronische klimaatregeling',
        'elektronische climate control' => 'Elektronische klimaatregeling',
        'bots herkenning en activatie' => 'Botsherkenning en -activatie',
        'bots waarschuwing systeem' => 'Botswaarschuwingssysteem',
        'vermoeidheids herkenning' => 'Vermoeidheidsherkenning',
        'zijwind assistent' => 'Zijwindassistent',
        'achteruitrij assistent' => 'Achteruitrijassistent',
        'afdaal assistent' => 'Afdaalassistent',
        'uitwijk assistent' => 'Uitwijkassistent',
        'ambiant verlichting' => 'Sfeerverlichting',
        'parkeersensor voor en achter' => 'Parkeersensoren voor en achter',
        'elektrisch verstelb. passagiersstoel met geheugen' => 'Elektrisch verstelbare passagiersstoel met geheugen',
        'verstelbare (in hoogte) bestuurders stoel' => 'Bestuurdersstoel in hoogte verstelbaar',
        // Twee schrijfwijzen van hetzelfde → één regel.
        'voorstoelen verwarmd' => 'Verwarmde voorstoelen',
        'stoel ventilatie voor' => 'Stoelventilatie voor',
        'side-skirts' => 'Side skirts',
        'sport-line' => 'Sport Line',
    ];

    /** Algemene variant => specifiekere varianten: staat er één van die, dan valt de algemene weg. */
    private const SUPERSEDED = [
        'Cruise control' => ['Cruise control adaptief', 'Cruise control adaptief met Stop&Go'],
        'Cruise control adaptief' => ['Cruise control adaptief met Stop&Go'],
        'Keyless entry' => ['Keyless-pakket', 'Keyless-Go pakket', 'Comfort Access pakket'],
        'Keyless start' => ['Keyless-pakket', 'Keyless-Go pakket', 'Comfort Access pakket'],
        'Parkeersensor voor' => ['Parkeersensoren voor en achter'],
        'Parkeersensor achter' => ['Parkeersensoren voor en achter'],
        'LED koplampen' => ['Matrix LED koplampen', 'LED koplampen met laser', 'LED koplampen adaptief'],
        'Elektrisch bedienbare achterklep' => ['Elektrisch bedienbare achterklep met sensorsturing'],
        'Audio installatie' => ['Audio installatie premium', 'Audio installatie high-end'],
        'Audio installatie premium' => ['Audio installatie high-end'],
        'Telefoonintegratie' => ['Telefoonintegratie premium'],
        'Autotelefoonvoorbereiding' => ['Autotelefoonvoorbereiding met Bluetooth'],
        'Rijstrooksensor' => ['Rijstrooksensor met correctie'],
        'Dodehoek detector' => ['Dodehoekdetectie met correctie'],
        'Airco' => ['Airco automatisch', 'Elektronische klimaatregeling'],
        'Alarm' => ['Alarm klasse 3', 'Alarmsysteem'],
        'Alarmsysteem' => ['Alarm klasse 3'],
        'Navigatie-systeem' => ['Navigatie-systeem full map', 'Navigatie-systeem full map + hard disk', 'Navigatie full map'],
        'Navigatiesysteem' => ['Navigatie-systeem full map', 'Navigatie-systeem full map + hard disk', 'Navigatie full map'],
        'Navigatie-systeem full map' => ['Navigatie-systeem full map + hard disk'],
        'Panoramadak' => ['Elektrisch glazen panorama-dak'],
        'Sportstuur' => ['Lederen sportstuur'],
        'Lederen stuurwiel' => ['Lederen sportstuur', 'Multifunctioneel lederen stuurwiel'],
        'Stuurwiel multifunctioneel' => ['Multifunctioneel lederen stuurwiel'],
        'Mistlampen voor' => ['Mistlampen adaptief', 'LED mistlampen'],
        'Mistlampen' => ['Mistlampen voor', 'Mistlampen adaptief', 'LED mistlampen'],
    ];

    /** Twee losse regels die samen één ding zijn. */
    private const MERGE = [
        'Parkeersensoren voor en achter' => ['Parkeersensor voor', 'Parkeersensor achter'],
        'Elektrische ramen voor en achter' => ['Elektrische ramen voor', 'Elektrische ramen achter'],
        'Keyless entry en start' => ['Keyless entry', 'Keyless start'],
    ];

    /**
     * @param  array<int, mixed>  $raw  optielijst zoals in de database
     * @return list<string>
     */
    public static function clean(array $raw): array
    {
        $out = [];
        foreach ($raw as $o) {
            $o = Car::cleanOption((string) $o);
            $key = mb_strtolower($o);
            if ($o === '' || in_array($key, self::NOISE, true)) {
                continue;
            }
            $o = self::RENAME[$key] ?? $o;
            $out[mb_strtolower($o)] ??= $o; // dubbel (ook na hernoemen) = één regel
        }
        $out = array_values($out);

        $has = fn (string $o) => in_array($o, $out, true);
        $out = array_values(array_filter($out, fn ($o) => ! array_filter(self::SUPERSEDED[$o] ?? [], $has)));

        foreach (self::MERGE as $into => [$a, $b]) {
            if (in_array($a, $out, true) && in_array($b, $out, true)) {
                $out[array_search($a, $out, true)] = $into;
                $out = array_values(array_diff($out, [$b]));
            }
        }

        return self::mergeAirbags($out);
    }

    /**
     * @param  list<string>  $clean  uitvoer van clean()
     * @return array<string, list<string>>  groep => opties, in vaste volgorde, lege groepen weg
     */
    public static function groups(array $clean): array
    {
        $groups = array_fill_keys(self::ORDER, []);
        foreach ($clean as $o) {
            $groups[self::groupOf($o)][] = $o;
        }
        // Binnen een groep op alfabet: zo scan je het snelst naar "stoelverwarming";
        // de belangrijkste opties staan al als hoogtepunten bovenaan.
        foreach ($groups as &$list) {
            usort($list, 'strnatcasecmp');
        }

        return array_filter($groups);
    }

    private static function groupOf(string $option): string
    {
        $o = mb_strtolower($option);
        foreach (self::RULES as $pattern => $group) {
            if (preg_match($pattern . 'u', $o)) {
                return $group;
            }
        }

        return 'Overig';
    }

    /** Vijf losse airbagregels → "Airbags: bestuurder, passagier, hoofd en zij". */
    private static function mergeAirbags(array $options): array
    {
        $bags = array_filter($options, fn ($o) => preg_match('/airbag/i', $o));
        if (count($bags) < 2) {
            return $options;
        }
        $where = array_key_first($bags);
        $joined = mb_strtolower(implode(' ', $bags));
        $parts = array_keys(array_filter([
            'bestuurder' => str_contains($joined, 'bestuurder') || str_contains($joined, 'dubbele'),
            'passagier' => str_contains($joined, 'passagier') || str_contains($joined, 'dubbele'),
            'hoofd' => str_contains($joined, 'hoofd'),
            'zij' => str_contains($joined, 'side'),
            'knie' => str_contains($joined, 'knie'),
        ]));
        $label = $parts ? 'Airbags: ' . (count($parts) > 1 ? implode(', ', array_slice($parts, 0, -1)) . ' en ' . end($parts) : $parts[0]) : 'Airbags';

        $rest = array_diff_key($options, $bags);
        $rest[$where] = $label;
        ksort($rest);

        return array_values($rest);
    }
}
