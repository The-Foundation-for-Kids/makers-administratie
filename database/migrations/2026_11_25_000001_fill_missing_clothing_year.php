<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration {

    /**
     * Vul het ontbrekende jaar aan op basis van de code (QDB260001 => 2026),
     * en anders op basis van de aanmaakdatum.
     */
    public function up(): void
    {
        DB::table('clothing')
            ->where(fn ($query) => $query->whereNull('year')->orWhere('year', ''))
            ->select('id', 'Code', 'created_at')
            ->get()
            ->each(function ($row) {
                if (preg_match('/(\d{2})\d{4}$/', (string) $row->Code, $match)) {
                    $year = '20' . $match[1];
                } elseif ($row->created_at) {
                    $year = substr($row->created_at, 0, 4);
                } else {
                    return;
                }

                DB::table('clothing')->where('id', $row->id)->update(['year' => $year]);
            });
    }

    public function down(): void {}
};
