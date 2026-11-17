<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Clothing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Agency model functionality
 */
class AgencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test creating agency with factory
     */
    public function test_create_agency_with_factory(): void
    {
        $agency = Agency::factory()->create();
        
        $this->assertDatabaseHas('agencies', ['id' => $agency->id]);
        $this->assertNotNull($agency->Code);
        $this->assertNotNull($agency->Aanvrager);
    }

    /**
     * Test agency has many clothing
     */
    public function test_agency_has_many_clothing(): void
    {
        $agency = Agency::factory()->create();
        
        Clothing::factory(3)->create(['agency_id' => $agency->id]);
        
        $this->assertCount(3, $agency->clothing);
    }

    /**
     * Test agency clothing counts by status
     */
    public function test_agency_clothing_counts_by_status(): void
    {
        $agency = Agency::factory()->create();
        
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Ingevoerd']);
        Clothing::factory(2)->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        Clothing::factory(3)->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt']);
        
        $this->assertCount(1, $agency->ingevoerd);
        $this->assertCount(2, $agency->aangeboden);
        $this->assertCount(3, $agency->opgepakt);
    }

    /**
     * Test agency open clothing count
     */
    public function test_agency_open_clothing_count(): void
    {
        $agency = Agency::factory()->create();
        
        // Create open clothing
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Ingevoerd']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt']);
        
        // Create closed clothing
        Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld']);
        
        $this->assertCount(3, $agency->open);
    }

    /**
     * Test agency with all fields
     */
    public function test_agency_with_all_fields(): void
    {
        $agency = Agency::factory()->create([
            'Code' => 'TEST',
            'Status' => 'Actief',
            'Aanvrager' => 'Test Organisatie',
            'Adres' => 'Test Adres 1',
            'Postcode' => '1234AB',
            'Vestigingsplaats' => 'Test Stad',
            'Naam_Contactpersoon' => 'Test Contact',
            'Emailadres_Contactpersoon' => 'test@example.com',
            'Bezorg_Naam' => 'Bezorg Naam',
            'Bezorg_Email' => 'bezorg@example.com',
            'Bezorg_Adres' => 'Bezorg Adres 1',
            'Bezorg_Postcode' => '5678CD',
            'Bezorg_Plaats' => 'Bezorg Stad',
            'KVK-nummer' => '12345678',
            'Partner_2022' => true,
        ]);
        
        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'Code' => 'TEST',
            'Aanvrager' => 'Test Organisatie',
            'Adres' => 'Test Adres 1',
            'Postcode' => '1234AB',
            'Vestigingsplaats' => 'Test Stad',
            'KVK-nummer' => '12345678',
        ]);
    }

    /**
     * Test agency code uniqueness in factory
     */
    public function test_agency_code_uniqueness(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();
        
        $this->assertNotEquals($agency1->Code, $agency2->Code);
    }

    /**
     * Test agency delete (agencies have no soft deletes)
     */
    public function test_agency_soft_delete(): void
    {
        $agency = Agency::factory()->create();

        $agency->delete();

        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
    }

    /**
     * Test deleting agency with clothing (cascade)
     */
    public function test_delete_agency_with_clothing(): void
    {
        $agency = Agency::factory()->create();
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);

        // Foreign key prevents deleting an agency that still has clothing
        $this->expectException(\Illuminate\Database\QueryException::class);
        $agency->delete();
    }

    /**
     * Test updating agency
     */
    public function test_update_agency(): void
    {
        $agency = Agency::factory()->create();
        
        $agency->update([
            'Aanvrager' => 'Updated Organisatie',
            'Status' => 'Inactief',
        ]);
        
        $this->assertDatabaseHas('agencies', [
            'id' => $agency->id,
            'Aanvrager' => 'Updated Organisatie',
            'Status' => 'Inactief',
        ]);
    }
}
