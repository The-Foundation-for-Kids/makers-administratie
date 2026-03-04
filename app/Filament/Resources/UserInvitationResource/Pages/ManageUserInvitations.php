<?php

namespace App\Filament\Resources\UserInvitationResource\Pages;

use Filament\Actions\CreateAction;
use App\Models\User;
use Filament\Actions;
use App\Models\UserInvitation;
use App\Mail\UserInvitationMail;
use Illuminate\Support\Facades\Mail;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use App\Filament\Resources\UserInvitationResource\UserInvitationResource;

class ManageUserInvitations extends ManageRecords
{
    protected static string $resource = UserInvitationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Uitnodigen')
                ->createAnother(false)
                ->mutateDataUsing(function (array $data): array {
                    $data['code'] = substr(md5(rand(0, 9) . $data['email'] . time()), 0, 32);;
                    return $data;
                })
                ->before(function (CreateAction $action, array $data) {
                    $user = User::where('email', $data['email'])->first();
                    if ($user) {
                        Notification::make()
                            ->danger()
                            ->title('Gebruiker bestaat al')
                            ->body('Dit email adres is al in gebruik in de administratie')
                            ->persistent()
                            ->send();

                        $action->halt();
                    }
                })
                ->after(function (UserInvitation $record) {
                    Mail::to($record->email)->send(new UserInvitationMail($record));
                })
                ->successNotification(
                    Notification::make()
                        ->success()
                        ->title('Uitnodiging verzonden')
                        ->body('De gebruiker heeft een email ontvangen met de uitnodiging'),
                ),
        ];
    }
}
