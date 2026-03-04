<?php

namespace App\Filament\Resources\Agencies;

use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Agency;
use Filament\Tables\Table;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Pages\Actions\CreateAction;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\DatePicker;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\AgencyResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use  Illuminate\Database\Eloquent\Relations\HasMany;
use App\Filament\Resources\Agencies\Pages\EditAgency;

use App\Filament\Resources\Agencies\Pages\CreateAgency;
use App\Filament\Resources\Agencies\Pages\ListAgencies;
use App\Filament\Resources\Agencies\RelationManagers\ClothingRelationManager;


class AgencyResource extends Resource
{
    protected static ?string $model = Agency::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';
    protected static ?string $navigationLabel = 'Instanties';
    protected static ?int $navigationSort = 20;
    public static ?string $pluralModelLabel = 'Instanties';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([

                Select::make('Status')
                    ->options([
                        'Actief' => 'Actief',
                        'Inactief' => 'Inactief',
                        'Onbekend' => 'Onbekend',
                    ]),

                TextInput::make('Code'),


                Tabs::make('Heading')->columns(2)->columnSpan('full')
                    ->tabs([
                        Tab::make('Adres Instantie')
                            ->schema([
                                TextInput::make('Aanvrager')->columnSpan('full'),
                                TextInput::make('Naam_Contactpersoon')->label('Contactpersoon'),
                                TextInput::make('Emailadres_Contactpersoon')->label('Email')->email(),
                                TextInput::make('Adres')->label('Straat')->columnSpan('full'),
                                TextInput::make('Postcode')->required(),
                                TextInput::make('Vestigingsplaats')->label('Plaats'),

                            ]),
                        Tab::make('Bezorging')
                            ->schema([
                                Select::make('verzamel_user_id')
                                    ->label('Verzamelpunt')
                                    ->options(User::role('Verzamelpunt')->pluck('name', 'id'))
                                    ->searchable(),

                                TextInput::make('Bezorg_Naam')->label('Naam'),
                                TextInput::make('Bezorg_Email')->label('Email')->email(),
                                TextInput::make('Bezorg_Adres')->label('Straat')->columnSpan('full'),
                                TextInput::make('Bezorg_Postcode')->label('Postcode'),
                                TextInput::make('Bezorg_Plaats')->label('Plaats'),
                            ]),
                        Tab::make('Details')
                            ->schema([
                                TextInput::make('Overige_Mail')->email(),
                                TextInput::make('KVK-nummer'),
                                TextInput::make('Partner_2022'),
                                TextInput::make('Extra_info'),
                                TextInput::make('Bezoek'),
                            ]),
/*                        Tab::make('Planning')
                            ->schema([
                                Repeater::make('planning')->relationship()
                                    ->schema([
                                        Select::make('name')
                                            ->required()
                                            ->options(['zomersetjes', 'wintersetjes', 'decemberactie']),
                                        DatePicker::make('dateExecution')
                                            ->native(false)
                                            ->label('Datum plaatsing')
                                            ->displayFormat('d-m')
                                            ->required()

                                    ])->columns(2)
                            ])
  */
                    ]),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //Tables\Columns\TextColumn::make('id')->sortable()->searchable(),
                TextColumn::make('Code')->sortable()->searchable(),
                TextColumn::make('Status')->sortable()->searchable(),
                TextColumn::make('Aanvrager')->sortable()->searchable(),

                TextColumn::make('open_count')->counts('open')->sortable()->label('Totaal'),
                TextColumn::make('ingevoerd_count')->counts('ingevoerd')->sortable()->label('Inge'),
                TextColumn::make('aangeboden_count')->counts('aangeboden')->sortable()->label('Aang'),
                TextColumn::make('opgepakt_count')->counts('opgepakt')->sortable()->label('Opge'),
                TextColumn::make('klaar_count')->counts('klaar')->sortable()->label('Klaa'),
                TextColumn::make('verzonden_count')->counts('verzonden')->sortable()->label('Verz'),
                TextColumn::make('ontvangen_count')->counts('ontvangen')->sortable()->label('Ontv'),
                TextColumn::make('afgehandeld_count')->counts('afgehandeld')->sortable()->label('Afg'),

                //
                //    ])->defaultSort('open_count','desc')
            ])->defaultSort('open_count', 'desc')
            ->filters([])
            ->recordActions([])
            ->toolbarActions([]);
    }

    public static function getRelations(): array
    {
        return [
            ClothingRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAgencies::route('/'),
            'create' => CreateAgency::route('/create'),
            'edit' => EditAgency::route('/{record}/edit'),
        ];
    }
}
