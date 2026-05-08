<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Filament\Resources\Users\Widgets\UserStats;
use App\Models\User;
use BezhanSalleh\FilamentShield\Support\Utils;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Page;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

use Filament\Tables;
use Filament\Tables\Columns\IconColumn;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';

    protected static ?string $navigationLabel = 'User';
    public static ?string $pluralModelLabel = 'User';
    protected static ?string $slug = 'user';
    //protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-users';
    //    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-currency-euro';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 23;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('email')
                            ->email()
                            ->unique(ignoreRecord: true)
                            //->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->required()
                            ->rule(Password::default())

                            ->maxLength(255)
                            ->dehydrateStateUsing(
                                static fn(null|string $state): null|string =>
                                filled($state) ? Hash::make($state) : null,
                            )->required(
                                static fn(Page $livewire): bool =>
                                $livewire instanceof CreateUser,
                            )->dehydrated(
                                static fn(null|string $state): bool =>
                                filled($state),
                            )->label(
                                static fn(Page $livewire): string => ($livewire instanceof EditUser) ? 'New Password' : 'Password'
                            ),
                        Textarea::make('notes')->label('Notities')
                            ->rows(4)
                            ->cols(10)
                            ->columnSpan('full'),
                        CheckboxList::make('roles')
                            ->relationship('roles', 'name', fn(Builder $query) => $query->where('name', '!=', 'super_admin'))
                            ->columns(2),
                        Toggle::make('voortoekenning')->label('Voortoekenning'),

                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->sortable()->searchable()->toggleable(),
                ToggleColumn::make('active')->toggleable(),
                TextColumn::make('roles.name')->searchable()->grow(false)->toggleable(),
                TextColumn::make('email')->sortable()->searchable()->toggleable(),

                IconColumn::make('notes')
                    ->sortable()
                    ->label('Notitie')
                    ->tooltip(fn(User $record): string => $record->notes ? $record->notes : "")
                    ->icons([
                        'heroicon-o-pencil',
                        '' => fn($state, $record): bool => $record->notes === null,

                    ])
                    ->toggleable(),

                TextColumn::make('clothing_count')->counts('clothing')->sortable()->label('Opgepakt')->toggleable(),
                TextColumn::make('last_login_at')
                    ->label('Loggedin')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('created_at')
                    ->label('Aangemaakt')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('updated_at')
                    ->label('Update')
                    ->date('d-m-Y')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
            ])->defaultSort('created_at', 'desc')
            ->filters([
                Filter::make('Voortoekenning')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->where('voortoekenning', '1')
                    ),
                Filter::make('Maaksters')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereHas('roles', function ($query) {
                            return $query->where('name', 'Maakster');
                        })
                    ),
                Filter::make('Beheerders')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereHas('roles', function ($query) {
                            return $query->where('name', 'Beheerder');
                        })
                    ),
                Filter::make('Overig')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereHas('roles', function ($query) {
                            return $query->whereIn('name', ['Donateur', 'Anders']);
                        })
                    ),
                Filter::make('Geen rechten')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->whereDoesntHave('roles')
                    ),
                Filter::make('Disabled')
                    ->query(
                        fn(Builder $query): Builder =>
                        $query->where('active', false)
                    ),
            ])
            ->recordActions([])
            ->toolbarActions([
                DeleteBulkAction::make(),
                ExportBulkAction::make()->exports([
                    ExcelExport::make()->fromTable()
                        ->withWriterType(Excel::XLSX)
                        ->withFilename('Ledenadministratie -' . date('Y-m-d'))
                ])
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getNavigationBadge(): ?string
    {

        $countroles = static::getModel()::whereDoesntHave('roles')->count();
        if ($countroles == 0) {
            return null;
        } else {
            return $countroles;
        }
    }

    public static function getWidgets(): array
    {
        return [
            UserStats::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
