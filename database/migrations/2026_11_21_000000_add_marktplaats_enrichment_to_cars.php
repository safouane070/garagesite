<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koppeling met de Marktplaats-advertentie van dezelfde auto (de dealer adverteert
     * daar ook), waaruit `cars:enrich` de specs haalt die de eigen dealersite niet heeft.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('marktplaats_id', 20)->nullable()->after('dealer_modified_at');
            $table->timestamp('enriched_at')->nullable()->after('marktplaats_id');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['marktplaats_id', 'enriched_at']);
        });
    }
};
