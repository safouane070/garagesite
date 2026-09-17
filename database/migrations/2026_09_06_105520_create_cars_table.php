<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cars', function (Blueprint $table) {
            $table->id();

            // SEO-vriendelijke, unieke URL-sleutel, bv. "bmw-320d-m-sport-2021"
            $table->string('slug')->unique();

            // Kerninfo (belangrijk in de visuele hiërarchie)
            $table->string('brand');                 // merk
            $table->string('model');                 // model
            $table->string('variant')->nullable();   // uitvoering/trim, bv. "M Sport"
            $table->unsignedSmallInteger('year');    // bouwjaar
            $table->decimal('price', 12, 2);         // prijs in euro (2 decimalen)
            $table->unsignedInteger('mileage');      // kilometerstand

            // Categorische velden waarop we filteren
            $table->string('fuel_type');             // brandstoftype
            $table->string('transmission');          // transmissie
            $table->string('color');                 // kleur
            $table->string('body_type')->nullable(); // carrosserie (sedan, SUV, ...)

            // Vrije tekst
            $table->text('description')->nullable();

            // Flexibele extra specs als JSON: vermogen, cilinderinhoud, deuren, zitplaatsen, ...
            $table->json('specs')->nullable();

            // Verkoopstatus + uitlichting op de homepage
            $table->string('status')->default('available'); // available | reserved | sold
            $table->boolean('is_featured')->default(false);

            $table->timestamps();

            // Indexen versnellen filteren/sorteren op deze kolommen
            $table->index('brand');
            $table->index('fuel_type');
            $table->index('status');
            $table->index('year');
            $table->index('price');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cars');
    }
};
