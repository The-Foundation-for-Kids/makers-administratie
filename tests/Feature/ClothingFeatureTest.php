<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Clothing model functionality against the database.
 */
class ClothingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creating a clothing request through the factory must persist a row in the clothing
     * table with an auto-generated Code and the default status 'Ingevoerd'.
     */
    public function test_create_clothing_with_factory(): void
    {
        $clothing = Clothing::factory()->create();

        $this->assertDatabaseHas('clothing', ['id' => $clothing->id]);
        $this->assertNotNull($clothing->Code);
        $this->assertEquals('Ingevoerd', $clothing->Status);
    }

    /**
     * A clothing request must be linked to its agency through agency_id, so the request
     * shows up in the agency's lists and gets the agency code prefix.
     */
    public function test_create_clothing_with_agency(): void
    {
        $agency = Agency::factory()->create();
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);

        $this->assertDatabaseHas('clothing', ['id' => $clothing->id, 'agency_id' => $agency->id]);
        $this->assertEquals($agency->id, $clothing->agency_id);
    }

    /**
     * Status transitions through setStatus() must persist to the database and apply the
     * side effects: 'Aangeboden' clears maker and deadline, 'Opgepakt' sets pickup date
     * and deadline.
     */
    public function test_clothing_status_transitions(): void
    {
        $clothing = Clothing::factory()->create();

        // Test transition to Aangeboden
        $clothing->setStatus('Aangeboden');
        $clothing->save();

        $this->assertDatabaseHas('clothing', ['id' => $clothing->id, 'Status' => 'Aangeboden']);
        $this->assertNull($clothing->Kleding_Deadline);
        $this->assertNull($clothing->maakster_id);

        // Test transition to Opgepakt
        $user = User::factory()->create();
        $clothing->maakster_id = $user->id;
        $clothing->Maakster = $user->name;
        $clothing->setStatus('Opgepakt');
        $clothing->save();

        $this->assertDatabaseHas('clothing', ['id' => $clothing->id, 'Status' => 'Opgepakt']);
        $this->assertNotNull($clothing->Kleding_Deadline);
        $this->assertNotNull($clothing->DatumMaakster);
    }

    /**
     * Changing the status and saving must write a Spatie activity_log entry for the
     * clothing record, which powers the change history in the admin panel.
     */
    public function test_status_change_writes_activity_log_entry(): void
    {
        $clothing = Clothing::factory()->create();

        $clothing->setStatus('Aangeboden');
        $clothing->save();

        $this->assertDatabaseHas('activity_log', [
            'subject_type' => Clothing::class,
            'subject_id' => $clothing->id,
            'event' => 'updated',
        ]);
    }

    /**
     * The factory states must create requests with the matching status and the expected
     * side fields, so tests and seeders can rely on them.
     */
    public function test_create_clothing_with_different_statuses(): void
    {
        $agency = Agency::factory()->create();

        // Create with Aangeboden status
        $aangeboden = Clothing::factory()->aangeboden()->create(['agency_id' => $agency->id]);
        $this->assertEquals('Aangeboden', $aangeboden->Status);

        // Create with Opgepakt status
        $opgepakt = Clothing::factory()->opgepakt()->create(['agency_id' => $agency->id]);
        $this->assertEquals('Opgepakt', $opgepakt->Status);
        $this->assertNotNull($opgepakt->Kleding_Deadline);

        // Create with Klaar status
        $klaar = Clothing::factory()->klaar()->create(['agency_id' => $agency->id]);
        $this->assertEquals('Klaar', $klaar->Status);

        // Create with Afgehandeld status
        $afgehandeld = Clothing::factory()->afgehandeld()->create(['agency_id' => $agency->id]);
        $this->assertEquals('Afgehandeld', $afgehandeld->Status);
    }

    /**
     * Querying clothing by status must return exactly the records created with that
     * status, which is how the admin tables and stats count per status.
     */
    public function test_query_clothing_by_status(): void
    {
        $agency = Agency::factory()->create();

        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Ingevoerd']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld']);

        $ingevoerd = Clothing::where('Status', 'Ingevoerd')->count();
        $aangeboden = Clothing::where('Status', 'Aangeboden')->count();
        $opgepakt = Clothing::where('Status', 'Opgepakt')->count();
        $afgehandeld = Clothing::where('Status', 'Afgehandeld')->count();

        $this->assertEquals(1, $ingevoerd);
        $this->assertEquals(1, $aangeboden);
        $this->assertEquals(1, $opgepakt);
        $this->assertEquals(1, $afgehandeld);
    }

    /**
     * The code generated on creation must start with the agency code and be unique per
     * request, so every request can be referenced by its code.
     */
    public function test_clothing_code_generation(): void
    {
        $agency = Agency::factory()->create(['Code' => 'TEST']);

        // Create first clothing for this agency
        $clothing1 = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'year' => '2025',
        ]);

        $this->assertStringStartsWith('TEST', $clothing1->Code);

        // Create second clothing for this agency
        $clothing2 = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'year' => '2025',
        ]);

        $this->assertStringStartsWith('TEST', $clothing2->Code);
        $this->assertNotEquals($clothing1->Code, $clothing2->Code);
    }

    /**
     * The year field must be stored as given, because it drives the code generation and
     * the year filters in the reports.
     */
    public function test_clothing_year_field(): void
    {
        $agency = Agency::factory()->create();

        $clothing = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'year' => '2025',
        ]);

        $this->assertEquals('2025', $clothing->year);
    }

    /**
     * All main request fields must persist as given, and a numeric Leeftijd (Excel
     * serial) must be converted to a d-m-Y date by the creating hook.
     */
    public function test_clothing_with_all_main_fields(): void
    {
        $agency = Agency::factory()->create();

        $clothing = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'year' => '2025',
            'Leeftijd' => '5',
            'Geslacht' => 'Jongen',
            'Maat' => '110',
            'Wens' => 'Broek en trui',
            'Kleuren' => 'Blauw, Rood',
        ]);

        $this->assertDatabaseHas('clothing', [
            'id' => $clothing->id,
            'Leeftijd' => '04-01-1900',
            'Geslacht' => 'Jongen',
            'Maat' => '110',
            'Wens' => 'Broek en trui',
            'Kleuren' => 'Blauw, Rood',
        ]);
    }

    /**
     * Deleting a clothing record must remove the row completely (the model uses no soft
     * deletes), so deleted requests disappear from every view.
     */
    public function test_soft_delete_clothing(): void
    {
        $clothing = Clothing::factory()->create();

        $clothing->delete();

        $this->assertDatabaseMissing('clothing', ['id' => $clothing->id]);
        $this->assertCount(0, Clothing::all());
    }
}
