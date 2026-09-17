<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('car_images', function (Blueprint $table) {
            $table->id();

            // Koppeling naar de auto; foto's gaan mee als de auto verwijderd wordt
            $table->foreignId('car_id')->constrained()->cascadeOnDelete();

            $table->string('path');                       // opslagpad in storage/app/public
            $table->boolean('is_primary')->default(false); // omslagfoto (cover)
            $table->unsignedInteger('sort_order')->default(0); // volgorde in de carousel

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('car_images');
    }
};
