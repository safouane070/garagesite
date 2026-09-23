<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Aantal weergaven van de detailpagina (zonder cookies of IP-adressen). */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->unsignedInteger('views')->default(0)->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropColumn('views');
        });
    }
};
