<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Deze kolommen bestaan in de bestaande databases, maar werden door geen
 * migratie aangemaakt. Alleen toevoegen als ze ontbreken (lege database);
 * op bestaande databases doet deze migratie niets.
 */
return new class extends Migration {

    public function up(): void
    {
        Schema::table('clothing', function (Blueprint $table) {
            if (! Schema::hasColumn('clothing', 'houdtVan')) {
                $table->string('houdtVan')->nullable();
            }
            if (! Schema::hasColumn('clothing', 'priority')) {
                $table->boolean('priority')->nullable();
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'notes')) {
                $table->text('notes')->nullable();
            }
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void {}
};
