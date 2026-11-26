<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Teruggezet op 2026-11-26: deze migratie stond al als uitgevoerd in de
 * database, maar het bestand ontbrak. Nagebouwd naar de huidige tabel.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('user_agencies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('agency_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_agencies');
    }
};
