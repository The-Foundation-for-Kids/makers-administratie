<?php

namespace App\Filament\Resources\UserInvitationResource;

use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use App\Models\UserInvitation;
use App\Mail\UserInvitationMail;
use Filament\Resources\Resource;
use Filament\Actions\DeleteAction;
use Illuminate\Support\Facades\Mail;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Spatie\Permission\Traits\HasRoles;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Model;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\CheckboxList;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\UserInvitationResource\Pages;
use App\Filament\Resources\UserInvitationResource\RelationManagers;
use App\Filament\Resources\UserInvitationResource\Pages\ManageUserInvitations;

class UserInvitationResource extends Resource
{

    protected static ?string $model = UserInvitation::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';

    protected static ?string $navigationLabel = 'Uitnodigingen';
    public static ?string $pluralModelLabel = 'Openstaande uitnodigingen';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?int $navigationSort = 25;
    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('email')
                    ->email()
                    ->unique(UserInvitation::class, ignoreRecord: true)
                    ->required()
                    ->autofocus()
                    ->autocomplete(false),

                CheckboxList::make('roles')
                    ->relationship('roles', 'name', fn(Builder $query) => $query->whereIn('name', ['maakster','verzamelpunt']))
                    ->columns(2),
            ]);
    }
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')
                    ->searchable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('resend')
                    ->label('Opnieuw')
                    ->icon('heroicon-o-inbox-stack')
                    ->requiresConfirmation()
                    ->modalIcon('heroicon-o-inbox-stack')
                    ->modalHeading('Opnieuw verzenden')
                    ->modalSubmitActionLabel('Verzenden')
                    ->action(function (Model $record) {
                        Mail::to($record->email)->send(new UserInvitationMail($record));
                        Notification::make()
                            ->success()
                            ->title('Uitnodiging verzonden')
                            ->body('De gebruiker heeft een uitnodiging gehad.')
                            ->send();
                    }),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageUserInvitations::route('/'),
        ];
    }
}
