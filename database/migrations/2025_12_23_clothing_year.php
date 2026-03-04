<?php

use Illuminate\Support\Facades\DB;
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
            $table->string('year')->nullable();
        });
        DB::statement("update clothing set year=concat('20',substr(code,REGEXP_INSTR(code,'\\\\d\\\\d'),2))");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
};
