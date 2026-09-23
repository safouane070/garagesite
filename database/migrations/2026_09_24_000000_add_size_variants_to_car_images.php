<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Extra maten naast de miniatuur (640) en het origineel (2000): 240 px voor
     * fotostrookjes en 1024 px voor telefoons, zodat mobiel niet de 2000 px-foto laadt.
     */
    public function up(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->string('xs_path')->nullable()->after('thumb_path');
            $table->string('md_path')->nullable()->after('xs_path');
        });
    }

    public function down(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->dropColumn(['xs_path', 'md_path']);
        });
    }
};
