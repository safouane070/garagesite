<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Miniatuur (voor kaartjes) en de echte afmetingen van de foto: nodig voor
     * srcset en voor width/height-attributen (geen verspringende layout).
     */
    public function up(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->string('thumb_path')->nullable()->after('path');
            $table->unsignedSmallInteger('width')->nullable()->after('thumb_path');
            $table->unsignedSmallInteger('height')->nullable()->after('width');
        });
    }

    public function down(): void
    {
        Schema::table('car_images', function (Blueprint $table) {
            $table->dropColumn(['thumb_path', 'width', 'height']);
        });
    }
};
