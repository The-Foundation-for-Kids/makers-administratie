<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Voortoekenning per gebruiker wordt niet meer gebruikt.
     * Datum_Verlopen_Voortoekenning ("Zichtbaar per") blijft bestaan.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('voortoekenning');
        });

        Schema::table('clothing', function (Blueprint $table) {
            $table->dropColumn('Gebruiker_Voortoekenning');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('voortoekenning')->nullable();
        });

        Schema::table('clothing', function (Blueprint $table) {
            $table->integer('Gebruiker_Voortoekenning')->nullable();
        });
    }
};
