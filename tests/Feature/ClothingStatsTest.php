<?php

namespace Tests\Feature;

use App\Filament\Resources\Clothing\Widgets\StatsOverview;
use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Feature tests for the StatsOverview widget on the clothing list: the statistics are
 * counted in the database and must reflect the actual request states.
 */
class ClothingStatsTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The widget must render the three stat labels that beheerders use to see the
     * workload at a glance on the clothing list page.
     */
    public function test_widget_renders_the_three_stat_labels(): void
    {
        $this->actingAs(User::factory()->create(['active' => true]));

        Livewire::test(StatsOverview::class)
            ->assertSee('Openstaande aanvragen')
            ->assertSee('Naderende deadline')
            ->assertSee('Deadline verlopen');
    }

    /**
     * The stat values must be counted in the database: 'Aangeboden' requests count as
     * open, 'Opgepakt' requests with a deadline within 7 days count as approaching and
     * 'Opgepakt' requests with a past deadline count as expired.
     */
    public function test_widget_counts_match_database_queries(): void
    {
        $agency = Agency::factory()->create();
        Clothing::factory(2)->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'Kleding_Deadline' => Carbon::now()->addDays(3)->toDateString()]);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'Kleding_Deadline' => Carbon::now()->subDay()->toDateString()]);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'Kleding_Deadline' => Carbon::now()->addDays(20)->toDateString()]);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld', 'Kleding_Deadline' => Carbon::now()->subDay()->toDateString()]);

        $widget = new StatsOverview;
        $stats = (new \ReflectionMethod($widget, 'getCards'))->invoke($widget);
        $values = array_map(fn ($stat) => $stat->getValue(), $stats);

        $this->assertEquals([2, 1, 1], $values);
    }
}
