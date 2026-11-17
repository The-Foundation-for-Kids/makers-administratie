<?php

namespace Tests\Feature;

use App\Filament\Resources\Clothing\ClothingResource;
use App\Filament\Resources\Collectors\CollectorResource;
use App\Filament\Resources\Completeds\CompletedResource;
use App\Filament\Resources\CreatorHistories\CreatorHistoryResource;
use App\Filament\Resources\Creators\CreatorResource;
use App\Filament\Resources\Offers\OfferResource;
use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Feature tests for the role-based views: each Filament resource scopes the shared
 * clothing table to exactly the requests its role is allowed to see.
 */
class ResourceQueryTest extends TestCase
{
    use RefreshDatabase;

    /**
     * The maker view (CreatorResource) must show only the requests assigned to the
     * logged-in maker that are still in progress ('Opgepakt' or 'Klaar').
     */
    public function test_creator_query_shows_only_own_active_requests(): void
    {
        $maker = User::factory()->create();
        $other = User::factory()->create();
        $agency = Agency::factory()->create();

        $ownOpgepakt = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'maakster_id' => $maker->id]);
        $ownKlaar = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Klaar', 'maakster_id' => $maker->id]);
        $ownVerzonden = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Verzonden', 'maakster_id' => $maker->id]);
        $otherOpgepakt = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'maakster_id' => $other->id]);

        $this->actingAs($maker);
        $ids = CreatorResource::getEloquentQuery()->pluck('id');

        $this->assertContains($ownOpgepakt->id, $ids);
        $this->assertContains($ownKlaar->id, $ids);
        $this->assertNotContains($ownVerzonden->id, $ids);
        $this->assertNotContains($otherOpgepakt->id, $ids);
    }

    /**
     * The offers view (OfferResource) must show all 'Aangeboden' requests whose
     * voortoekenning is not blocking them: no expiry date, an expired date, or a
     * reservation for the logged-in user.
     */
    public function test_offer_query_shows_aangeboden_respecting_voortoekenning(): void
    {
        $maker = User::factory()->create();
        $other = User::factory()->create();
        $agency = Agency::factory()->create();

        $noVoortoekenning = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        $expiredVoortoekenning = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'Status' => 'Aangeboden',
            'Datum_Verlopen_Voortoekenning' => Carbon::now()->subDay(),
            'Gebruiker_Voortoekenning' => $other->id,
        ]);
        $reservedForOther = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'Status' => 'Aangeboden',
            'Datum_Verlopen_Voortoekenning' => Carbon::now()->addDays(5),
            'Gebruiker_Voortoekenning' => $other->id,
        ]);
        $reservedForMe = Clothing::factory()->create([
            'agency_id' => $agency->id,
            'Status' => 'Aangeboden',
            'Datum_Verlopen_Voortoekenning' => Carbon::now()->addDays(5),
            'Gebruiker_Voortoekenning' => $maker->id,
        ]);
        $opgepakt = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt']);

        $this->actingAs($maker);
        $ids = OfferResource::getEloquentQuery()->pluck('id');

        $this->assertContains($noVoortoekenning->id, $ids);
        $this->assertContains($expiredVoortoekenning->id, $ids);
        $this->assertContains($reservedForMe->id, $ids);
        $this->assertNotContains($reservedForOther->id, $ids);
        $this->assertNotContains($opgepakt->id, $ids);
    }

    /**
     * The collection-point view (CollectorResource) must show only the requests of the
     * agencies assigned to the logged-in user, limited to the statuses a collector
     * handles ('Aangeboden', 'Opgepakt', 'Klaar', 'Verzonden').
     */
    public function test_collector_query_shows_only_own_agencies_active_requests(): void
    {
        $collector = User::factory()->create();
        $agency = Agency::factory()->create(['verzamel_user_id' => $collector->id]);
        $otherAgency = Agency::factory()->create();

        $aangeboden = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        $verzonden = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Verzonden']);
        $afgehandeld = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld']);
        $otherAgencyItem = Clothing::factory()->create(['agency_id' => $otherAgency->id, 'Status' => 'Aangeboden']);

        $this->actingAs($collector);
        $ids = CollectorResource::getEloquentQuery()->pluck('id');

        $this->assertContains($aangeboden->id, $ids);
        $this->assertContains($verzonden->id, $ids);
        $this->assertNotContains($afgehandeld->id, $ids);
        $this->assertNotContains($otherAgencyItem->id, $ids);
    }

    /**
     * A user without any agency (no verzamel_user_id) must see an empty collection-point
     * view instead of all requests, so collectors never see other agencies' data.
     */
    public function test_collector_query_is_empty_for_user_without_agency(): void
    {
        $user = User::factory()->create();
        Clothing::factory()->aangeboden()->create();

        $this->actingAs($user);

        $this->assertCount(0, CollectorResource::getEloquentQuery()->get());
    }

    /**
     * The completed view (CompletedResource) must show only requests with status
     * 'Afgehandeld' and nothing else.
     */
    public function test_completed_query_shows_only_afgehandeld(): void
    {
        $agency = Agency::factory()->create();
        $afgehandeld = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld']);
        $ontvangen = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Ontvangen']);

        $this->actingAs(User::factory()->create());
        $ids = CompletedResource::getEloquentQuery()->pluck('id');

        $this->assertContains($afgehandeld->id, $ids);
        $this->assertNotContains($ontvangen->id, $ids);
    }

    /**
     * The maker archive view (CreatorHistoryResource) must show the logged-in maker's
     * finished requests (any status except 'Opgepakt'/'Klaar') including archived ones.
     */
    public function test_creator_history_query_shows_own_finished_requests_including_archived(): void
    {
        $maker = User::factory()->create();
        $other = User::factory()->create();
        $agency = Agency::factory()->create();

        $ownVerzonden = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Verzonden', 'maakster_id' => $maker->id]);
        $ownArchived = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld', 'maakster_id' => $maker->id]);
        $ownArchived->archive();
        $ownOpgepakt = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt', 'maakster_id' => $maker->id]);
        $otherVerzonden = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Verzonden', 'maakster_id' => $other->id]);

        $this->actingAs($maker);
        $ids = CreatorHistoryResource::getEloquentQuery()->pluck('id');

        $this->assertContains($ownVerzonden->id, $ids);
        $this->assertContains($ownArchived->id, $ids);
        $this->assertNotContains($ownOpgepakt->id, $ids);
        $this->assertNotContains($otherVerzonden->id, $ids);
    }

    /**
     * The admin clothing view (ClothingResource) must show every request that is not
     * 'Afgehandeld' yet and not archived, which is the working queue for beheerders.
     */
    public function test_clothing_query_hides_afgehandeld_and_archived(): void
    {
        $agency = Agency::factory()->create();
        $open = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Opgepakt']);
        $afgehandeld = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Afgehandeld']);
        $archived = Clothing::factory()->create(['agency_id' => $agency->id, 'Status' => 'Aangeboden']);
        $archived->archive();

        $this->actingAs(User::factory()->create());
        $ids = ClothingResource::getEloquentQuery()->pluck('id');

        $this->assertContains($open->id, $ids);
        $this->assertNotContains($afgehandeld->id, $ids);
        $this->assertNotContains($archived->id, $ids);
    }
}
