<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Koppeling met de voorraad op de dealersite (cars:sync). Een auto mét
     * dealer_slug wordt door de sync beheerd; zonder (handmatig in het beheer
     * aangemaakt) laat de sync 'm met rust.
     */
    public function up(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->string('dealer_slug')->nullable()->unique()->after('slug');
            $table->timestamp('dealer_modified_at')->nullable()->after('dealer_slug');
        });
    }

    public function down(): void
    {
        Schema::table('cars', function (Blueprint $table) {
            $table->dropUnique(['dealer_slug']);
            $table->dropColumn(['dealer_slug', 'dealer_modified_at']);
        });
    }
};
