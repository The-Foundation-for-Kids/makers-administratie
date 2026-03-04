<?php
namespace Database\Seeders\Models;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        /**
         * Command :
         * artisan seed:generate --model-mode --models=Agency --ids=1
         *
         */


        $newData0 = \App\Models\Agency::create([
            'id' => 1,
            'Code' => 'BP',
            'Status' => 'Actief',
            'Aanvrager' => 'Babypakketten',
            'Adres' => 'dummy',
            'Postcode' => 'dummy',
            'Vestigingsplaats' => 'dummy',
            'Naam_Contactpersoon' => 'dummy',
            'Emailadres_Contactpersoon' => 'dummy',

        ]);
    }
}
