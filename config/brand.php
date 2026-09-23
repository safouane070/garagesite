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
        // Publieke Trustindex-widget-ID van de zaak — databron voor
        // `php artisan reviews:fetch`, dat de echte reviews ophaalt en cachet.
        // Niet in de HTML embed (we tonen ze in eigen stijl), puur als bron.
        'trustindex_widget_id' => env('BRAND_TRUSTINDEX_WIDGET', 'd143df026f54805b969687b3cea'),
    ],

    // Financial lease loopt via onze leasepartner FinancialLease.nl (widget met
    // echte maandbedragen). stock_id = de dealer-feed van de zaak.
    'finance' => [
        'financiallease_stock_id' => env('BRAND_FL_STOCK_ID', '1262'),
    ],

    // Echte Google-reviews van het bedrijfsprofiel van de zaak (naam, score en
    // tekst van Google; alleen kennelijke typefouten en emoji opgeschoond, niets
    // verzonnen). Dit is dezelfde bron die hun eigen site via Trustindex toont —
    // hier in de eigen huisstijl. Bijwerken = deze lijst vervangen door de
    // nieuwste reviews van het Google-profiel.
    'testimonials' => [
        ['name' => 'Teus Dekker', 'rating' => 5, 'text' => 'Een paar weken geleden een andere auto gekocht bij Van Rijswijk. Keurig netjes geholpen. Er word alle tijd voor je genomen om alle opties die er zijn goed door te nemen. Uiterst vriendelijk personeel. En ook niet onbelangrijk, de prijzen zijn superscherp. Bij een volgende aanschaf zal ik als eerste naar Van Rijswijk gaan.'],
        ['name' => 'Enis Aliov', 'rating' => 5, 'text' => 'Vorige maand een Tesla Model 3 gekocht bij Autobedrijf Rijswijk. Alles is eerlijk en netjes nagekomen, precies zoals afgesproken. Inmiddels een heerlijke vakantie achter de rug met de Tesla, zonder ook maar één probleem. Top service en zeker een aanrader!'],
        ['name' => 'Martijn Jellema', 'rating' => 5, 'text' => 'Gewoon goed bedrijf, heb hier een super mooie T-Roc gekocht van 2022, super blij mee. Zou dit bedrijf zeker aanraden aan wie een leuke occasion zoekt, want ze hebben van alles staan.'],
        ['name' => 'Gemeente Den Haag', 'rating' => 5, 'text' => 'Onlangs een prachtige Mercedes-AMG G63 gekocht bij BS Autobedrijf in Rijswijk. Wat een geweldige auto! Ik ben ontzettend tevreden over de service en de manier waarop ik ben geholpen. Alles was netjes geregeld en het contact was professioneel en vriendelijk. Zeker een aanrader.'],
        ['name' => 'Seko 1907', 'rating' => 5, 'text' => 'Hele mooie GLC 400e gekocht. Vanaf \'t begin tot einde goed geholpen. Ceasar en Dennis hebben passie voor auto\'s, denken keurig netjes mee en zijn zeer vriendelijk! Ga zo door heren.'],
        ['name' => 'MBM Bouwservice B.V.', 'rating' => 5, 'text' => 'De heren hebben mij zo goed geholpen, vriendelijk en servicegericht. Ik was niet van plan om iets te kopen, meer oriënterend. 6 maanden later besloot ik toch een auto te kopen voor mijn dochter. Ik wist precies waar ik moest zijn. Dankjewel Dennis.'],
        ['name' => 'Joze Marbus', 'rating' => 4, 'text' => 'Zeer vriendelijke ontvangst, heeft alle tijd genomen voor de uitleg. Nette inruilprijs. Kortom een fijne deal en tevreden met de aankoop.'],
        ['name' => 'Murat Altuntas', 'rating' => 5, 'text' => 'Aardige heren! Dennis en Ceasar toppers.'],
    ],

    // Foutbewaking: wie krijgt een mail bij een fout in productie (App\Support\ErrorAlert).
    'alerts' => [
        'error_email' => env('ERROR_ALERT_EMAIL'),
    ],

    // Wettelijk verplicht op de website van een bedrijf (Handelsregisterwet):
    // KvK-nummer en btw-nummer, in de footer. Geverifieerd op 2026-09-23: KvK via
    // het BOVAG-ledenregister, btw via EU VIES (op naam + adres van de zaak).
    // (De oude site toont het btw-nummer ten onrechte als "KvK".)
    'company' => [
        'legal_name' => env('BRAND_LEGAL_NAME', 'BS Rijswijk Automotive B.V.'),
        'kvk'        => env('BRAND_KVK', '95760733'),
        'vat'        => env('BRAND_VAT', 'NL867282368B01'),
    ],

    // Bron voor `cars:sync`: de WordPress-site waar de voorraad nu op staat.
    // Vervangt déze site straks het domein, dan moet de WordPress-site op een
    // ander adres blijven draaien (bv. een subdomein) of de sync uit (leeg laten).
    'dealer_site_url' => env('DEALER_SITE_URL', 'https://autobedrijfrijswijk.nl'),
];
