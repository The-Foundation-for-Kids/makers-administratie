<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('clothing', function (Blueprint $table) {
            $table->id();
            $table->string('Status')->nullable();
            $table->string('Code')->nullable();
            $table->foreignId('agency_id')->constrained('agencies');
            $table->string('Leeftijd')->nullable();
            $table->string('Geslacht')->nullable();
            $table->string('Maat')->nullable();
            $table->string('Wens')->nullable();
            $table->string('Kleuren')->nullable();
            $table->date('DatumPlaatsing')->nullable();
            $table->string('Maakster')->nullable();
            $table->integer('maakster_id')->nullable();
            $table->date('DatumMaakster')->nullable();
            $table->date('DatumKlaar')->nullable();
            $table->date('DatumOntvangen')->nullable();
            $table->date('DatumContact1')->nullable();
            $table->string('WieContact1')->nullable();
            $table->date('DatumContact2')->nullable();
            $table->string('WieContact2')->nullable();
            $table->date('Datum_Ingevoerd')->nullable();
            $table->date('Datum_Aangeboden')->nullable();
            $table->date('Datum_Opgepakt')->nullable();
            $table->date('Datum_Bevestigd')->nullable();
            $table->date('Datum_BevestigdDeadline')->nullable();
            $table->date('Datum_Klaar')->nullable();
            $table->date('Datum_Ontvangen')->nullable();
            $table->date('Datum_Afgehandeld')->nullable();
            $table->integer('Gebruiker_Ingevoerd')->nullable();
            $table->integer('Gebruiker_Aangeboden')->nullable();
            $table->integer('Gebruiker_Opgepakt')->nullable();
            $table->integer('Gebruiker_Bevestigd')->nullable();
            $table->integer('Gebruiker_BevestigdDeadline')->nullable();
            $table->integer('Gebruiker_Klaar')->nullable();
            $table->integer('Gebruiker_Ontvangen')->nullable();
            $table->integer('Gebruiker_Afgehandeld')->nullable();
            $table->date('Kleding_Deadline')->nullable();
            $table->text('Notities')->nullable();
            $table->text('Question')->nullable();
            $table->boolean('Question_Filled')->nullable();
            $table->string('TrackAndTraceCode')->nullable();
            $table->string('Kenmerk_Instantie')->nullable();
            $table->string('Kenmerk_Geprint')->nullable();
            $table->datetime('Datum_Verlopen_Voortoekenning')->nullable();
            $table->integer('Gebruiker_Voortoekenning')->nullable();
            $table->text('Activity')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('clothing');
    }
};
