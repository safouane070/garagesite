<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Uitgebreide uitrusting/opties per auto als JSON-array van strings
     * (bv. "Panoramadak", "Adaptieve cruise control"). Los van `specs`, dat
     * key/waarde-kerngegevens bevat; dit is een platte checklist.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->json('options')->nullable()->after('specs');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('options');
        });
    }
};
