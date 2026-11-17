<?php

namespace Database\Factories;

use App\Models\Agency;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Clothing>
 */
class ClothingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'Status' => 'Ingevoerd',
            'year' => date('Y'),
            'agency_id' => Agency::factory(),
            'Leeftijd' => fake()->numberBetween(1, 18),
            'Geslacht' => fake()->randomElement(['Jongen', 'Meisje']),
            'Maat' => fake()->randomElement(['50', '56', '62', '68', '74', '80', '86', '92', '98', '104', '110', '116', '122', '128', '134', '140', '146', '152', '158']),
            'Wens' => fake()->optional()->sentence(),
            'Kleuren' => fake()->optional()->colorName(),
            'Maakster' => null,
            'maakster_id' => null,
            'DatumMaakster' => null,
            'DatumKlaar' => null,
            'Gebruiker_Ingevoerd' => null,
            'Kleding_Deadline' => null,
            'Notities' => null,
            'TrackAndTraceCode' => null,
            'Kenmerk_Instantie' => null,
            'Activity' => '',
            'Gebruiker_Voortoekenning' => null,
            'Datum_Verlopen_Voortoekenning' => null,
        ];
    }

    /**
     * Create a clothing item with status 'Aangeboden'
     */
    public function aangeboden(): static
    {
        return $this->state(fn (array $attributes) => [
            'Status' => 'Aangeboden',
        ]);
    }

    /**
     * Create a clothing item with status 'Opgepakt'
     */
    public function opgepakt(): static
    {
        return $this->state(fn (array $attributes) => [
            'Status' => 'Opgepakt',
            'DatumMaakster' => Carbon::now()->format('Y-m-d'),
            'maakster_id' => User::factory(),
            'Maakster' => fake()->name(),
            'Kleding_Deadline' => Carbon::now()->addDays(29)->format('Y-m-d'),
        ]);
    }

    /**
     * Create a clothing item with status 'Klaar'
     */
    public function klaar(): static
    {
        return $this->state(fn (array $attributes) => [
            'Status' => 'Klaar',
            'DatumKlaar' => Carbon::now()->format('Y-m-d'),
            'DatumMaakster' => Carbon::now()->subDays(7)->format('Y-m-d'),
        ]);
    }

    /**
     * Create a clothing item with status 'Afgehandeld'
     */
    public function afgehandeld(): static
    {
        return $this->state(fn (array $attributes) => [
            'Status' => 'Afgehandeld',
        ]);
    }
}
