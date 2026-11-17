<?php

namespace Tests\Feature;

use App\Filament\Pages\Register;
use App\Mail\UserInvitationMail;
use App\Models\User;
use App\Models\UserInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Feature tests for the user invitation flow: the signed register URL, the
 * invitation mail and completing the registration.
 */
class UserInvitationTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A correctly signed register URL for an existing invitation must render the
     * registration page with the invitation's e-mail prefilled.
     */
    public function test_signed_register_url_with_valid_invitation_renders(): void
    {
        $invitation = UserInvitation::create(['email' => 'nieuw@example.com', 'code' => str_repeat('a', 32)]);

        $url = URL::signedRoute('filament.app.register', ['token' => $invitation->code]);

        $this->get($url)->assertOk();
    }

    /**
     * A correctly signed URL whose token matches no invitation must return a 404,
     * because Register::mount() looks up the invitation with firstOrFail().
     */
    public function test_signed_register_url_with_unknown_token_returns_404(): void
    {
        $url = URL::signedRoute('filament.app.register', ['token' => str_repeat('c', 32)]);

        $this->get($url)->assertNotFound();
    }

    /**
     * A register URL with a tampered signature must be rejected with a 403 by the
     * 'signed' middleware, so invitation links cannot be forged.
     */
    public function test_register_url_with_tampered_signature_is_rejected(): void
    {
        $invitation = UserInvitation::create(['email' => 'nieuw@example.com', 'code' => str_repeat('a', 32)]);

        $this->get('/register?token='.$invitation->code.'&signature=tampered')->assertForbidden();
    }

    /**
     * A register URL without any signature must also be rejected with a 403, because
     * the route requires a valid signed URL.
     */
    public function test_register_url_without_signature_is_rejected(): void
    {
        $this->get('/register?token='.str_repeat('a', 32))->assertForbidden();
    }

    /**
     * The invitation mail must have the fixed welcome subject and contain a signed
     * accept URL that carries the invitation token, so the recipient can register.
     */
    public function test_invitation_mail_contains_signed_accept_url_with_token(): void
    {
        $invitation = UserInvitation::create(['email' => 'nieuw@example.com', 'code' => str_repeat('b', 32)]);

        $mail = new UserInvitationMail($invitation);
        $mail->assertHasSubject('Welkom als maakster bij The Foundation for Kids');

        $html = $mail->render();
        $this->assertStringContainsString('token='.$invitation->code, $html);
        $this->assertStringContainsString('signature=', $html);

        $acceptUrl = $mail->content()->with['acceptUrl'];
        $this->assertStringContainsString('/register', $acceptUrl);
        $this->assertTrue(URL::hasValidSignature(
            \Illuminate\Http\Request::create($acceptUrl)
        ));
    }

    /**
     * Completing the registration through the invitation must create the user with the
     * invitation's e-mail, assign the invitation's roles, delete the invitation and log
     * the new user in.
     */
    public function test_registering_via_invitation_creates_user_with_roles_and_consumes_invitation(): void
    {
        Role::create(['name' => 'Maakster', 'guard_name' => 'web']);
        $invitation = UserInvitation::create(['email' => 'nieuw@example.com', 'code' => str_repeat('a', 32)]);
        $invitation->guard_name = 'web';
        $invitation->assignRole('Maakster');

        // Strong random password to satisfy the min(8)->mixedCase()->uncompromised() rule.
        $password = 'Abcdefgh'.\Illuminate\Support\Str::random(24);

        Livewire::test(Register::class, ['token' => $invitation->code])
            ->fillForm([
                'name' => 'Nieuw Lid',
                'password' => $password,
                'passwordConfirmation' => $password,
            ])
            ->call('register')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'nieuw@example.com')->first();
        $this->assertNotNull($user, 'Registering via the invitation should create the user');
        $this->assertTrue($user->hasRole('Maakster'));
        $this->assertDatabaseMissing('user_invitations', ['id' => $invitation->id]);
        $this->assertAuthenticatedAs($user);
    }
}
