<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * De jaarkeuze in het gebruikersmenu is verwijderd; het opgeslagen jaar werd
 * nergens gebruikt (het Rapport heeft een eigen jaarfilter).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('users', 'selected_year')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('selected_year');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'selected_year')) {
            Schema::table('users', function (Blueprint $table) {
                $table->integer('selected_year')->nullable();
            });
        }
    }
};
