<?php

namespace App\Filament\Resources\Clothing;

use App\Filament\Forms\Components\YearPicker;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Actions\BulkAction;
use App\Filament\Resources\Clothing\Pages\ListClothing;
use App\Filament\Resources\Clothing\Pages\EditClothing;
use DateTime;
use DateInterval;
use Filament\Forms;
use App\Models\User;
use Filament\Tables;
use App\Models\Agency;
use App\Models\Clothing;
use Illuminate\Support\Str;
use Filament\Tables\Table;

use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;
use App\Filament\Resources\Closure;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Forms\Components\DateTimePicker;
use Filament\Tables\Actions\DeleteBulkAction;
use App\Filament\Resources\ClothingResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\ClothingResource\RelationManagers;
use App\Filament\Resources\Clothing\Pages\CreateClothing;
use App\Filament\Resources\Clothing\Widgets\StatsOverview;
use Filament\Infolists\Components\TextEntry;

class ClothingResource extends Resource
{
    protected static ?string $model = Clothing::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';
    protected static ?string $navigationLabel = 'Aanvragen';
    protected static ?int $navigationSort = 23;
    public static ?string $pluralModelLabel = 'Aanvragen';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public $data;

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
                                $livewire->dispatch('refreshForm');
                            }),

                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Clothing $record) => $record === null),
                Section::make()
                    ->schema([
                        TextEntry::make('verzenden')
                            ->label('Verzenden naar:')
                            ->state(function (Clothing $record) {
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
                    ->hidden(fn(?Clothing $record) => $record === null),


                Section::make()
                    ->schema([
                        Select::make('agency_id')
                            ->label('Instantie')
                            ->options(Agency::where('Status','Actief')->pluck('Aanvrager', 'id'))
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

                        TextInput::make('Leeftijd')
                            ->datalist([
                                '0 Jaar',
                                '1 Jaar',
                                '2 Jaar',
                                '3 Jaar',
                                '4 Jaar',
                                '5 Jaar',
                                '6 Jaar',
                                '7 Jaar',
                                '8 Jaar',
                                '9 Jaar',
                                '10 Jaar',
                                '11 Jaar',
                                '12 Jaar',
                            ])->required(),

                        TextInput::make('Wens')->required(),
                        TextInput::make('Kleuren'),
                        TextInput::make('houdtVan'),
                        Toggle::make('priority'),
                        TextInput::make('Kenmerk_Instantie'),

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
                                Select::make('maakster_id')
                                    ->label('Maakster')
                                    ->options(User::role('Maakster')->pluck('name', 'id'))
                                    ->searchable()
                                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                        if (!$get('DatumMaakster') && filled($state)) {

                                            $set('DatumMaakster', now());
                                            $set('Status', 'Opgepakt');
                                            if (str_contains(strtolower($get('Wens')), 'verjaardag')) {
                                                $set('Kleding_Deadline', now()->addDays(43));
                                            } elseif (str_contains(strtolower($get('Wens')), 'december')) {
                                                $set('Kleding_Deadline', now()->addDays(43));
                                            } else {
                                                $set('Kleding_Deadline', now()->addDays(29));
                                            }
                                        }
                                    })
                                    ->reactive(),
                                DatePicker::make('DatumMaakster')
                                    ->label('Datum opgepakt')
                                    ->displayFormat('d-m-Y')
                                    ->Native(false),
                                DatePicker::make('Kleding_Deadline')
                                    ->label('Deadline')
                                    ->displayFormat('d-m-Y')
                                    ->reactive()
                                    ->Native(false),
                            ]),
                        Tab::make('Status')
                            ->schema([

                                TextEntry::make('Activiteit')
                                    ->label('Activiteit:')
                                    ->state(function (?Clothing $record) {
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
                TextColumn::make('Status')->sortable()->searchable()->toggleable(),

                IconColumn::make('Notities')->toggleable()
                    ->sortable()
                    ->label('Notitie')
                    ->icons([
                        'heroicon-o-pencil',
                        '' => fn($state, $record): bool => $record->Notities === null,
                    ])->tooltip(fn(Clothing $record): string => $record->Notities ? $record->Notities : ""),


                TextColumn::make('Code')->sortable()->searchable()->toggleable(),
                TextColumn::make('Geslacht')->sortable()->searchable()->toggleable(),
                TextColumn::make('Leeftijd')->sortable()->searchable()->toggleable(),

                TextColumn::make('Wens')->sortable()->searchable()->toggleable(),
                TextColumn::make('Maat')->sortable()->searchable()->toggleable()
                    ->sortable(),
                TextColumn::make('maakster.name')->sortable()->searchable(),
                ToggleColumn::make('priority')->toggleable(),
                TextColumn::make('Kleding_Deadline')->toggleable()
                    ->sortable(query: function (Builder $query, string $direction): Builder {
                        return $query
                            ->orderByRaw('ISNULL(Kleding_Deadline), Kleding_Deadline ' . $direction);
                    })
                    ->label('Deadline')
                    ->date($format = 'd-m-Y')
                    ->searchable()
                    ->color(function (?Clothing $record) {
                        if ($record->Kleding_Deadline) {
                            $deadline = Carbon::parse($record->Kleding_Deadline);

                            $grens = Carbon::now()->add(new DateInterval('P7D'));
                            $nu = Carbon::now();

                            if ($deadline->lt($grens)) {
                                if ($deadline->lt($nu)) {
                                    return 'danger';
                                }
                                return 'primary';
                            }
                        }
                    }),
                TextColumn::make('updated_at')->sortable()->searchable()->toggleable(),
            ])->defaultSort('updated_at', 'desc')
            ->filters([
                Filter::make('Aandacht')
                    ->query(
                        fn(Builder $query): Builder => $query
                            ->where('Wens', '')->orWhereNull('Wens')
                            ->orWhere('Geslacht', '')->orWhereNull('Geslacht')
                            ->orWhere('Maat', '')->orWhereNull('Maat')

                    ),

                SelectFilter::make('Status')
                    ->options([
                        'Ingevoerd' => 'Ingevoerd',
                        'Aangeboden' => 'Aangeboden',
                        'Opgepakt' => 'Opgepakt',
                        'Klaar' => 'Klaar',
                        'Verzonden' => 'Verzonden',
                        'Ontvangen' => 'Ontvangen',
                    ])->multiple(),

            ])

            ->recordActions([])
            ->toolbarActions([
                BulkAction::make('Klaar => Verzonden')
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'][0])) {
                                if (in_array('Klaar', $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isStatus('Klaar')) {
                                $record->setStatus('Verzonden');
                                $record->save();
                            }
                        }
                    })

                    ->deselectRecordsAfterCompletion()

                    ->icon('heroicon-o-trash'),
                BulkAction::make('Verwijderen')
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'][0])) {
                                if (in_array('Ingevoerd', $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records) {

                        foreach ($records as $record) {
                            if ($record->isStatus('Ingevoerd')) {
                                $record->delete();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion()
                    ->color('danger')
                    ->icon('heroicon-o-trash'),
                BulkAction::make('Ingevoerd => Aangeboden')
                    ->visible(
                        function ($livewire) {

                            if (isset($livewire->tableFilters['Status']['values'][0])) {
                                if (in_array('Ingevoerd', $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isStatus('Ingevoerd')) {
                                $record->setStatus('Aangeboden');
                                $record->save();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Opgepakt => Ingevoerd')->hidden(true)
                    ->visible(
                        function ($livewire) {

                            if (isset($livewire->tableFilters['Status']['values'][0])) {
                                if (in_array('Opgepakt', $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isStatus('Opgepakt')) {
                                $record->setStatus('Ingevoerd');
                                $record->save();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Ingevoerd => Opgepakt+toekenning')
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'][0])) {
                                if (in_array('Ingevoerd', $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records, array $data) {
                        $mid = $data['maakster_id'];
                        foreach ($records as $record) {
                            if ($record->isStatus('Ingevoerd')) {
                                $record->setStatus('Opgepakt');
                                $record->maakster_id = $mid;
                                $record->save();
                            }
                        }
                    })
                    ->schema([
                        Select::make('maakster_id')
                            ->label('Maakster')
                            ->options(User::role('Maakster')->pluck('name', 'id'))

                            ->searchable()
                            ->required(),
                    ])
                    ->deselectRecordsAfterCompletion(),

                BulkAction::make('Aangeboden => Ingevoerd')->hidden(false)
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'])) {
                                if (in_array("Aangeboden",  $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isStatus('Aangeboden')) {
                                $record->setStatus('Ingevoerd');
                                $record->save();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Ontvangen => Afgehandeld')->hidden(false)
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'])) {
                                if (in_array("Ontvangen",  $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )

                    ->action(function (Collection $records) {
                        foreach ($records as $record) {
                            if ($record->isStatus('Ontvangen')) {
                                $record->setStatus('Afgehandeld');
                                $record->save();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Aangeboden op datum')
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'])) {
                                if (in_array("Ingevoerd",  $livewire->tableFilters['Status']['values']) || in_array("Aangeboden",  $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            if ($record->isStatus('Ingevoerd') || $record->setStatus('Aangeboden')) {

                                $record->Datum_Verlopen_Voortoekenning = $data['verloopdatum'];
                                $record->setStatus('Aangeboden');

                                $record->save();
                            }
                        }
                    })
                    ->schema([
                        DateTimePicker::make('verloopdatum')
                            ->label('Zichtbaar per')
                            ->displayFormat('d-m-Y H:i')
                            ->seconds(false)
                            ->native(false)
                            ->minDate(now()->toDateTimeLocalString(unitPrecision: 'minute'))
                            ->maxDate(now()->addDays(31)->toDateTimeLocalString(unitPrecision: 'minute'))
                            ->required(),
                    ]),
                BulkAction::make('Update deadline/notitie')
                    ->visible(
                        function ($livewire) {
                            if (isset($livewire->tableFilters['Status']['values'])) {
                                if (in_array("Ingevoerd",  $livewire->tableFilters['Status']['values']) || in_array("Aangeboden",  $livewire->tableFilters['Status']['values'])) {
                                    return true;
                                };
                            }
                            return false;
                        }
                    )
                    ->action(function (Collection $records, array $data): void {
                        foreach ($records as $record) {
                            if ($data['deadline']) {
                                $record->Kleding_Deadline = $data['deadline'];
                            }
                            if ($data['Notities']) {
                                $record->Notities = $data['Notities'];
                            }
                            $record->save();
                        }
                    })
                    ->schema([
                        Textarea::make('Notities')->label('Notities/opmerkingen')
                            ->rows(4)
                            ->cols(20)
                            ->columnSpan('full'),
                        DateTimePicker::make('deadline')
                            ->label('Deadline')
                            ->displayFormat('d-m-Y H:i')
                            ->Native(false),
                    ]),
            ]);
    }


    public static function getRelations(): array
    {
        return [
            //
        ];
    }
    public static function getWidgets(): array
    {
        return [
            StatsOverview::class
        ];
    }
    public static function getPages(): array
    {
        return [
            'index' => ListClothing::route('/'),
            'create' => CreateClothing::route('/create'),
            'edit' => EditClothing::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('Status', '!=', 'Afgehandeld');
    }
}
