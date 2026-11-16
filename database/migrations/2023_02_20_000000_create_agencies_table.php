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
            $table->boolean('Partner_2022')->nullable();
            $table->text('Extra_info')->nullable();
            $table->date('Bezoek')->nullable();
            $table->foreignId('verzamel_user_id')->nullable()->constrained('users');
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
        Schema::dropIfExists('agencies');
    }
};
