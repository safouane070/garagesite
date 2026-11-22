<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * De advertenties van de dealer op Marktplaats: daar staan per auto veel meer gegevens
 * (vermogen, verbruik, APK, energielabel, eigenaren, gewicht, …) dan op de eigen site.
 * Gebruikt door `cars:enrich`. robots.txt van Marktplaats staat advertentie- en
 * profielpagina's toe (alleen varianten met zoekparameters zijn uitgesloten).
 */
class Marktplaats
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    private const BASE = 'https://www.marktplaats.nl';

    /** Hoeveel profielpagina's we hoogstens doorlopen (30 advertenties per pagina). */
    private const MAX_PAGES = 15;

    /**
     * Alle advertentie-paden van de verkoper, bv. "/v/auto-s/audi/m2468195560-audi-q8-…".
     *
     * @return list<string>
     */
    public static function listingPaths(string $sellerPath): array
    {
        $paths = [];
        $base = rtrim($sellerPath, '/');
        for ($page = 1; $page <= self::MAX_PAGES; $page++) {
            $resp = self::request(self::BASE . $base . ($page > 1 ? "/p/{$page}/" : '/'));
            if (! $resp->ok()) {
                break;
            }
            // Verandert de profielnaam (autobedrijf-rijswijk → bs-autobedrijf-rijswijk), dan
            // stuurt Marktplaats elke /p/N/ door naar pagina 1. Daarom pagineren we op het
            // adres waar pagina 1 echt uitkwam.
            if ($page === 1 && ($final = $resp->effectiveUri()?->getPath())) {
                $base = rtrim($final, '/');
            }
            $html = $resp->body();
            preg_match_all('#/v/auto-s/[a-z0-9-]+/m\d+-[a-z0-9-]+#', $html, $m);
            $new = array_diff(array_unique($m[0]), $paths);
            if ($new === []) {
                break; // voorbij de laatste pagina: Marktplaats toont dan weer pagina 1 of niets
            }
            array_push($paths, ...$new);
        }

        return array_values($paths);
    }

    public static function idFromPath(string $path): ?string
    {
        return preg_match('#/m(\d+)-#', $path, $m) ? $m[1] : null;
    }

    public static function listing(string $path): ?array
    {
        $html = self::get(self::BASE . $path);

        return $html === null ? null : self::parse($html);
    }

    /**
     * Leest de autokenmerken uit de advertentie-HTML (het JSON-blok window.__CONFIG__).
     *
     * @return array{year:int,mileage:int,brand:string,price:int,attributes:array<string,string>}|null
     */
    public static function parse(string $html): ?array
    {
        $start = strpos($html, 'window.__CONFIG__ = ');
        if ($start === false) {
            return null;
        }
        $i = $start + strlen('window.__CONFIG__ = ');
        $depth = 0;
        $len = strlen($html);
        for ($j = $i; $j < $len; $j++) {
            if ($html[$j] === '{') {
                $depth++;
            } elseif ($html[$j] === '}' && --$depth === 0) {
                break;
            }
        }
        $listing = json_decode(substr($html, $i, $j - $i + 1), true)['listing'] ?? null;
        if (! is_array($listing)) {
            return null;
        }

        $attr = [];
        foreach ($listing['carAttributes']['groupedWithIcons'] ?? [] as $group) {
            foreach ($group['attributes'] ?? [] as $a) {
                if (isset($a['key'], $a['value']) && is_scalar($a['value']) && trim((string) $a['value']) !== '') {
                    $attr[$a['key']] ??= trim((string) $a['value']);
                }
            }
        }
        // Het kenteken staat soms alleen in het automotive-blok.
        $plate = $listing['automotive']['licensePlate'] ?? $listing['carDetails']['licensePlate'] ?? null;
        if (is_string($plate) && $plate !== '') {
            $attr['licensePlate'] ??= $plate;
        }

        return [
            'year' => (int) self::digits($attr['constructionYear'] ?? ''),
            'mileage' => (int) self::digits($attr['mileage'] ?? ''),
            'brand' => (string) ($attr['brand'] ?? ''),
            'price' => (int) round(($listing['priceInfo']['priceCents'] ?? 0) / 100),
            'attributes' => $attr,
        ];
    }

    /**
     * Marktplaats-kenmerken → onze spec-sleutels (zie Car::SPEC_FIELDS). Getallen als getal,
     * zodat de weergave zelf eenheden en notatie kiest. Onbekend of leeg → weggelaten.
     *
     * @param  array<string,string>  $a
     * @return array<string,int|float|string>
     */
    public static function specs(array $a): array
    {
        $int = fn (string $k) => isset($a[$k]) ? ((int) self::digits($a[$k]) ?: null) : null;
        $dec = fn (string $k) => isset($a[$k]) && is_numeric($v = str_replace(',', '.', preg_replace('/[^\d.,]/', '', $a[$k]))) ? (float) $v : null;

        return array_filter([
            'vermogen_pk' => $int('powerInHorsePower'),
            'motorinhoud_l' => $dec('cylinderCapacity'),
            'cilinders' => $int('numberOfCylinders'),
            'koppel_nm' => isset($a['torque']) && preg_match('/(\d+)\s*nm/i', $a['torque'], $t) ? (int) $t[1] : null,
            'versnellingen' => isset($a['transmission']) && preg_match('/(\d+)\s*versnelling/i', $a['transmission'], $g) ? (int) $g[1] : null,
            'aandrijving' => $a['powerWheelDriver'] ?? null,
            'topsnelheid_kmh' => $int('topSpeed'),
            'acceleratie_0_100' => $dec('acceleration'),
            'verbruik_km_l' => $dec('fuelConsumption'),
            'co2_g_km' => $int('co2emission'),
            'energielabel' => isset($a['energyLabel']) ? Str::upper(Str::limit($a['energyLabel'], 3, '')) : null,
            'euronorm' => $a['euronormBE'] ?? null,
            'hybride_type' => $a['hybridType'] ?? null,
            'accu_kwh' => $dec('batteryCapacity'),
            'deuren' => $int('numberOfDoors'),
            'zitplaatsen' => $int('numberOfSeats'),
            'bekleding' => $a['upholstery'] ?? null,
            'gewicht_kg' => $int('emptyWeight'),
            'trekgewicht_geremd_kg' => $int('maxWeightWithBrakes'),
            'trekgewicht_ongeremd_kg' => $int('maxWeightWithoutBrakes'),
            'lengte_m' => $dec('lengthInMeters'),
            'breedte_m' => $dec('widthInMeters'),
            'hoogte_m' => $dec('heightInMeters'),
            'tankinhoud_l' => $int('fuelTankCapacity'),
            'apk_tot' => $a['dateApk'] ?? null,
            'onderhoud' => $a['serviceHistory'] ?? null,
            'eigenaren' => $int('totalNumberOfOwners'),
            'eerste_toelating_nl' => $a['firstRecordInNl'] ?? null,
            'nieuwprijs' => $int('newCarPrice'),
            'kenteken' => isset($a['licensePlate']) ? Str::upper($a['licensePlate']) : null,
        ], fn ($v) => $v !== null && $v !== '');
    }

    /** "1.766" (NL-duizendtal) → "1766"; "€ 41.686" → "41686". */
    private static function digits(string $value): string
    {
        return preg_replace('/\D/', '', $value);
    }

    private static function get(string $url): ?string
    {
        $resp = self::request($url);

        return $resp->ok() ? $resp->body() : null;
    }

    private static function request(string $url): \Illuminate\Http\Client\Response
    {
        return Http::withHeaders(['User-Agent' => self::UA, 'Accept' => 'text/html'])
            ->timeout(25)
            ->retry(2, 1000, throw: false)
            ->get($url);
    }
}
