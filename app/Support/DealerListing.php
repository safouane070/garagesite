<?php

namespace App\Support;

use App\Models\Car;
use Illuminate\Support\Collection;

/**
 * Koppelt onze auto's aan de voertuig-pagina's van de dealersite. De
 * voertuig-slug komt uit de originele Marktplaats-listings (zelfde bron waaruit
 * de auto's zijn geseed); de match gaat op genormaliseerde slug (koppeltekens
 * en punten weg, zodat "1-4-tfsi" == "14-tfsi") via de langste gemeenschappelijke
 * voorloop en een 1-op-1-toewijzing, zodat bijna-identieke varianten niet door
 * elkaar raken. Gedeeld door cars:fill-options en cars:prune-gone.
 */
class DealerListing
{
    public const BASE = 'https://autobedrijfrijswijk.nl/voertuig/';

    /** Dealer-slugs (deel ná "m<cijfers>-") uit de listing-paden. */
    public static function dealerSlugs(array $listingPaths): array
    {
        $slugs = [];
        foreach ($listingPaths as $path) {
            if (preg_match('#/m\d+-(.+)$#', $path, $m)) {
                $slugs[] = $m[1];
            }
        }

        return $slugs;
    }

    /**
     * @return array<int,string>  carId => dealerSlug (1-op-1)
     */
    public static function match(Collection $cars, array $dealerSlugs): array
    {
        $norm = fn (string $s): string => preg_replace('/[^a-z0-9]/', '', strtolower($s));

        $pairs = [];
        foreach ($dealerSlugs as $di => $ds) {
            $dn = $norm($ds);
            foreach ($cars as $car) {
                $cn = $norm($car->slug);
                if ($cn === '') {
                    continue;
                }
                $score = self::commonPrefixLength($dn, $cn);
                if ($score >= min(strlen($cn), 20)) {
                    $pairs[] = ['score' => $score, 'di' => $di, 'cid' => $car->id, 'slug' => $ds];
                }
            }
        }

        usort($pairs, fn ($a, $b) => $b['score'] <=> $a['score']);

        $usedDealer = [];
        $usedCar = [];
        $out = [];
        foreach ($pairs as $p) {
            if (isset($usedDealer[$p['di']]) || isset($usedCar[$p['cid']])) {
                continue;
            }
            $usedDealer[$p['di']] = true;
            $usedCar[$p['cid']] = true;
            $out[$p['cid']] = $p['slug'];
        }

        return $out;
    }

    private static function commonPrefixLength(string $a, string $b): int
    {
        $n = min(strlen($a), strlen($b));
        $i = 0;
        while ($i < $n && $a[$i] === $b[$i]) {
            $i++;
        }

        return $i;
    }
}
