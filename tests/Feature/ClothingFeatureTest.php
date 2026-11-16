<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Clothing model functionality
 */
class ClothingFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating clothing with factory
     */
    public function test_create_clothing_with_factory(): void
    {
        $clothing = Clothing::factory()->create();
        
        $this->assertDatabaseHas('clothing', ['id' => $clothing->id]);
        $this->assertNotNull($clothing->Code);
        $this->assertEquals('Ingevoerd', $clothing->Status);
    }

    /**
     * Test creating clothing with agency relationship
     */
    public function test_create_clothing_with_agency(): void
    {
        $agency = Agency::factory()->create();
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);
        
        $this->assertDatabaseHas('clothing', ['id' => $clothing->id, 'agency_id' => $agency->id]);
        $this->assertEquals($agency->id, $clothing->agency_id);
    }

    /**
     * Test clothing status transitions
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
     * Test creating clothing with different statuses
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
     * Test querying clothing by status
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
     * Test clothing code generation on creation
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
     * Test clothing year field
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
     * Test clothing with all main fields
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
            'priority' => 'hoog',
        ]);
        
        $this->assertDatabaseHas('clothing', [
            'id' => $clothing->id,
            'Leeftijd' => '5',
            'Geslacht' => 'Jongen',
            'Maat' => '110',
            'Wens' => 'Broek en trui',
            'Kleuren' => 'Blauw, Rood',
            'priority' => 'hoog',
        ]);
    }

    /**
     * Test soft delete functionality
     */
    public function test_soft_delete_clothing(): void
    {
        $clothing = Clothing::factory()->create();
        
        $clothing->delete();
        
        $this->assertSoftDeleted($clothing);
        $this->assertCount(0, Clothing::all());
        $this->assertCount(1, Clothing::withTrashed()->get());
    }
}
