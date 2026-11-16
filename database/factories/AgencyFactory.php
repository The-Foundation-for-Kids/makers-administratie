<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Agency>
 */
class AgencyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Code' => fake()->unique()->lexify('???'),
            'Status' => 'Actief',
            'Aanvrager' => fake()->company(),
            'Adres' => fake()->streetAddress(),
            'Postcode' => fake()->postcode(),
            'Vestigingsplaats' => fake()->city(),
            'Naam_Contactpersoon' => fake()->name(),
            'Emailadres_Contactpersoon' => fake()->unique()->safeEmail(),
            'Bezorg_Naam' => fake()->optional()->company(),
            'Bezorg_Email' => fake()->optional()->safeEmail(),
            'Bezorg_Adres' => fake()->optional()->streetAddress(),
            'Bezorg_Postcode' => fake()->optional()->postcode(),
            'Bezorg_Plaats' => fake()->optional()->city(),
            'Overige_Mail' => fake()->optional()->safeEmail(),
            'KVK-nummer' => fake()->optional()->numerify('#########'),
            'Partner_2022' => fake()->boolean(),
            'Extra_info' => fake()->optional()->sentence(),
            'Bezoek' => fake()->optional()->date(),
            'verzamel_user_id' => null,
        ];
    }
}
