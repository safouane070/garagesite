<?php

/*
|--------------------------------------------------------------------------
| Merk- en contactgegevens
|--------------------------------------------------------------------------
|
| Eén bron van waarheid voor alles wat over de showroom zelf gaat: contact,
| openingsuren, het huisstijl-accent en de vertrouwenscijfers. Views, de
| seeder en de sitemap lezen hier uit, zodat de eigenaar dit op één plek
| aanpast in plaats van verspreid door de blade-bestanden.
|
| De waarden hieronder zijn de ECHTE gegevens van de zaak (contact, uren,
| Google-score 4,7 · 227 reviews, FinancialLease-feed). Verandert er iets bij
| de zaak, dan hier bijwerken — of via de BRAND_*-keys in .env.
|
*/

return [
    // Het herkenbare accent. Zelfde waarde als brass.500 in tailwind.config.js
    // en de --brass CSS-variabele; hier voor server-side SVG-placeholders.
    'accent' => env('BRAND_ACCENT', '#D90429'),

    'contact' => [
        // Weergavevorm en tel:-vorm (zonder spaties) apart, zodat het nummer
        // netjes toont én klikbaar blijft.
        'phone'         => env('BRAND_PHONE', '085 130 40 58'),
        'phone_href'    => env('BRAND_PHONE_HREF', '+31851304058'),

        // Tweede (mobiele) nummer.
        'mobile'        => env('BRAND_MOBILE', '06 4467 48 48'),
        'mobile_href'   => env('BRAND_MOBILE_HREF', '+31644674848'),

        // WhatsApp: alleen cijfers met landcode (voor wa.me/…).
        'whatsapp'      => env('BRAND_WHATSAPP', '31611565177'),

        'email'         => env('BRAND_EMAIL', 'info@autobedrijfrijswijk.nl'),
        'address'       => env('BRAND_ADDRESS', 'Poldermeesterstraat 16, 2288 GV Rijswijk'),
        'city'          => env('BRAND_CITY', 'Rijswijk'),

        // Korte samenvatting (footer-regel) + gedetailleerd per dag (contactpagina).
        'hours'         => env('BRAND_HOURS', 'Ma–Za · 09:00–18:00'),
    ],

    // Openingstijden per dag. Losse "note" voor de "bel even vooraf"-melding.
    'opening_hours' => [
        'note' => 'Bel altijd even voordat u langskomt, zodat we tijd voor u kunnen vrijmaken.',
        'days' => [
            ['label' => 'Maandag t/m vrijdag', 'value' => '09:00 – 18:00'],
            ['label' => 'Zaterdag',            'value' => '09:00 – 18:00'],
            ['label' => 'Zondag',              'value' => 'Op afspraak'],
        ],
    ],

    // Google Maps: de zoekopdracht voor de "route"-knop en de embed-kaart.
    'maps' => [
        'query' => env('BRAND_MAPS_QUERY', 'Autobedrijf Rijswijk, Poldermeesterstraat 16, 2288 GV Rijswijk'),
    ],

    // Cijfers die als belofte op de site staan. Config i.p.v. losse tekst,
    // zodat ze kloppen én centraal aanpasbaar zijn.
    'trust' => [
        'inspection_points' => (int) env('BRAND_INSPECTION_POINTS', 40),
        'warranty_months'   => (int) env('BRAND_WARRANTY_MONTHS', 12),
    ],

    // Echte Google-reviews (score + aantal zoals op het Google Bedrijfsprofiel
    // van de zaak). Werk deze bij wanneer Google het aantal aanpast, en vul de
    // exacte review-URL in (Google Bedrijfsprofiel → "Reviews delen").
    'reviews' => [
        'rating' => (float) env('BRAND_REVIEW_RATING', 4.7),
        'count'  => (int) env('BRAND_REVIEW_COUNT', 227),
        // Echte Google-bedrijfslink (CID van het Google-profiel — opent de zaak
        // met reviews). Geverifieerd op Google Maps: 4,7 · 227 reviews.
        'url'    => env('BRAND_REVIEW_URL', 'https://www.google.com/maps?cid=12874092222779176954'),
        // Live Trustindex-review-widget van de zaak (auto-updatend). Dit is de
        // publieke loader-ID uit hun eigen site (sectie "wat onze klanten
        // zeggen"). Leeg laten = terugval op de statische citaten hieronder.
        'trustindex_widget_id' => env('BRAND_TRUSTINDEX_WIDGET', 'd143df026f54805b969687b3cea'),
    ],

    // Financial lease loopt via onze leasepartner FinancialLease.nl (widget met
    // echte maandbedragen). stock_id = de dealer-feed van de zaak.
    'finance' => [
        'financiallease_stock_id' => env('BRAND_FL_STOCK_ID', '1262'),
    ],

    // Echte reviews van het Google/Trustindex-profiel van de zaak (verkorte
    // citaten, geen verzinsels). Vervang eventueel door de live Trustindex-widget.
    'testimonials' => [
        ['name' => 'Martijn J.', 'text' => 'Gewoon fantastisch geholpen bij het vinden van mijn droomauto! Van de uitgebreide testrit tot de aflevering was alles uitstekend geregeld. De service is echt top.'],
        ['name' => 'Teus D.', 'text' => 'Keurig netjes geholpen. Er wordt alle tijd voor je genomen om alle opties goed door te nemen. Uiterst vriendelijk personeel — en de prijzen zijn superscherp.'],
        ['name' => 'Joze M.', 'text' => 'Zeer vriendelijke ontvangst, heeft alle tijd genomen voor de uitleg. Nette inruilprijs. Kortom: een fijne deal en tevreden met de aankoop.'],
    ],

    // Officiële juridische documenten van de zaak (we verzinnen geen eigen tekst).
    'legal' => [
        'terms_url'   => env('BRAND_TERMS_URL', 'https://autobedrijfrijswijk.nl/algemene-voorwaarden/'),
        'privacy_url' => env('BRAND_PRIVACY_URL', 'https://autobedrijfrijswijk.nl/privacy-policy/'),
    ],
];
