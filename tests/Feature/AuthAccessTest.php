<?php

namespace Tests\Feature;

use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for access to the admin panel: login page, panel authorization via
 * Shield permissions, the Sanctum API route and the last-login middleware.
 */
class AuthAccessTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A guest hitting the panel root must be redirected to the login page instead of
     * seeing any administration page.
     */
    public function test_guest_is_redirected_from_panel_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect();
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    /**
     * The login page must be publicly reachable so users can sign in.
     */
    public function test_login_page_is_publicly_accessible(): void
    {
        $this->get('/login')->assertOk();
    }

    /**
     * Any active user must be able to open the dashboard, which shows a different
     * welcome text depending on whether the user has roles.
     */
    public function test_active_user_can_open_dashboard(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)->get('/')->assertOk();
    }

    /**
     * canAccessPanel() must return true only for active users, because inactive
     * accounts are blocked from the whole panel even with valid credentials.
     */
    public function test_can_access_panel_depends_on_active_flag(): void
    {
        $panel = Filament::getPanel('admin');

        $this->assertTrue(User::factory()->create(['active' => true])->canAccessPanel($panel));
        $this->assertFalse(User::factory()->create(['active' => false])->canAccessPanel($panel));
    }

    /**
     * An authenticated user without the 'ViewAny:Clothing' permission must get a 403
     * on the clothing resource, proving the Shield policies guard the admin lists.
     */
    public function test_user_without_permission_gets_403_on_clothing_resource(): void
    {
        $user = User::factory()->create(['active' => true]);

        $this->actingAs($user)->get('/clothing')->assertForbidden();
    }

    /**
     * A user who is granted the 'ViewAny:Clothing' permission must be able to open the
     * clothing list, which is the base permission every beheerder role needs.
     */
    public function test_user_with_viewany_permission_can_open_clothing_resource(): void
    {
        // The clothing form queries these roles, so they must exist to render the page.
        Role::create(['name' => 'Maakster', 'guard_name' => 'web']);
        Role::create(['name' => 'Verzamelpunt', 'guard_name' => 'web']);
        Permission::create(['name' => 'ViewAny:Clothing', 'guard_name' => 'web']);

        $user = User::factory()->create(['active' => true]);
        $user->givePermissionTo('ViewAny:Clothing');

        $this->actingAs($user)->get('/clothing')->assertOk();
    }

    /**
     * The /api/user route must reject guests with a 401, since it sits behind the
     * auth:sanctum middleware.
     */
    public function test_api_user_rejects_guests(): void
    {
        $this->getJson('/api/user')->assertUnauthorized();
    }

    /**
     * A request with a valid Sanctum bearer token must return the authenticated user's
     * data as JSON, which is the only API endpoint the app exposes.
     */
    public function test_api_user_returns_authenticated_user_with_token(): void
    {
        $user = User::factory()->create(['active' => true]);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/user');

        $response->assertOk()->assertJsonPath('email', $user->email);
    }

    /**
     * Every authenticated panel request passes through LogSuccessfulLogin, so the
     * user's last_login_at must be updated when the dashboard is opened.
     */
    public function test_panel_request_updates_last_login_at(): void
    {
        $user = User::factory()->create(['active' => true]);
        $this->assertNull($user->last_login_at);

        $this->actingAs($user)->get('/')->assertOk();

        $this->assertNotNull($user->fresh()->last_login_at);
    }
}
