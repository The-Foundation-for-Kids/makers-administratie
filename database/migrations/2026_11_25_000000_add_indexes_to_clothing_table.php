<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    public function up(): void
    {
        Schema::table('clothing', function (Blueprint $table) {
            $table->index(['Status', 'archived_at']);
            $table->index(['maakster_id', 'Status']);
            $table->index(['agency_id', 'Status', 'archived_at']);
        });
    }

    public function down(): void
    {
        Schema::table('clothing', function (Blueprint $table) {
            // De foreign key op agency_id heeft altijd een index nodig; MySQL kan
            // de oorspronkelijke vervangen hebben door de samengestelde index.
            if (! Schema::hasIndex('clothing', ['agency_id'])) {
                $table->index('agency_id', 'clothing_agency_id_foreign');
            }

            $table->dropIndex(['Status', 'archived_at']);
            $table->dropIndex(['maakster_id', 'Status']);
            $table->dropIndex(['agency_id', 'Status', 'archived_at']);
        });
    }
};
