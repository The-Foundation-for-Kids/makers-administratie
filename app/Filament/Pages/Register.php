<?php

namespace App\Filament\Pages;

use Filament\Auth\Http\Responses\Contracts\RegistrationResponse;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Filament\Schemas\Components\Component;
use Filament\Forms\Components\TextInput;
use App\Models\User;
use App\Models\UserInvitation;
use Filament\Forms;
use Livewire\Attributes\Url;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Auth\Events\Registered;
use Filament\Notifications\Notification;
use DanHarrin\LivewireRateLimiting\Exceptions\TooManyRequestsException;

class Register extends \Filament\Auth\Pages\Register
{
    #[Url]
    public $token = '';

    public ?UserInvitation $invitation = null;

    public ?array $data = [];

    public function mount(): void
    {
        $this->invitation = UserInvitation::where('code', $this->token)->firstOrFail();

        $this->form->fill([
            'email' => $this->invitation->email,
        ]);
    }

    public function register(): ?RegistrationResponse
    {
        try {
            $this->rateLimit(2);
        } catch (TooManyRequestsException $exception) {
            Notification::make()
                ->title(__('filament-panels::auth/pages/register.notifications.throttled.title', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]))
                ->body(array_key_exists('body', __('filament-panels::auth/pages/register.notifications.throttled') ?: []) ? __('filament-panels::pages/auth/register.notifications.throttled.body', [
                    'seconds' => $exception->secondsUntilAvailable,
                    'minutes' => ceil($exception->secondsUntilAvailable / 60),
                ]) : null)
                ->danger()
                ->send();

            return null;
        }

        $data = $this->form->getState();
        $user = $this->getUserModel()::create($data);
        $user->assignRole($this->invitation->getRoleNames());

        $this->invitation->delete();
        app()->bind(
            SendEmailVerificationNotification::class,
        );

        event(new Registered($user));

        Filament::auth()->login($user);

        session()->regenerate();

        return app(RegistrationResponse::class);
    }

    protected function getEmailFormComponent(): Component
    {
        return TextInput::make('email')
            ->label(__('filament-panels::auth/pages/register.form.email.label'))
            ->email()
            ->required()
            ->maxLength(255)
            ->unique($this->getUserModel())
            ->readOnly();
    }
}
