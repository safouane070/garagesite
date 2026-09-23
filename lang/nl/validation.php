<?php

// Nederlandse validatiemeldingen voor de regels die deze app gebruikt
// (aanvraag-, auto- en login/profielformulieren). Ontbrekende regels vallen
// terug op de Engelse standaardtekst van Laravel.
return [
    'accepted' => ':Attribute moet geaccepteerd worden.',
    'after_or_equal' => ':Attribute moet vandaag of later zijn.',
    'array' => ':Attribute moet een lijst zijn.',
    'between' => [
        'numeric' => ':Attribute moet tussen :min en :max liggen.',
        'string' => ':Attribute moet tussen :min en :max tekens bevatten.',
    ],
    'boolean' => ':Attribute moet waar of onwaar zijn.',
    'confirmed' => 'De bevestiging van :attribute komt niet overeen.',
    'current_password' => 'Het wachtwoord is onjuist.',
    'date' => ':Attribute is geen geldige datum.',
    'email' => ':Attribute moet een geldig e-mailadres zijn.',
    'enum' => 'De gekozen :attribute is ongeldig.',
    'exists' => 'De gekozen :attribute bestaat niet.',
    'image' => ':Attribute moet een afbeelding zijn.',
    'in' => 'De gekozen :attribute is ongeldig.',
    'integer' => ':Attribute moet een geheel getal zijn.',
    'lowercase' => ':Attribute mag alleen kleine letters bevatten.',
    'max' => [
        'array' => ':Attribute mag niet meer dan :max items bevatten.',
        'file' => ':Attribute mag niet groter zijn dan :max kilobytes.',
        'numeric' => ':Attribute mag niet groter zijn dan :max.',
        'string' => ':Attribute mag niet meer dan :max tekens bevatten.',
    ],
    'mimes' => ':Attribute moet een bestand zijn van het type: :values.',
    'min' => [
        'numeric' => ':Attribute moet minimaal :min zijn.',
        'string' => ':Attribute moet minimaal :min tekens bevatten.',
    ],
    'numeric' => ':Attribute moet een getal zijn.',
    'prohibited' => ':Attribute is niet toegestaan.',
    'required' => ':Attribute is verplicht.',
    'string' => ':Attribute moet tekst zijn.',
    'unique' => ':Attribute is al in gebruik.',
    'uploaded' => ':Attribute kon niet worden geüpload.',
    'password' => [
        'letters' => ':Attribute moet minimaal één letter bevatten.',
        'mixed' => ':Attribute moet minimaal één hoofdletter en één kleine letter bevatten.',
        'numbers' => ':Attribute moet minimaal één cijfer bevatten.',
        'symbols' => ':Attribute moet minimaal één symbool bevatten.',
        'uncompromised' => 'Dit :attribute komt voor in een datalek. Kies een ander :attribute.',
    ],

    'attributes' => [
        'email' => 'e-mailadres',
        'password' => 'wachtwoord',
        'current_password' => 'huidig wachtwoord',
        'name' => 'naam',
    ],
];
