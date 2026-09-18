<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Voorkeursdatum voor een proefrit-/bezichtigingsaanvraag. Optioneel: de
     * meeste aanvragen (algemene vraag, inruil, financiering) hebben er geen.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->date('preferred_date')->nullable()->after('message');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn('preferred_date');
        });
    }
};
