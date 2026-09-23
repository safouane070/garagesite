<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Alle kennis over de voorraad op de dealersite (autobedrijfrijswijk.nl) op één
 * plek, gebruikt door `cars:sync`.
 *
 * - Voorraadlijst: de publieke WordPress REST API (/wp-json/wp/v2/voertuig). De
 *   robots.txt van de dealer staat /voertuig/ expliciet toe.
 * - Details (prijs, specs, opties, foto's): de voertuigpagina zelf. Die HTML
 *   komt uit een paginabouwer en kan veranderen; daarom valideert parse() streng
 *   en geeft het bij twijfel een reden terug i.p.v. halve gegevens.
 */
class DealerSite
{
    private const UA = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120 Safari/537.36';

    /** Bekende merknamen (langste eerst) om "merk + model" uit een titel te halen. */
    public const MAKES = [
        'Mercedes-Benz', 'Alfa Romeo', 'Land Rover', 'Volkswagen', 'Porsche', 'BMW', 'Audi',
        'Volvo', 'Tesla', 'Cupra', 'Kia', 'Peugeot', 'Honda', 'SEAT', 'Škoda', 'Skoda',
        'Ford', 'Renault', 'Opel', 'Toyota', 'Nissan', 'Mazda', 'Hyundai', 'MINI', 'Mini',
    ];

    /** Alle foto's (de dealer zet er 20–40 per auto, interieur achteraan); dit is alleen een vangrail. */
    private const MAX_PHOTOS = 60;

    /**
     * De volledige actuele voorraadlijst. Gooit een exception als ook maar één
     * pagina faalt: een halve lijst mag nooit als "de voorraad" gelden.
     *
     * @return list<array{slug:string,title:string,link:string,modified:string,media:int}>
     */
    public static function vehicles(): array
    {
        // Zelfde domein als deze site = we zouden onszelf uitlezen (na livegang op
        // het oude domein staat de WordPress-site daar niet meer).
        if (parse_url(self::base(), PHP_URL_HOST) === parse_url(config('app.url'), PHP_URL_HOST)) {
            throw new RuntimeException('DEALER_SITE_URL wijst naar deze site zelf; zet het adres van de WordPress-site of laat het leeg.');
        }

        $out = [];
        $page = 1;

        do {
            $resp = self::http()->get(self::base() . '/wp-json/wp/v2/voertuig', [
                'per_page' => 100,
                'page' => $page,
                '_fields' => 'slug,title,link,modified,featured_media',
            ]);

            if (! $resp->ok() || ! is_array($resp->json())) {
                throw new RuntimeException("Voorraadlijst (pagina {$page}) niet op te halen: HTTP {$resp->status()}");
            }

            foreach ($resp->json() as $v) {
                $out[] = [
                    'slug' => (string) $v['slug'],
                    'title' => trim(html_entity_decode((string) ($v['title']['rendered'] ?? ''), ENT_QUOTES | ENT_HTML5)),
                    'link' => (string) $v['link'],
                    'modified' => (string) $v['modified'],
                    'media' => (int) ($v['featured_media'] ?? 0),
                ];
            }

            $pages = max(1, (int) $resp->header('X-WP-TotalPages'));
            $page++;
        } while ($page <= $pages);

        return $out;
    }

    public static function base(): string
    {
        return rtrim((string) config('brand.dealer_site_url'), '/');
    }

    /**
     * WordPress geeft absolute links terug met z'n eigen site-adres. Staat dat nog
     * op het domein dat deze site inmiddels heeft overgenomen, dan zouden we onszelf
     * ophalen: zulke links gaan naar de echte bron (DEALER_SITE_URL).
     */
    private static function onDealer(string $url): string
    {
        $parts = parse_url($url);
        if (($parts['host'] ?? null) !== parse_url(config('app.url'), PHP_URL_HOST)) {
            return $url;
        }

        return self::base() . ($parts['path'] ?? '/') . (isset($parts['query']) ? '?' . $parts['query'] : '');
    }

    public static function page(string $url): ?string
    {
        $resp = self::http()->get(self::onDealer($url));

        return $resp->ok() ? $resp->body() : null;
    }

    public static function mediaUrl(int $id): ?string
    {
        if ($id <= 0) {
            return null;
        }
        $resp = self::http()->get(self::base() . "/wp-json/wp/v2/media/{$id}", ['_fields' => 'source_url']);

        return $resp->ok() ? ($resp->json('source_url') ?: null) : null;
    }

    public static function download(string $url): ?string
    {
        $resp = self::http()->timeout(40)->get(self::onDealer($url));

        return $resp->ok() ? $resp->body() : null;
    }

    /**
     * Leest een voertuigpagina uit. Geeft een array met genormaliseerde velden
     * terug, of een string met de reden waarom de pagina niet betrouwbaar is.
     *
     * @param  list<string>  $knownModels  modelnamen uit onze eigen database (woordenboek)
     */
    public static function parse(string $html, string $title, ?string $coverUrl, array $knownModels = []): array|string
    {
        $rows = self::specRows($html);

        $price = preg_match('#id="span-375-134"[^>]*>\s*([\d.\s]+)<#', $html, $m) ? (int) preg_replace('/\D/', '', $m[1]) : 0;
        $year = (int) preg_replace('/\D/', '', $rows['Bouwjaar'] ?? '');
        $mileage = (int) preg_replace('/\D/', '', $rows['Kilometerstand'] ?? '');
        $fuel = self::mapFuel($rows['Brandstof'] ?? '');

        if ($price < 500 || $price > 2_000_000) {
            return 'geen geldige prijs';
        }
        if ($year < 1950 || $year > (int) date('Y') + 1) {
            return 'geen geldig bouwjaar';
        }
        if (! isset($rows['Kilometerstand']) || $mileage > 2_000_000) {
            return 'geen geldige kilometerstand';
        }
        if ($fuel === null) {
            return 'onbekende brandstof "' . ($rows['Brandstof'] ?? '') . '"';
        }

        $photos = self::gallery($html, $coverUrl);
        if ($photos === []) {
            return "geen foto's";
        }

        [$brand, $model, $variant] = self::splitTitle($title, $knownModels);

        $transmission = preg_match('#Transmissie.{0,400}?<span[^>]*>\s*([^<]+?)\s*</span>#s', $html, $t) && Str::startsWith(Str::lower($t[1]), 'a')
            ? 'Automaat'
            : 'Handgeschakeld';

        return [
            'brand' => $brand,
            'model' => $model,
            'variant' => $variant,
            'year' => $year,
            'price' => $price,
            'mileage' => $mileage,
            'fuel_type' => $fuel,
            'transmission' => $transmission,
            'color' => Str::ucfirst($rows['Kleur'] ?? 'Onbekend'),
            'body_type' => self::mapBody($rows['Caressorie'] ?? $rows['Carrosserie'] ?? ''),
            'specs' => array_filter(['deuren' => (int) ($rows['Aantal deuren'] ?? 0) ?: null]),
            'options' => self::options($html),
            'photos' => $photos,
        ];
    }

    /** Label → waarde uit de specificatietabel ("Bouwjaar" → "2021"). */
    private static function specRows(string $html): array
    {
        $labels = ['Aantal versnellingen', 'Aantal deuren', 'Kilometerstand', 'Bouwjaar', 'Brandstof', 'Caressorie', 'Carrosserie', 'Kleur'];

        preg_match_all('#class="ct-div-block table-row"[^>]*>(.*?)</div>\s*</div>#s', $html, $m);

        $rows = [];
        foreach ($m[1] as $block) {
            $text = trim(preg_replace('/\s+/u', ' ', html_entity_decode(strip_tags(str_replace('<', ' <', $block)), ENT_QUOTES | ENT_HTML5)));
            foreach ($labels as $label) {
                if (Str::startsWith($text, $label . ' ')) {
                    $rows[$label] = trim(Str::after($text, $label . ' '));
                    break;
                }
            }
        }

        return $rows;
    }

    /** Uitrusting uit de "optionstest"-lijst. */
    public static function options(string $html): array
    {
        if (! preg_match('#<ul[^>]*optionstest[^>]*>(.*?)</ul>#is', $html, $m)) {
            return [];
        }
        preg_match_all('#<li[^>]*>(.*?)</li>#is', $m[1], $lis);

        $out = [];
        foreach ($lis[1] as $li) {
            // Spaties normaliseren en verdubbelde inch-tekens (18""") terug naar één.
            $label = trim(preg_replace(['/\s+/', '/"{2,}/'], [' ', '"'], html_entity_decode(strip_tags($li), ENT_QUOTES)));
            if ($label !== '' && mb_strlen($label) <= 80) {
                $out[$label] = true;
            }
        }

        return array_keys($out);
    }

    /**
     * Foto's van déze auto: alle uploads met hetzelfde beeld-ID als de hoofdfoto
     * ("56623861-1.jpg", "56623861-2.jpg", …). Zo komen foto's van de
     * "vergelijkbare auto's" op dezelfde pagina niet mee.
     *
     * @return list<string> absolute URL's, in galerijvolgorde
     */
    private static function gallery(string $html, ?string $coverUrl): array
    {
        if (! $coverUrl || ! preg_match('#/(\d+)-\d+(?:-scaled)?\.(?:jpe?g|webp)$#i', $coverUrl, $id)) {
            return [];
        }

        preg_match_all('#https?://[^"\'\s)]+/' . $id[1] . '-(\d+)(-\d+x\d+|-scaled)?\.(jpe?g|webp)#i', $html, $m, PREG_SET_ORDER);

        // Per fotonummer de grootste *webversie* (hoogstens 2048 px breed). Het
        // camera-origineel kan tientallen megapixels zijn: traag en geheugenvretend.
        // Alleen als er geen webversie is, valt het terug op "-scaled" of het origineel.
        $best = [];
        foreach ($m as [$url, $n, $size]) {
            $width = $size === '' || $size === '-scaled' ? 0 : (int) Str::before(ltrim($size, '-'), 'x');
            $rank = match (true) {
                $width > 0 && $width <= 2048 => 10_000 + $width,
                $size === '-scaled' => 2,
                $size === '' => 1,
                default => 0, // webversie breder dan 2048: liever niet
            };
            if (! isset($best[$n]) || $rank > $best[$n][0]) {
                $best[$n] = [$rank, $url];
            }
        }
        // Hoofdfoto zelf telt altijd mee (ook als de HTML 'm alleen verkleind toont).
        $best[1] ??= [0, $coverUrl];

        ksort($best, SORT_NUMERIC);

        return array_slice(array_column($best, 1), 0, self::MAX_PHOTOS);
    }

    /** @return array{0:string,1:string,2:?string} [merk, model, uitvoering] */
    public static function splitTitle(string $title, array $knownModels = []): array
    {
        $title = trim(preg_replace('/\s+/', ' ', $title));
        $brand = null;
        foreach (self::MAKES as $make) {
            if (Str::startsWith(Str::lower($title), Str::lower($make) . ' ')) {
                $brand = $make === 'Mini' ? 'MINI' : ($make === 'Skoda' ? 'Škoda' : $make);
                $rest = trim(Str::substr($title, Str::length($make)));
                break;
            }
        }
        if ($brand === null) {
            [$brand, $rest] = array_pad(explode(' ', $title, 2), 2, '');
        }

        // Langste bekende modelnaam (uit onze eigen data) die de rest opent.
        $model = null;
        usort($knownModels, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a));
        foreach ($knownModels as $known) {
            if ($known !== '' && Str::startsWith(Str::lower($rest . ' '), Str::lower($known) . ' ')) {
                $model = Str::substr($rest, 0, mb_strlen($known));
                break;
            }
        }
        // Terugval: eerste woord, met "Model Y" / "3 Serie" als twee woorden.
        if ($model === null) {
            $words = explode(' ', $rest);
            $model = in_array($words[0], ['Model'], true) || ctype_digit($words[0])
                ? implode(' ', array_slice($words, 0, 2))
                : $words[0];
        }

        $variant = trim(explode('/', trim(Str::substr($rest, mb_strlen($model))))[0]);

        return [$brand, $model, $variant !== '' ? $variant : null];
    }

    /**
     * Koppelt bestaande auto's aan dealer-slugs via de langste gemeenschappelijke
     * voorloop van de genormaliseerde slug ("1-4-tfsi" == "14-tfsi"), 1-op-1.
     *
     * @param  list<string>  $dealerSlugs
     * @return array<int,string> carId => dealerSlug
     */
    public static function match(Collection $cars, array $dealerSlugs): array
    {
        $norm = fn (string $s): string => preg_replace('/[^a-z0-9]/', '', strtolower($s));

        $pairs = [];
        foreach ($dealerSlugs as $ds) {
            $dn = $norm($ds);
            foreach ($cars as $car) {
                $cn = $norm($car->slug);
                if ($cn === '') {
                    continue;
                }
                $n = min(strlen($dn), strlen($cn));
                $score = 0;
                while ($score < $n && $dn[$score] === $cn[$score]) {
                    $score++;
                }
                if ($score >= min(strlen($cn), 20)) {
                    $pairs[] = [$score, $ds, $car->id];
                }
            }
        }

        usort($pairs, fn ($a, $b) => $b[0] <=> $a[0]);

        $usedSlug = [];
        $out = [];
        foreach ($pairs as [, $slug, $carId]) {
            if (isset($usedSlug[$slug]) || isset($out[$carId])) {
                continue;
            }
            $usedSlug[$slug] = true;
            $out[$carId] = $slug;
        }

        return $out;
    }

    /** Brandstof staat er als code ("B", "B,E") óf voluit ("Benzine", "Hybride"). */
    private static function mapFuel(string $value): ?string
    {
        $v = Str::lower(trim($value));
        $parts = array_map('trim', explode(',', $v));

        return match (true) {
            str_contains($v, 'hybr') || (count($parts) > 1 && in_array('e', $parts, true)) => 'Hybride',
            str_contains($v, 'elektr') || $parts === ['e'] => 'Elektrisch',
            str_contains($v, 'benz') || $parts === ['b'] => 'Benzine',
            str_contains($v, 'dies') || $parts === ['d'] => 'Diesel',
            str_contains($v, 'lpg') || str_contains($v, 'gas') || in_array($parts[0], ['l', 'g'], true) => 'LPG',
            default => null,
        };
    }

    private static function mapBody(string $value): ?string
    {
        $v = Str::lower($value);

        return match (true) {
            Str::contains($v, ['suv', 'terrein']) => 'SUV',
            Str::contains($v, 'station') => 'Stationwagen',
            Str::contains($v, 'mpv') => 'MPV',
            Str::contains($v, ['coupé', 'coupe']) => 'Coupé',
            Str::contains($v, 'cabrio') => 'Cabrio',
            Str::contains($v, 'hatch') => 'Hatchback',
            Str::contains($v, ['sedan', 'limousine']) => 'Sedan',
            default => null,
        };
    }

    private static function http()
    {
        return Http::withHeaders(['User-Agent' => self::UA, 'Accept' => 'application/json, text/html'])
            ->timeout(25)
            ->retry(2, 500, throw: false);
    }
}
