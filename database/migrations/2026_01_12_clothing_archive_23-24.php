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
        DB::statement("update clothing set  archived_at=now() where (substr(code,REGEXP_INSTR(code,'\\\\d\\\\d'),2)= '23' or substr(code,REGEXP_INSTR(code,'\\\\d\\\\d'),2)= '24')");
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */

    public function down() {}
};
