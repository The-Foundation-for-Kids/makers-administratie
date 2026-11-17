<?php

namespace Tests\Feature;

use App\Livewire\SelectYear;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for the SelectYear Livewire component: a super_admin can switch the
 * working year, which is persisted on the user and used by the year filters.
 */
class SelectYearTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Choosing a year in the SelectYear component must store that year in the logged-in
     * user's selected_year column so all year-filtered views use it.
     */
    public function test_selecting_a_year_persists_selected_year_on_user(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['active' => true]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(SelectYear::class)->set('setSelectedYear', '2024');

        $this->assertEquals(2024, $user->fresh()->selected_year);
    }

    /**
     * Clearing the selection (empty string) must reset selected_year to null, which
     * makes the views fall back to the current year.
     */
    public function test_clearing_the_year_resets_selected_year_to_null(): void
    {
        Role::create(['name' => 'super_admin', 'guard_name' => 'web']);
        $user = User::factory()->create(['active' => true, 'selected_year' => 2024]);
        $user->assignRole('super_admin');
        $this->actingAs($user);

        Livewire::test(SelectYear::class)->set('setSelectedYear', '');

        $this->assertNull($user->fresh()->selected_year);
    }
}
