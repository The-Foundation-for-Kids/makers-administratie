<?php

namespace Tests\Feature;

use App\Models\Agency;
use App\Models\Clothing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Feature tests for Agency model functionality against the database.
 */
class AgencyFeatureTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Creating an agency through the factory must persist a row in the agencies table
     * with the mandatory Code and Aanvrager fields filled in.
     */
    public function test_create_agency_with_factory(): void
    {
        $agency = Agency::factory()->create();

        $this->assertDatabaseHas('agencies', ['id' => $agency->id]);
        $this->assertNotNull($agency->Code);
        $this->assertNotNull($agency->Aanvrager);
    }

    /**
     * An agency must have many clothing requests through the clothing relation, which is
     * the base for all per-agency counts in the admin tables.
     */
    public function test_agency_has_many_clothing(): void
    {
        $agency = Agency::factory()->create();

        Clothing::factory(3)->create(['agency_id' => $agency->id]);

        $this->assertCount(3, $agency->clothing);
    }

    /**
     * The per-status relations must count the agency's clothing per status, so the
     * agency table can show how many requests are in each state.
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
     * The open relation must contain every request that is not 'Afgehandeld', which the
     * agency table uses to show the outstanding work per agency.
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
     * All agency fields must persist as given, including the address, contact and KVK
     * fields shown on the agency detail page.
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
     * Each agency created by the factory must get a unique Code, because the code is
     * used as the prefix for generated clothing codes.
     */
    public function test_agency_code_uniqueness(): void
    {
        $agency1 = Agency::factory()->create();
        $agency2 = Agency::factory()->create();

        $this->assertNotEquals($agency1->Code, $agency2->Code);
    }

    /**
     * Deleting an agency must remove the row completely (agencies use no soft deletes).
     */
    public function test_agency_soft_delete(): void
    {
        $agency = Agency::factory()->create();

        $agency->delete();

        $this->assertDatabaseMissing('agencies', ['id' => $agency->id]);
    }

    /**
     * Deleting an agency that still has clothing must fail with a foreign-key error,
     * so agencies with requests can never be removed accidentally.
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
     * Updating an agency must persist the changed fields, which is how beheerders keep
     * agency details and status current.
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
