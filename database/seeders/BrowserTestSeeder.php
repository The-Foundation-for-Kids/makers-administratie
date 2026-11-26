<?php

namespace Database\Seeders;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Testgegevens voor de browsertests (tests/browser). Alleen bedoeld voor een
 * lege testdatabase, nooit voor productie.
 *
 * Alle testgebruikers hebben het wachtwoord uit BROWSER_TEST_PASSWORD
 * (standaard "Test-wachtwoord-1").
 */
class BrowserTestSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->isProduction()) {
            throw new \RuntimeException('BrowserTestSeeder mag niet op productie draaien.');
        }

        $this->call(ShieldSeeder::class);

        $password = Hash::make(env('BROWSER_TEST_PASSWORD', 'Test-wachtwoord-1'));
        $user = function (string $name, string $email, ?string $role) use ($password): User {
            $user = User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'active' => true,
            ]);
            if ($role) {
                $user->assignRole($role);
            }

            return $user;
        };

        $admin = $user('Test Admin', 'admin@example.test', 'super_admin');
        $user('Test Beheerder', 'beheerder@example.test', 'Beheerder');
        $maakster1 = $user('Test Maakster Een', 'maakster1@example.test', 'Maakster');
        $user('Test Maakster Twee', 'maakster2@example.test', 'Maakster');
        $inzamelpunt = $user('Test Inzamelpunt', 'inzamelpunt@example.test', 'Verzamelpunt');
        $user('Test Zonder Rol', 'geenrol@example.test', null);

        $metInzamelpunt = Agency::create([
            'Code' => 'TA',
            'Status' => 'Actief',
            'Aanvrager' => 'Test Instantie Alfa',
            'Postcode' => '1234 AB',
            'Vestigingsplaats' => 'Testdorp',
            'Bezorg_Naam' => 'Test Inzamelpunt',
            'Bezorg_Adres' => 'Teststraat 1',
            'Bezorg_Postcode' => '1234 AB',
            'Bezorg_Plaats' => 'Testdorp',
            'verzamel_user_id' => $inzamelpunt->id,
        ]);
        $zonderInzamelpunt = Agency::create([
            'Code' => 'TB',
            'Status' => 'Actief',
            'Aanvrager' => 'Test Instantie Beta',
            'Postcode' => '5678 CD',
            'Vestigingsplaats' => 'Teststad',
        ]);

        // Het aanmaken van een aanvraag legt vast wie hem invoerde.
        Auth::setUser($admin);

        $aanvraag = function (Agency $agency, string $status, array $extra = []) {
            $clothing = Clothing::create([
                'agency_id' => $agency->id,
                'Geslacht' => 'Meisje',
                'Leeftijd' => '5 jaar',
                'Maat' => '110',
                'Wens' => 'Pyjama',
                'Kleuren' => 'roze',
                'houdtVan' => 'paarden',
                'Status' => $status,
            ] + $extra);

            return $clothing;
        };

        $opgepakt = fn () => [
            'maakster_id' => $maakster1->id,
            'Maakster' => $maakster1->name,
            'DatumMaakster' => now()->subDays(3),
            'Kleding_Deadline' => now()->addDays(25),
        ];

        foreach (range(1, 4) as $i) {
            $aanvraag($metInzamelpunt, 'Aangeboden');
        }
        $aanvraag($zonderInzamelpunt, 'Aangeboden');
        $aanvraag($zonderInzamelpunt, 'Ingevoerd');
        $aanvraag($zonderInzamelpunt, 'Ingevoerd');
        $aanvraag($metInzamelpunt, 'Opgepakt', $opgepakt());
        $aanvraag($metInzamelpunt, 'Opgepakt', $opgepakt());
        $aanvraag($metInzamelpunt, 'Opgepakt', $opgepakt());
        $aanvraag($metInzamelpunt, 'Klaar', $opgepakt());
        $aanvraag($metInzamelpunt, 'Verzonden', $opgepakt());
        $aanvraag($metInzamelpunt, 'Verzonden', $opgepakt());
        $aanvraag($metInzamelpunt, 'Ontvangen', $opgepakt());
        $aanvraag($metInzamelpunt, 'Ontvangen', $opgepakt());
        $aanvraag($metInzamelpunt, 'Afgehandeld', $opgepakt());
        $aanvraag($metInzamelpunt, 'Afgehandeld', $opgepakt());

        Auth::forgetUser();
    }
}
