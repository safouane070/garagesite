<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Herkomst van een aanvraag: via welk kanaal de bezoeker binnenkwam, op welke
     * pagina, en waar het formulier werd ingevuld. Geen tracking over sessies heen.
     */
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->string('source', 60)->nullable()->after('preferred_date');
            $table->string('landing_page')->nullable()->after('source');
            $table->string('form_page')->nullable()->after('landing_page');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['source', 'landing_page', 'form_page']);
        });
    }
};
