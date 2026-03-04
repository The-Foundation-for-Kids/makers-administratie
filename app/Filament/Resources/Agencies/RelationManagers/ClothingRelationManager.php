<?php

namespace App\Filament\Resources\Agencies\RelationManagers;

use App\Models\Agency;
use App\Models\Clothing;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\EditAction;
use Filament\Forms;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Laravel\SerializableClosure\Serializers\Native;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class ClothingRelationManager extends RelationManager
{
    protected static string $relationship = 'clothing';

    protected static ?string $recordTitleAttribute = 'Code';

    public function form(Schema $schema): Schema
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
                        DateTimePicker::make('Datum_Verlopen_Voortoekenning')
                            ->visible()
                            ->label('Zichtbaar per')
                            ->native(false)
                            ->displayFormat('d-m-Y H:i')
                            ->seconds(false),
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

    public function table(Table $table): Table
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
                TextColumn::make('Maakster')->sortable()->searchable(),
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
            ->headerActions([
                CreateAction::make(),
            ])
            ->recordActions([
                EditAction::make()->label(''),
            ])
            ->modifyQueryUsing(fn(Builder $query) => $query->where('Status', '!=', 'Afgehandeld'))
            ->toolbarActions([
                ExportBulkAction::make()->label('Opslaan')
                    ->exports([
                        ExcelExport::make()->withColumns([
                            Column::make('Code'),
                            Column::make('Kenmerk_Instantie'),
                            Column::make('Wens'),
                            Column::make('Maat'),
                            Column::make('Geslacht'),
                            Column::make('Leeftijd'),
                            Column::make('Kleuren'),
                            Column::make('houdtVan'),


                        ])->withWriterType(Excel::XLSX)
                            ->withFilename('Overzicht -' . date('Y-m-d'))
                    ])
            ]);
    }
}
