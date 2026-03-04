<?php

namespace App\Filament\Resources\Completeds;

use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Agency;
use App\Models\Clothing;
use App\Models\Completed;
use Filament\Tables\Table;
use Filament\Resources\Resource;

use Illuminate\Support\HtmlString;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Pages\Actions\EditAction;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\CompletedResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CompletedResource\RelationManagers;
use App\Filament\Resources\Completeds\Pages\EditCompleted;
use App\Filament\Resources\Completeds\Pages\ListCompleteds;
use App\Filament\Resources\Completeds\Pages\CreateCompleted;
use Filament\Infolists\Components\TextEntry;

class CompletedResource extends Resource
{
    protected static ?string $model = Completed::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';
    protected static ?string $navigationLabel = 'Afgehandeld';
    protected static ?int $navigationSort = 23;
    public static ?string $pluralModelLabel = 'Afgehandeld';


    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([


                Section::make()
                    ->schema([
                        TextInput::make('Code')->unique(ignoreRecord: true)->disabled(
                            fn() => !auth()->user()->hasrole('super_admin')
                        ),




                        Select::make('Status')
                            ->options([
                                'Ingevoerd' => 'Ingevoerd',
                                'Aangeboden' => 'Aangeboden',
                                'Opgepakt' => 'Opgepakt',
                                'Klaar' => 'Klaar',
                                'Verzonden' => 'Verzonden',
                                'Ontvangen' => 'Ontvangen',
                                'Afgehandeld' => 'Afgehandeld',
                            ])->reactive()
                            ->default('Ingevoerd')
                            ->afterStateUpdated(function ($livewire, Clothing $record, callable $get) {

                                $record->setStatus($get('Status'));
                                $record->save();
                                $livewire->emitSelf('refreshForm');
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Completed $record) => $record === null),
                Section::make()
                    ->schema([
                        TextEntry::make('verzenden')
                            ->label('Verzenden naar:')
                            ->state(function (Completed $record) {
                                $agency = $record->agency;

                                $adres = $agency->Aanvrager . "<br>";
                                if ($agency->Bezorg_Naam) {
                                    $adres .= "T.a.v. " . $agency->Bezorg_Naam . "<br>";
                                }
                                $adres .= $agency->Bezorg_Adres . "<br>" . $agency->Bezorg_Postcode . "  " . $agency->Bezorg_Plaats;
                                return new HtmlString($adres);
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Completed $record) => $record === null),


                Section::make()
                    ->schema([
                        Select::make('agency_id')
                            ->label('Instantie')
                            ->options(Agency::all()->pluck('Aanvrager', 'id'))
                            ->searchable()
                            ->visibleOn('create')
                            ->required(),
                        Select::make('Geslacht')
                            ->options([
                                'Jongen' => 'Jongen',
                                'Meisje' => 'Meisje',
                                'Onbekend' => 'Onbekend',
                            ])->required(),
                        TextInput::make('Maat')
                            ->required(),
                        TextInput::make('Leeftijd'),
                        TextInput::make('Wens')->required(),
                        TextInput::make('Kleur'),
                    ])->columnSpan(['lg' => 1]),
                Section::make()
                    ->schema([
                        Textarea::make('Notities')->label('Notities/opmerkingen')
                            ->rows(4)
                            ->cols(20)
                            ->columnSpan('full'),


                    ])->columnSpan(['lg' => 1]),


                Tabs::make('Heading')->columns(2)->columnSpan('full')
                    ->tabs([
                        Tab::make('Maakster')
                            ->schema([
                                //todo: user userfilter maakster
                                Select::make('maakster_id')
                                    ->label('Maakster')
                                    ->options(User::role('Maakster')->pluck('name', 'id'))
                                    ->searchable(),
                                DatePicker::make('DatumMaakster')
                                    ->label('Datum opgepakt')
                                    ->displayFormat('d-m-Y')
                                    ->Native(false),
                                DatePicker::make('Kleding_Deadline')
                                    ->label('Deadline')
                                    ->displayFormat('d-m-Y')
                                    ->Native(false)
                                    ->reactive(),


                            ]),
                        Tab::make('Status')
                            ->schema([

                                TextEntry::make('Activiteit')
                                    ->label('Activiteit:')
                                    ->state(function (?Completed $record) {
                                        if ($record != null) {
                                            $string = str_Replace("\n", "<br>", $record->Activity);

                                            return new HtmlString($string);
                                        }
                                    }),
                            ]),
                    ]),

            ]);
    }



    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Status')->sortable()->searchable(),


                TextColumn::make('Code')->sortable()->searchable(),
                TextColumn::make('Geslacht')->sortable()->searchable(),
                TextColumn::make('Leeftijd')->sortable()->searchable(),

                TextColumn::make('Wens')->sortable()->searchable(),
                TextColumn::make('Maat')->sortable()->searchable()
                    ->sortable(),

                TextColumn::make('maakster.name')->sortable()->searchable(),
                TextColumn::make('Kleding_Deadline')
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw('ISNULL(Kleding_Deadline), Kleding_Deadline ' . $direction);
                    })
                    ->label('Deadline')
                    ->date($format = 'd-m-Y')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
                \Filament\Actions\EditAction::make(),
            ])
            ->toolbarActions([
                DeleteBulkAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompleteds::route('/'),
            'create' => CreateCompleted::route('/create'),
            'edit' => EditCompleted::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('Status', 'Afgehandeld');
    }
}
