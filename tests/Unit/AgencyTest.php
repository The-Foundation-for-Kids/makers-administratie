<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Agency model: relationships, status counts and month counts.
 */
class AgencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The fillable list must expose the agency fields used by the resource forms,
     * including the delivery address fields and the collector (verzamel_user_id).
     */
    public function test_fillable_attributes(): void
    {
        $agency = new Agency;

        $this->assertContains('Code', $agency->getFillable());
        $this->assertContains('Status', $agency->getFillable());
        $this->assertContains('Aanvrager', $agency->getFillable());
        $this->assertContains('Adres', $agency->getFillable());
        $this->assertContains('Postcode', $agency->getFillable());
        $this->assertContains('Vestigingsplaats', $agency->getFillable());
        $this->assertContains('Bezorg_Adres', $agency->getFillable());
        $this->assertContains('Bezorg_Postcode', $agency->getFillable());
        $this->assertContains('Bezorg_Plaats', $agency->getFillable());
    }

    /**
     * The model must stay mapped to the 'agencies' table.
     */
    public function test_table_name(): void
    {
        $agency = new Agency;
        $this->assertEquals('agencies', $agency->getTable());
    }

    /**
     * A new agency starts without clothing, and creating clothing through the relation
     * must make it show up in the agency's clothing collection.
     */
    public function test_clothing_relationship(): void
    {
        $agency = Agency::factory()->create();

        $this->assertEmpty($agency->clothing);

        $clothing = $agency->clothing()->create([
            'Status' => 'Ingevoerd',
            'year' => date('Y'),
            'Wens' => 'Test kleding',
        ]);

        $this->assertCount(1, $agency->fresh()->clothing);
        $this->assertEquals($clothing->id, $agency->fresh()->clothing->first()->id);
    }

    /**
     * The month relations (monthjan, monthfeb, ...) must count the agency's clothing
     * picked up in that calendar month, based on DatumMaakster.
     */
    public function test_month_scope_methods(): void
    {
        $agency = Agency::factory()->create();

        // Create clothing for different months
        $agency->clothing()->create([
            'Status' => 'Opgepakt',
            'year' => date('Y'),
            'DatumMaakster' => now()->setMonth(1)->setDay(15)->format('Y-m-d'),
        ]);

        $agency->clothing()->create([
            'Status' => 'Opgepakt',
            'year' => date('Y'),
            'DatumMaakster' => now()->setMonth(2)->setDay(15)->format('Y-m-d'),
        ]);

        $this->assertCount(1, $agency->monthjan);
        $this->assertCount(1, $agency->monthfeb);
    }

    /**
     * Month relations must use withArchived(), so requests archived into an old year
     * still count in the month statistics of the agency report.
     */
    public function test_month_relations_include_archived_records(): void
    {
        $agency = Agency::factory()->create();

        $jan = $agency->clothing()->create([
            'Status' => 'Opgepakt',
            'year' => '2024',
            'DatumMaakster' => '2024-01-15',
        ]);
        $feb = $agency->clothing()->create([
            'Status' => 'Opgepakt',
            'year' => '2024',
            'DatumMaakster' => '2024-02-15',
        ]);

        $jan->archive();

        // The archived record is hidden from normal queries but still counted per month.
        $remaining = $agency->clothing()->get();
        $this->assertCount(1, $remaining);
        $this->assertEquals($feb->id, $remaining->first()->id);
        $this->assertCount(1, $agency->monthjan);
        $this->assertCount(1, $agency->monthfeb);
    }

    /**
     * The per-status relations (ingevoerd, aangeboden, opgepakt, ...) must each return
     * only the agency's clothing with exactly that status.
     */
    public function test_status_scope_methods(): void
    {
        $agency = Agency::factory()->create();

        $agency->clothing()->create(['Status' => 'Ingevoerd', 'year' => date('Y')]);
        $agency->clothing()->create(['Status' => 'Aangeboden', 'year' => date('Y')]);
        $agency->clothing()->create(['Status' => 'Opgepakt', 'year' => date('Y')]);

        $this->assertCount(1, $agency->ingevoerd);
        $this->assertCount(1, $agency->aangeboden);
        $this->assertCount(1, $agency->opgepakt);
    }

    /**
     * The open relation must contain every request that is not yet 'Afgehandeld',
     * which is what the agency table uses to show outstanding work.
     */
    public function test_open_scope(): void
    {
        $agency = Agency::factory()->create();

        $agency->clothing()->create(['Status' => 'Ingevoerd', 'year' => date('Y')]);
        $agency->clothing()->create(['Status' => 'Aangeboden', 'year' => date('Y')]);
        $agency->clothing()->create(['Status' => 'Afgehandeld', 'year' => date('Y')]);

        $this->assertCount(2, $agency->open);
    }

    /**
     * The verzamel_user relation must link an agency to the user who acts as its
     * collection point, and that user's agency() relation must return the agency back.
     */
    public function test_verzamel_user_relationship(): void
    {
        $user = User::factory()->create();
        $agency = Agency::factory()->create(['verzamel_user_id' => $user->id]);

        $this->assertEquals($user->id, $agency->verzamel_user->id);
        $this->assertEquals($agency->id, $user->agency->first()->id);
    }

    /**
     * The factory must produce a persistable agency with the mandatory Code and
     * Aanvrager fields filled in.
     */
    public function test_factory_creates_valid_agency(): void
    {
        $agency = Agency::factory()->create();

        $this->assertNotNull($agency->id);
        $this->assertNotNull($agency->Code);
        $this->assertNotNull($agency->Aanvrager);
    }
}
