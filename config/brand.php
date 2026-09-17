<?php

/*
|--------------------------------------------------------------------------
| Merk- en contactgegevens
|--------------------------------------------------------------------------
|
| Eén bron van waarheid voor alles wat over de showroom zelf gaat: contact,
| openingsuren, het huisstijl-accent en de vertrouwenscijfers. Views en de
| seeder lezen hier uit, zodat de eigenaar dit op één plek aanpast in plaats
| van verspreid door de blade-bestanden.
|
| De contactwaarden hieronder zijn realistische demo-gegevens — vervang ze
| door de echte gegevens van de zaak vóór livegang.
|
*/

return [
    // Het herkenbare messing/goud-accent. Zelfde waarde als brass.500 in
    // tailwind.config.js en de --brass CSS-variabele; hier voor server-side
    // gegenereerde SVG-placeholders.
    'accent' => env('BRAND_ACCENT', '#D90429'),

    'contact' => [
        // Weergavevorm en tel:-vorm (zonder spaties) apart, zodat het nummer
        // netjes toont én klikbaar blijft.
        'phone'      => env('BRAND_PHONE', '085 130 40 58'),
        'phone_href' => env('BRAND_PHONE_HREF', '+31851304058'),
        'email'      => env('BRAND_EMAIL', 'info@autobedrijfrijswijk.nl'),
        'address'    => env('BRAND_ADDRESS', 'Poldermeesterstraat 16, 2288 GV Rijswijk'),
        'hours'      => env('BRAND_HOURS', 'Ma–Za · 09:00–18:00'),
        'city'       => env('BRAND_CITY', 'Rijswijk'),
    ],

    // Cijfers die als belofte op de site staan. Config i.p.v. losse tekst,
    // zodat ze kloppen én centraal aanpasbaar zijn.
    'trust' => [
        'inspection_points' => (int) env('BRAND_INSPECTION_POINTS', 120),
        'warranty_months'   => (int) env('BRAND_WARRANTY_MONTHS', 12),
    ],
];
