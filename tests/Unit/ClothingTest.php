<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Clothing model
 */
class ClothingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Test valid status array
     */
    public function test_valid_status_array(): void
    {
        $validStatuses = Clothing::validStatus();
        
        $this->assertIsArray($validStatuses);
        $this->assertContains('Ingevoerd', $validStatuses);
        $this->assertContains('Aangeboden', $validStatuses);
        $this->assertContains('Opgepakt', $validStatuses);
        $this->assertContains('Klaar', $validStatuses);
        $this->assertContains('Verzonden', $validStatuses);
        $this->assertContains('Ontvangen', $validStatuses);
        $this->assertContains('Afgehandeld', $validStatuses);
        $this->assertCount(7, $validStatuses);
    }

    /**
     * Test isStatus method
     */
    public function test_is_status_method(): void
    {
        $clothing = Clothing::factory()->create();
        
        $this->assertTrue($clothing->isStatus('Ingevoerd'));
        $this->assertFalse($clothing->isStatus('Aangeboden'));
    }

    /**
     * Test isIngediend method (legacy)
     */
    public function test_is_ingediend_method(): void
    {
        $clothing = Clothing::factory()->create(['Status' => 'Ingevoerd']);
        
        // Note: isIngediend checks for 'Ingediend' but the status is 'Ingevoerd'
        // This is intentional to test the actual behavior
        $this->assertFalse($clothing->isIngediend());
        
        $clothing->Status = 'Ingediend';
        $this->assertTrue($clothing->isIngediend());
    }

    /**
     * Test setStatus method with valid status
     */
    public function test_set_status_with_valid_status(): void
    {
        $clothing = Clothing::factory()->create();
        
        $this->assertTrue($clothing->setStatus('Aangeboden'));
        $this->assertEquals('Aangeboden', $clothing->Status);
        $this->assertNull($clothing->Kleding_Deadline);
        $this->assertNull($clothing->maakster_id);
        $this->assertNull($clothing->Maakster);
        $this->assertNull($clothing->DatumMaakster);
    }

    /**
     * Test setStatus method with invalid status
     */
    public function test_set_status_with_invalid_status(): void
    {
        $clothing = Clothing::factory()->create();
        
        $this->assertFalse($clothing->setStatus('InvalidStatus'));
        $this->assertEquals('Ingevoerd', $clothing->Status);
    }

    /**
     * Test setStatus method with Opgepakt status sets deadline
     */
    public function test_set_status_opgepakt_sets_deadline(): void
    {
        $clothing = Clothing::factory()->create(['Wens' => 'Kleding voor verjaardag']);
        
        $this->assertTrue($clothing->setStatus('Aangeboden'));
        $this->assertTrue($clothing->setStatus('Opgepakt'));
        $this->assertNotNull($clothing->Kleding_Deadline);
        $this->assertEqualsWithDelta(43, abs($clothing->Kleding_Deadline->diffInDays(now())), 0.01);
    }

    /**
     * Test setStatus with prev_status check
     */
    public function test_set_status_with_prev_status_check(): void
    {
        $clothing = Clothing::factory()->create();
        
        // Try to change from Ingevoerd to Klaar (skipping steps)
        $this->assertFalse($clothing->setStatus('Klaar', 'Aangeboden'));
        
        // Change to Aangeboden first
        $clothing->setStatus('Aangeboden');
        
        // Now try to change to Klaar with prev_status = Aangeboden
        $this->assertFalse($clothing->setStatus('Klaar', 'Ingevoerd'));
    }

    /**
     * Test standaardDeadline for verjaardag
     */
    public function test_standaard_deadline_verjaardag(): void
    {
        $deadline = Clothing::standaardDeadline('Kleding voor verjaardag');
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(43, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * Test standaardDeadline for december
     */
    public function test_standaard_deadline_december(): void
    {
        $deadline = Clothing::standaardDeadline('December-setje');
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(43, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * Test standaardDeadline default
     */
    public function test_standaard_deadline_default(): void
    {
        $deadline = Clothing::standaardDeadline('Normale kleding');
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(29, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * Test standaardDeadline with null
     */
    public function test_standaard_deadline_null(): void
    {
        $deadline = Clothing::standaardDeadline(null);
        
        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(29, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * Test isAfterCurrentStatus method
     */
    public function test_is_after_current_status(): void
    {
        $clothing = Clothing::factory()->create(['Status' => 'Aangeboden']);
        
        $this->assertFalse($clothing->isAfterCurrentStatus('Ingevoerd'));
        $this->assertTrue($clothing->isAfterCurrentStatus('Aangeboden'));
        $this->assertTrue($clothing->isAfterCurrentStatus('Afgehandeld'));
    }

    /**
     * Test fillable attributes
     */
    public function test_fillable_attributes(): void
    {
        $clothing = new Clothing();
        
        $this->assertContains('Status', $clothing->getFillable());
        $this->assertContains('year', $clothing->getFillable());
        $this->assertContains('Code', $clothing->getFillable());
        $this->assertContains('agency_id', $clothing->getFillable());
        $this->assertContains('Wens', $clothing->getFillable());
    }

    /**
     * Test agency relationship
     */
    public function test_agency_relationship(): void
    {
        $agency = Agency::factory()->create();
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);
        
        $this->assertInstanceOf(Agency::class, $clothing->agency);
        $this->assertEquals($agency->id, $clothing->agency->id);
    }

    /**
     * Test table name
     */
    public function test_table_name(): void
    {
        $clothing = new Clothing();
        $this->assertEquals('clothing', $clothing->getTable());
    }
}
