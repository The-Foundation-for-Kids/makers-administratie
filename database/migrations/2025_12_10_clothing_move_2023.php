<?php
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Run the migrations.
     *
     * @return void
     */

    public function up()
    {
        if (! Schema::hasTable('clothing_2023')) {
            return;
        }
        if (! Schema::hasTable('clothing_2024')) {
            return;
        }

        $sourceQuery = DB::table('clothing_2023')
            ->select('Activity', 'agency_id', 'Code', 'created_at', 'Datum_Verlopen_Voortoekenning', 'DatumMaakster', 'deleted_at', 'Gebruiker_Ingevoerd', 'Gebruiker_Voortoekenning', 'Geslacht', 'id', 'Kenmerk_Geprint', 'Kenmerk_Instantie', 'Kleding_Deadline', 'Kleuren', 'Leeftijd', 'Maakster', 'maakster_id', 'Maat', 'Notities', 'Question_Filled', 'Status', 'TrackAndTraceCode', 'updated_at', 'Wens');

        // Execute the bulk insert
        DB::table('clothing')->insertUsing(
            ['Activity', 'agency_id', 'Code', 'created_at', 'Datum_Verlopen_Voortoekenning', 'DatumMaakster', 'deleted_at', 'Gebruiker_Ingevoerd', 'Gebruiker_Voortoekenning', 'Geslacht', 'id', 'Kenmerk_Geprint', 'Kenmerk_Instantie', 'Kleding_Deadline', 'Kleuren', 'Leeftijd', 'Maakster', 'maakster_id', 'Maat', 'Notities', 'Question_Filled', 'Status', 'TrackAndTraceCode', 'updated_at', 'Wens'],
            $sourceQuery
        );
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
