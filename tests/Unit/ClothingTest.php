<?php

namespace Tests\Unit;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\Collector;
use App\Models\Completed;
use App\Models\Confirm;
use App\Models\Creator;
use App\Models\CreatorHistory;
use App\Models\Management;
use App\Models\Offer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the Clothing model: status flow, deadlines, oppakken,
 * code generation, boot hooks and archiving.
 */
class ClothingTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The validStatus() helper must return the full status flow in the exact order
     * in which requests move through the administration.
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
     * The isStatus() helper must return true only for the status the record currently
     * has, so callers can branch on it safely.
     */
    public function test_is_status_method(): void
    {
        $clothing = Clothing::factory()->create();

        $this->assertTrue($clothing->isStatus('Ingevoerd'));
        $this->assertFalse($clothing->isStatus('Aangeboden'));
    }

    /**
     * The legacy isIngEDIEND() helper still checks for the old 'Ingediend' status string,
     * so a record with the current 'Ingevoerd' status must return false for it.
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
     * Moving a request to 'Aangeboden' is a valid transition and must clear the maker
     * assignment, pickup date and deadline because the request is offered again.
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
     * Passing a status that is not part of the flow must return false and leave the
     * current status untouched, so invalid transitions can never be persisted.
     */
    public function test_set_status_with_invalid_status(): void
    {
        $clothing = Clothing::factory()->create();

        $this->assertFalse($clothing->setStatus('InvalidStatus'));
        $this->assertEquals('Ingevoerd', $clothing->Status);
    }

    /**
     * Transitioning to 'Opgepakt' must stamp the pickup date and compute a default
     * deadline of 43 days for a birthday wish, because such requests are time-critical.
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
     * The optional previous-status guard must reject a transition when the record has
     * moved on since the caller read it, which protects against lost updates.
     */
    public function test_set_status_with_prev_status_check(): void
    {
        $clothing = Clothing::factory()->create();

        // Try to change from Ingevoerd to Klaar (skipping steps)
        $this->assertFalse($clothing->setStatus('Klaar', 'Aangeboden'));

        // Change to Aangeboden first
        $clothing->setStatus('Aangeboden');

        // Now try to change to Klaar with prev_status = Ingevoerd
        $this->assertFalse($clothing->setStatus('Klaar', 'Ingevoerd'));
    }

    /**
     * When the previous status matches the current status, the guarded transition must
     * succeed and apply the new status.
     */
    public function test_set_status_with_matching_prev_status_succeeds(): void
    {
        $clothing = Clothing::factory()->create();

        $this->assertTrue($clothing->setStatus('Aangeboden', 'Ingevoerd'));
        $this->assertEquals('Aangeboden', $clothing->Status);

        $this->assertFalse($clothing->setStatus('Klaar', 'Ingevoerd'));
        $this->assertEquals('Aangeboden', $clothing->Status);
    }

    /**
     * Walking the complete flow from Ingevoerd to Afgehandeld must accept every step
     * and persist each intermediate status, proving the flow itself is consistent.
     */
    public function test_full_status_flow_persists_every_step(): void
    {
        $clothing = Clothing::factory()->create();

        foreach (['Aangeboden', 'Opgepakt', 'Klaar', 'Verzonden', 'Ontvangen', 'Afgehandeld'] as $status) {
            $this->assertTrue($clothing->setStatus($status), "Transition to $status should be allowed");
            $clothing->save();
            $this->assertDatabaseHas('clothing', ['id' => $clothing->id, 'Status' => $status]);
        }

        $this->assertEquals('Afgehandeld', $clothing->fresh()->Status);
    }

    /**
     * Moving a claimed request back to 'Ingevoerd' or 'Aangeboden' must wipe the maker,
     * the maker name, the pickup date and the deadline so no stale assignment survives.
     */
    public function test_set_status_back_to_ingevoerd_or_aangeboden_clears_maker_and_deadline(): void
    {
        $maker = User::factory()->create();
        $clothing = Clothing::factory()->create([
            'Status' => 'Opgepakt',
            'maakster_id' => $maker->id,
            'Maakster' => $maker->name,
            'DatumMaakster' => now(),
            'Kleding_Deadline' => now()->addDays(29),
        ]);

        $this->assertTrue($clothing->setStatus('Aangeboden'));
        $this->assertNull($clothing->maakster_id);
        $this->assertNull($clothing->Maakster);
        $this->assertNull($clothing->DatumMaakster);
        $this->assertNull($clothing->Kleding_Deadline);

        $clothing->setStatus('Opgepakt');
        $clothing->maakster_id = $maker->id;
        $this->assertTrue($clothing->setStatus('Ingevoerd'));
        $this->assertNull($clothing->maakster_id);
        $this->assertNull($clothing->Kleding_Deadline);
    }

    /**
     * When a deadline was already set manually, transitioning to 'Opgepakt' must keep
     * that deadline instead of overwriting it with the default rule.
     */
    public function test_set_status_opgepakt_keeps_existing_deadline(): void
    {
        $deadline = now()->addDays(10)->startOfDay();
        $clothing = Clothing::factory()->create([
            'Status' => 'Aangeboden',
            'Kleding_Deadline' => $deadline,
        ]);

        $this->assertTrue($clothing->setStatus('Opgepakt'));
        $this->assertEquals($deadline->toDateString(), $clothing->Kleding_Deadline->toDateString());
    }

    /**
     * A birthday wish must produce a deadline 43 days out, giving makers six weeks
     * plus a day to finish the clothing before the party.
     */
    public function test_standaard_deadline_verjaardag(): void
    {
        $deadline = Clothing::standaardDeadline('Kleding voor verjaardag');

        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(43, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * A december wish is treated like a birthday wish and must also get the longer
     * 43-day deadline because of the holidays.
     */
    public function test_standaard_deadline_december(): void
    {
        $deadline = Clothing::standaardDeadline('December-setje');

        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(43, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * Any other wish falls back to the standard deadline of 29 days (four weeks plus
     * a day) so makers have a predictable default.
     */
    public function test_standaard_deadline_default(): void
    {
        $deadline = Clothing::standaardDeadline('Normale kleding');

        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(29, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * A missing wish must still return a valid Carbon instance with the standard
     * 29-day deadline instead of failing.
     */
    public function test_standaard_deadline_null(): void
    {
        $deadline = Clothing::standaardDeadline(null);

        $this->assertInstanceOf(\Carbon\Carbon::class, $deadline);
        $this->assertEqualsWithDelta(29, abs($deadline->diffInDays(now())), 0.01);
    }

    /**
     * isAfterCurrentStatus() must walk the flow in order: a status equal to or after
     * the current one returns true, an earlier status returns false.
     */
    public function test_is_after_current_status(): void
    {
        $clothing = Clothing::factory()->create(['Status' => 'Aangeboden']);

        $this->assertFalse($clothing->isAfterCurrentStatus('Ingevoerd'));
        $this->assertTrue($clothing->isAfterCurrentStatus('Aangeboden'));
        $this->assertTrue($clothing->isAfterCurrentStatus('Afgehandeld'));
    }

    /**
     * The fillable list must expose the core fields used by forms and imports, so mass
     * assignment of Status, year, Code, agency and wish keeps working.
     */
    public function test_fillable_attributes(): void
    {
        $clothing = new Clothing;

        $this->assertContains('Status', $clothing->getFillable());
        $this->assertContains('year', $clothing->getFillable());
        $this->assertContains('Code', $clothing->getFillable());
        $this->assertContains('agency_id', $clothing->getFillable());
        $this->assertContains('Wens', $clothing->getFillable());
    }

    /**
     * The agency() relation must resolve to the Agency the clothing request belongs
     * to, which the code generator and address builder rely on.
     */
    public function test_agency_relationship(): void
    {
        $agency = Agency::factory()->create();
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);

        $this->assertInstanceOf(Agency::class, $clothing->agency);
        $this->assertEquals($agency->id, $clothing->agency->id);
    }

    /**
     * The model must stay mapped to the 'clothing' table, because all role-based
     * subclasses share this single table.
     */
    public function test_table_name(): void
    {
        $clothing = new Clothing;
        $this->assertEquals('clothing', $clothing->getTable());
    }

    /**
     * All role-based subclasses (Creator, Collector, Offer, Completed, Confirm,
     * Management, CreatorHistory) must query the same 'clothing' table so a status
     * change is visible in every role view at once.
     */
    public function test_subclasses_share_the_clothing_table(): void
    {
        foreach ([Creator::class, Collector::class, Offer::class, Completed::class, Confirm::class, Management::class, CreatorHistory::class] as $class) {
            $this->assertEquals('clothing', (new $class)->getTable());
        }
    }

    /**
     * oppakken() must only succeed from 'Aangeboden' and then assign the logged-in maker
     * by id and name, stamp the pickup date and set a deadline.
     */
    public function test_oppakken_assigns_current_user_and_sets_pickup_data(): void
    {
        $maker = User::factory()->create();
        $this->actingAs($maker);
        $clothing = Clothing::factory()->aangeboden()->create();

        $this->assertTrue($clothing->oppakken());

        $clothing->refresh();
        $this->assertEquals('Opgepakt', $clothing->Status);
        $this->assertEquals($maker->id, $clothing->maakster_id);
        $this->assertEquals($maker->name, $clothing->Maakster);
        $this->assertNotNull($clothing->DatumMaakster);
        $this->assertNotNull($clothing->Kleding_Deadline);
    }

    /**
     * oppakken() on a request that is not 'Aangeboden' (anymore) must return false and
     * leave the record unchanged, so a request can never be claimed twice.
     */
    public function test_oppakken_fails_when_not_aangeboden(): void
    {
        $this->actingAs(User::factory()->create());
        $clothing = Clothing::factory()->create(['Status' => 'Ingevoerd']);

        $this->assertFalse($clothing->oppakken());
        $this->assertEquals('Ingevoerd', $clothing->fresh()->Status);
        $this->assertNull($clothing->fresh()->maakster_id);
    }

    /**
     * When another maker already claimed the request, a second oppakken() must fail
     * because the row lock reveals the status is no longer 'Aangeboden'.
     */
    public function test_oppakken_fails_when_already_claimed_by_another_maker(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $clothing = Clothing::factory()->aangeboden()->create();

        $this->actingAs($first);
        $this->assertTrue($clothing->oppakken());

        $this->actingAs($second);
        $this->assertFalse($clothing->oppakken());

        $clothing->refresh();
        $this->assertEquals($first->id, $clothing->maakster_id);
        $this->assertEquals('Opgepakt', $clothing->Status);
    }

    /**
     * The first request of an agency in a year must get code agency+YY+sequence starting
     * at year*10000+1, and each next request must increment that sequence by one.
     */
    public function test_code_generation_starts_at_year_sequence_and_increments(): void
    {
        $agency = Agency::factory()->create(['Code' => 'AB']);

        $first = Clothing::factory()->create(['agency_id' => $agency->id, 'year' => '2025']);
        $second = Clothing::factory()->create(['agency_id' => $agency->id, 'year' => '2025']);

        $this->assertEquals('AB250001', $first->Code);
        $this->assertEquals('AB250002', $second->Code);
    }

    /**
     * The code must embed the last two digits of the year field and restart the sequence
     * per agency, so codes stay unique and readable across years and agencies.
     */
    public function test_code_generation_uses_year_field_and_is_per_agency(): void
    {
        $agency = Agency::factory()->create(['Code' => 'AB']);
        $otherAgency = Agency::factory()->create(['Code' => 'XY']);

        $this->assertEquals('AB240001', Clothing::factory()->create(['agency_id' => $agency->id, 'year' => '2024'])->Code);
        $this->assertEquals('AB250001', Clothing::factory()->create(['agency_id' => $agency->id, 'year' => '2025'])->Code);
        $this->assertEquals('XY250001', Clothing::factory()->create(['agency_id' => $otherAgency->id, 'year' => '2025'])->Code);
    }

    /**
     * The creating hook must normalize free-text gender input starting with 'jong' or
     * 'meis' to the canonical 'Jongen'/'Meisje' values used by the filters.
     */
    public function test_geslacht_is_normalized_on_create(): void
    {
        $agency = Agency::factory()->create();

        $this->assertEquals('Jongen', Clothing::factory()->create(['agency_id' => $agency->id, 'Geslacht' => 'jongen'])->fresh()->Geslacht);
        $this->assertEquals('Meisje', Clothing::factory()->create(['agency_id' => $agency->id, 'Geslacht' => 'MEISJE'])->fresh()->Geslacht);
        $this->assertEquals('anders', Clothing::factory()->create(['agency_id' => $agency->id, 'Geslacht' => 'anders'])->fresh()->Geslacht);
    }

    /**
     * A numeric Leeftijd (Excel date serial, as produced by the import) must be
     * converted to a d-m-Y date string on create.
     */
    public function test_numeric_leeftijd_is_converted_from_excel_serial(): void
    {
        $clothing = Clothing::factory()->create(['Leeftijd' => 45000]);

        $expected = gmdate('d-m-Y', (45000 - 25569) * 86400);
        $this->assertEquals($expected, $clothing->fresh()->Leeftijd);
    }

    /**
     * A non-numeric Leeftijd (free text like '7 jaar') must be stored unchanged,
     * because only Excel serial numbers are converted.
     */
    public function test_non_numeric_leeftijd_is_kept_as_is(): void
    {
        $clothing = Clothing::factory()->create(['Leeftijd' => '7 jaar']);

        $this->assertEquals('7 jaar', $clothing->fresh()->Leeftijd);
    }

    /**
     * The creating hook must record Gebruiker_Ingevoerd as the logged-in user's id and
     * mention that user in the Activity log when the request is entered.
     */
    public function test_gebruiker_ingevoerd_and_activity_mention_logged_in_user(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $clothing = Clothing::factory()->create(['Status' => null]);

        $fresh = $clothing->fresh();
        $this->assertEquals('Ingevoerd', $fresh->Status);
        $this->assertEquals($user->id, $fresh->Gebruiker_Ingevoerd);
        $this->assertStringContainsString('-> Ingevoerd door '.$user->name, $fresh->Activity);
    }

    /**
     * For a guest (no logged-in user) the creating hook must leave Gebruiker_Ingevoerd
     * null and write 'onbekend' in the Activity log line.
     */
    public function test_activity_log_uses_onbekend_for_guests(): void
    {
        $clothing = Clothing::factory()->create(['Status' => null]);

        $fresh = $clothing->fresh();
        $this->assertNull($fresh->Gebruiker_Ingevoerd);
        $this->assertStringContainsString('door onbekend', $fresh->Activity);
    }

    /**
     * When the status changes, the updating hook must append a new Activity line with
     * the new status and the acting user; other field updates must not add lines.
     */
    public function test_activity_is_appended_on_status_change_but_not_on_other_updates(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $clothing = Clothing::factory()->create();

        $clothing->setStatus('Aangeboden');
        $clothing->save();
        $this->assertStringContainsString('-> Aangeboden door '.$user->name, $clothing->fresh()->Activity);

        $activityAfterStatusChange = $clothing->fresh()->Activity;
        $clothing->update(['Notities' => 'Alleen een notitie']);
        $this->assertEquals($activityAfterStatusChange, $clothing->fresh()->Activity);
    }

    /**
     * Archiving a request must hide it from normal queries (withArchived/onlyArchived
     * still see it) and unarchiving must make it visible again, which is how old
     * years are kept out of the working views.
     */
    public function test_archive_excludes_record_from_default_queries(): void
    {
        $clothing = Clothing::factory()->create();

        $clothing->archive();

        $this->assertTrue($clothing->isArchived());
        $this->assertEquals(0, Clothing::count());
        $this->assertEquals(1, Clothing::withArchived()->count());
        $this->assertEquals(1, Clothing::onlyArchived()->count());
        $this->assertEquals($clothing->id, Clothing::onlyArchived()->first()->id);

        $clothing->unarchive();

        $this->assertFalse($clothing->fresh()->isArchived());
        $this->assertEquals(1, Clothing::count());
    }

    /**
     * The adres() helper must build a single delivery address line from the agency
     * fields, including the 'T.a.v.' recipient line when a Bezorg_Naam is set.
     */
    public function test_adres_builds_delivery_address_from_agency(): void
    {
        $agency = Agency::factory()->create([
            'Aanvrager' => 'Test Org',
            'Bezorg_Naam' => 'Piet',
            'Bezorg_Adres' => 'Straat 1',
            'Bezorg_Postcode' => '1234AB',
            'Bezorg_Plaats' => 'Stad',
        ]);
        $clothing = Clothing::factory()->create(['agency_id' => $agency->id]);

        $this->assertEquals('Test Org, T.a.v. Piet, Straat 1, 1234AB  Stad', (string) $clothing->adres());

        $agency->update(['Bezorg_Naam' => null]);
        $this->assertEquals('Test Org, Straat 1, 1234AB  Stad', (string) $clothing->fresh()->adres());
    }
}
