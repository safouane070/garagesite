<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * vat_deductible: BTW-auto (btw aftrekbaar). De voorraadfeed van de dealer heeft
     *   dit niet als veld; de beheerder zet het, de sync vult het in als "BTW" in de titel staat.
     * sold_at: wanneer de auto verkocht werd, voor "X auto's verkocht dit jaar".
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->boolean('vat_deductible')->default(false)->after('is_featured');
            $table->timestamp('sold_at')->nullable()->after('status');
        });

        DB::table('cars')->where('variant', 'like', '%BTW%')->update(['vat_deductible' => true]);
        // Beste benadering voor al verkochte auto's: de laatste wijziging was het verkopen.
        DB::table('cars')->where('status', 'sold')->update(['sold_at' => DB::raw('updated_at')]);
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn(['vat_deductible', 'sold_at']);
        });
    }
};
