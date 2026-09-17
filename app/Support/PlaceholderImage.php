<?php

namespace App\Support;

/**
 * Genereert nette, in-huisstijl SVG-placeholderfoto's voor demo-auto's.
 *
 * We tekenen een strak auto-silhouet op een donkere studio-achtergrond met
 * een warme accentkleur. Zo ogen de seeddata's bewust ontworpen (geen
 * generieke grijze vlakken) en is er geen internet of stockfoto nodig.
 */
class PlaceholderImage
{
    /** De vier "opnames" die we per auto genereren voor de carousel. */
    public const VIEWS = ['Vooraanzicht', 'Zijaanzicht', 'Achteraanzicht', 'Interieur'];

    /**
     * Bouw een SVG-string.
     *
     * @param  string  $brand   Merk, bv. "BMW"
     * @param  string  $model   Model + uitvoering, bv. "320d M Sport"
     * @param  string  $view    Een van self::VIEWS
     * @param  string|null  $accent  Hex-accentkleur; valt terug op config('brand.accent').
     */
    public static function svg(string $brand, string $model, string $view, ?string $accent = null): string
    {
        $accent = $accent ?? config('brand.accent', '#C8A24A');
        $brand = htmlspecialchars(strtoupper($brand), ENT_QUOTES);
        $model = htmlspecialchars($model, ENT_QUOTES);
        $view = htmlspecialchars($view, ENT_QUOTES);

        $motif = $view === 'Interieur'
            ? self::interiorMotif($accent)
            : self::carMotif($accent, $view);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1600 1000" role="img" aria-label="$brand $model — $view">
  <defs>
    <linearGradient id="bg" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#191c23"/>
      <stop offset="0.55" stop-color="#0f1116"/>
      <stop offset="1" stop-color="#0a0b0e"/>
    </linearGradient>
    <radialGradient id="spot" cx="0.5" cy="0.42" r="0.6">
      <stop offset="0" stop-color="$accent" stop-opacity="0.20"/>
      <stop offset="0.6" stop-color="$accent" stop-opacity="0.04"/>
      <stop offset="1" stop-color="$accent" stop-opacity="0"/>
    </radialGradient>
    <linearGradient id="floor" x1="0" y1="0" x2="0" y2="1">
      <stop offset="0" stop-color="#22262f"/>
      <stop offset="1" stop-color="#0a0b0e"/>
    </linearGradient>
  </defs>

  <rect width="1600" height="1000" fill="url(#bg)"/>
  <rect width="1600" height="1000" fill="url(#spot)"/>

  <!-- studiovloer -->
  <rect x="0" y="720" width="1600" height="280" fill="url(#floor)"/>
  <line x1="0" y1="720" x2="1600" y2="720" stroke="$accent" stroke-opacity="0.18"/>

  $motif

  <!-- tekstlaag -->
  <text x="90" y="140" fill="#f4f2ec" font-family="Georgia, 'Times New Roman', serif" font-size="66" font-weight="700" letter-spacing="2">$brand</text>
  <text x="92" y="188" fill="$accent" font-family="Arial, Helvetica, sans-serif" font-size="30" letter-spacing="6">$model</text>

  <text x="90" y="930" fill="#8b8f98" font-family="Arial, Helvetica, sans-serif" font-size="22" letter-spacing="7">KROON AUTOMOBIELEN</text>
  <text x="1510" y="930" text-anchor="end" fill="#b9bcc4" font-family="Arial, Helvetica, sans-serif" font-size="24" letter-spacing="3">$view</text>

  <rect x="40" y="40" width="1520" height="920" fill="none" stroke="#ffffff" stroke-opacity="0.06" rx="8"/>
</svg>
SVG;
    }

    /** Zijprofiel-silhouet van een auto, opgebouwd uit nette vormen. */
    private static function carMotif(string $accent, string $view): string
    {
        // Spiegel het silhouet bij het achteraanzicht voor wat variatie.
        $transform = $view === 'Achteraanzicht'
            ? 'transform="translate(1600,0) scale(-1,1)"'
            : '';

        return <<<SVG
  <g $transform>
    <!-- grondschaduw -->
    <ellipse cx="800" cy="712" rx="470" ry="34" fill="#000000" opacity="0.55"/>

    <!-- carrosserie -->
    <path d="M 360 648
             Q 356 600 404 586
             L 545 560
             Q 585 500 660 470
             L 720 448
             Q 760 436 812 436
             L 1010 436
             Q 1085 438 1120 500
             L 1178 566
             L 1255 582
             Q 1292 590 1288 634
             L 1284 648
             Z"
          fill="#2b2f39" stroke="$accent" stroke-opacity="0.55" stroke-width="2" stroke-linejoin="round"/>

    <!-- ruiten -->
    <path d="M 600 556 Q 636 506 700 480 L 806 458 L 806 556 Z" fill="#0c1116" opacity="0.9"/>
    <path d="M 838 458 L 1000 458 Q 1066 460 1096 512 L 1120 556 L 838 556 Z" fill="#0c1116" opacity="0.9"/>

    <!-- accent-lichtlijn -->
    <path d="M 404 620 L 1270 620" stroke="$accent" stroke-opacity="0.35" stroke-width="3"/>

    <!-- wielen -->
    <g>
      <circle cx="560" cy="656" r="92" fill="#0b0c10"/>
      <circle cx="560" cy="656" r="92" fill="none" stroke="$accent" stroke-opacity="0.7" stroke-width="4"/>
      <circle cx="560" cy="656" r="40" fill="none" stroke="#6b7280" stroke-width="6"/>
      <circle cx="560" cy="656" r="10" fill="$accent"/>
    </g>
    <g>
      <circle cx="1090" cy="656" r="92" fill="#0b0c10"/>
      <circle cx="1090" cy="656" r="92" fill="none" stroke="$accent" stroke-opacity="0.7" stroke-width="4"/>
      <circle cx="1090" cy="656" r="40" fill="none" stroke="#6b7280" stroke-width="6"/>
      <circle cx="1090" cy="656" r="10" fill="$accent"/>
    </g>
  </g>
SVG;
    }

    /** Abstract dashboard/stuur-motief voor het interieur-aanzicht. */
    private static function interiorMotif(string $accent): string
    {
        return <<<SVG
  <g>
    <ellipse cx="800" cy="712" rx="420" ry="30" fill="#000000" opacity="0.45"/>
    <!-- dashboardlijn -->
    <path d="M 320 470 Q 800 420 1280 470 L 1280 560 Q 800 520 320 560 Z" fill="#20242c"/>
    <!-- stuur -->
    <circle cx="620" cy="600" r="140" fill="none" stroke="#3a3f4a" stroke-width="26"/>
    <circle cx="620" cy="600" r="140" fill="none" stroke="$accent" stroke-opacity="0.5" stroke-width="4"/>
    <circle cx="620" cy="600" r="34" fill="#2b2f39" stroke="$accent" stroke-opacity="0.7" stroke-width="3"/>
    <path d="M 620 634 L 620 720 M 500 600 L 586 600 M 654 600 L 740 600" stroke="#3a3f4a" stroke-width="24" stroke-linecap="round"/>
    <!-- instrumenten -->
    <circle cx="620" cy="600" r="8" fill="$accent"/>
    <!-- middenscherm -->
    <rect x="900" y="520" width="240" height="150" rx="12" fill="#0c1116" stroke="$accent" stroke-opacity="0.4" stroke-width="2"/>
    <line x1="930" y1="560" x2="1110" y2="560" stroke="$accent" stroke-opacity="0.5" stroke-width="4"/>
    <line x1="930" y1="592" x2="1060" y2="592" stroke="#4b5563" stroke-width="4"/>
    <line x1="930" y1="620" x2="1090" y2="620" stroke="#4b5563" stroke-width="4"/>
  </g>
SVG;
    }
}
