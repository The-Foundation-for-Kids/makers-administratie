<?php

namespace Tests\Unit;

use App\Models\Agency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Agency model
 */
class AgencyTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $agency = new Agency();
        
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
     * Test table name
     */
    public function test_table_name(): void
    {
        $agency = new Agency();
        $this->assertEquals('agencies', $agency->getTable());
    }

    /**
     * Test clothing relationship
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
     * Test month scope methods
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
     * Test status scope methods
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
     * Test open scope (not Afgehandeld)
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
     * Test factory creates valid agency
     */
    public function test_factory_creates_valid_agency(): void
    {
        $agency = Agency::factory()->create();
        
        $this->assertNotNull($agency->id);
        $this->assertNotNull($agency->Code);
        $this->assertNotNull($agency->Aanvrager);
    }
}
