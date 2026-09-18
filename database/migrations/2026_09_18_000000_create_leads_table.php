<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            // Aanvraag hoort meestal bij één auto, maar hoeft niet (algemene vraag).
            // Blijft de auto later verwijderd, dan blijft de lead bestaan.
            $table->foreignId('car_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type')->default('vraag'); // vraag | bezichtiging | inruil | financiering
            $table->string('name');
            $table->string('email');
            $table->string('phone')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('handled_at')->nullable(); // afgehandeld door de zaak
            $table->timestamps();

            $table->index(['handled_at', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
