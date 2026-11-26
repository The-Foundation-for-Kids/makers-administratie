<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Teruggezet op 2026-11-26: deze migratie stond al als uitgevoerd in de
 * database, maar het bestand ontbrak. Nagebouwd naar de huidige tabel, zodat
 * een lege database (bijvoorbeeld voor de browsertests) kan worden opgebouwd.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::create('agencies', function (Blueprint $table) {
            $table->id();
            $table->string('Code')->nullable();
            $table->string('Status')->nullable();
            $table->string('Aanvrager')->nullable();
            $table->string('Adres')->nullable();
            $table->string('Postcode')->nullable();
            $table->string('Vestigingsplaats')->nullable();
            $table->string('Naam_Contactpersoon')->nullable();
            $table->string('Emailadres_Contactpersoon')->nullable();
            $table->string('Bezorg_Naam')->nullable();
            $table->string('Bezorg_Email')->nullable();
            $table->string('Bezorg_Adres')->nullable();
            $table->string('Bezorg_Postcode')->nullable();
            $table->string('Bezorg_Plaats')->nullable();
            $table->string('Overige_Mail')->nullable();
            $table->string('KVK-nummer')->nullable();
            $table->string('Partner_2022')->nullable();
            $table->text('Extra_info')->nullable();
            $table->string('Bezoek')->nullable();
            $table->text('Afspraken')->nullable();
            $table->string('verzamel_user_id')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('agencies');
    }
};
