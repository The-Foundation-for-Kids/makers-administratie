<?php
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{

    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
		Schema::table('clothing', function (Blueprint $table) {
			$table->dropColumn('DatumPlaatsing');
			$table->dropColumn('DatumOntvangen');
            $table->dropColumn('DatumContact1');
            $table->dropColumn('WieContact1');
            $table->dropColumn('DatumContact2');
            $table->dropColumn('WieContact2');
            $table->dropColumn('Datum_Ingevoerd');
            $table->dropColumn('Datum_Aangeboden');
            $table->dropColumn('Datum_Opgepakt');
            $table->dropColumn('Datum_Bevestigd');
            $table->dropColumn('Datum_BevestigdDeadline');
            $table->dropColumn('Datum_Klaar');
            $table->dropColumn('Datum_Ontvangen');
            $table->dropColumn('Datum_Afgehandeld');
            $table->dropColumn('Gebruiker_Aangeboden');
            $table->dropColumn('Gebruiker_Opgepakt');
            $table->dropColumn('Gebruiker_Bevestigd');
            $table->dropColumn('Gebruiker_BevestigdDeadline');
            $table->dropColumn('Gebruiker_Klaar');
            $table->dropColumn('Gebruiker_Ontvangen');
            $table->dropColumn('Gebruiker_Afgehandeld');
            $table->dropColumn('Question');

        });

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down()
    {

    }

};