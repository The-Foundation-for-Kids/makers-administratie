<?php

namespace App\Filament\Resources\Creators;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Maatwebsite\Excel\Excel;
use App\Filament\Resources\Creators\Pages\ListCreators;
use App\Filament\Resources\Creators\Pages\CreateCreator;
use App\Filament\Resources\Creators\Pages\EditCreator;
use DateInterval;
use Filament\Forms;
use Filament\Tables;
use App\Models\Creator;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\HtmlString;

use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use App\Filament\Resources\CreatorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use App\Filament\Resources\CreatorResource\RelationManagers;
use Filament\Infolists\Components\TextEntry;

class CreatorResource extends Resource
{
    protected static ?string $model = Creator::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Maakster';
    protected static ?string $navigationLabel = 'Mijn aanvragen';
    protected static ?string $slug = 'maakster';
    public static ?string $pluralModelLabel = 'aanvragen';
    protected static ?int $navigationSort = 11;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Code')->unique(ignoreRecord: true)->disabled(),
                Select::make('Status')
                    ->options([

                        'Opgepakt' => 'Opgepakt',

                        'Klaar' => 'Klaar',
                    ])->reactive()
                    ->afterStateUpdated(function (Creator $record, callable $get) {

                        $record->setStatus($get('Status'));
                        $record->save();
                    }),

                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        TextEntry::make('Kleding')
                            ->label('Verzoek')
                            ->state(function (Creator $record) {
                                $kleding = "<table><tr><td>";
                                $kleding .= "<tr><td>Voor wie<td>: " . $record->Geslacht . "";
                                $kleding .= "<tr><td>Kledingwens&nbsp;<td>: " . $record->Wens . "";
                                $kleding .= "<tr><td>Kleur<td>: " . $record->Kleuren . "";
                                $kleding .= "<tr><td>Maat<td>: " . $record->Maat . "";
                                $kleding .= "<tr><td>Leeftijd<td>: " . $record->Leeftijd . "";
                                $kleding .= "<tr><td>Houdt van<td>: " . $record->houdtVan . "";
                                $kleding .= "<tr><td>Inleverdatum&nbsp;<td> : " . \Carbon\Carbon::parse($record->Kleding_Deadline)->format('d-m-Y') . "";

                                $kleding .= "</table>";
                                return new HtmlString($kleding);
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Creator $record) => $record === null),
                \Filament\Schemas\Components\Section::make()
                    ->schema([
                        TextEntry::make('verzenden')
                            ->label('Verzenden naar')
                            ->state(function (Creator $record) {
                                $agency = $record->agency;

                                $adres = $agency->Aanvrager . "<br>";
                                if ($agency->Bezorg_Naam) {
                                    $adres .= "T.a.v. " . $agency->Bezorg_Naam . "<br>";
                                }
                                $adres .= $agency->Bezorg_Adres . "<br>" . $agency->Bezorg_Postcode . "  " . $agency->Bezorg_Plaats . "<br><br>";
                                $adres .= "Gebruik voor het verzenden van je pakketje het mailadres <b>info@thefoundationforkids.com</b>";

                                return new HtmlString($adres);
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Creator $record) => $record === null),
                Textarea::make('Notities')->label('Notities/opmerkingen')
                    ->rows(4)
                    ->cols(20)
                    ->columnSpan('full')
                    ->disabled(),

            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Split::make([
                    TextColumn::make('Code')->searchable()->grow(false)
                        ->tooltip(fn(Creator $record): string => "Adres: {$record->adres()}"),

                    TextColumn::make('Maat')->searchable(['Geslacht', 'Wens', 'Maat'])->grow(false)
                        ->formatStateUsing(
                            function ($state, Creator $record) {
                                $returnvalue = $record->Geslacht;
                                $returnvalue .= " / " . $record->Wens;
                                $returnvalue .= " / " . $record->Maat;
                                return $returnvalue;
                            }
                        ),


                ]),



                Split::make([
                    Stack::make([
                        TextColumn::make('Leeftijd')->searchable(['Leeftijd', 'Kleuren', 'houdtVan'])->grow(false)
                            ->formatStateUsing(
                                function ($state, Creator $record) {
                                    $returnvalue = "";
                                    if ($record->Leeftijd) {

                                        $returnvalue = "Leeftijd : " . $record->Leeftijd;
                                    }
                                    if ($record->Kleuren) {
                                        $returnvalue .= " / " . $record->Kleuren;
                                    }
                                    if ($record->houdtVan) {
                                        $returnvalue .= " / " . $record->houdtVan;
                                    }
                                    return $returnvalue;
                                }
                            )->hidden(function ($record) {
                                if ($record != null) {

                                    if ($record->Leeftijd == '' && $record->Kleuren == '' && $record->houdtVan == '')
                                        return true;
                                }
                            }),
                        TextColumn::make('Notities')->hidden(function ($record) {
                            if ($record != null) {

                                if ($record->Notities == '')
                                    return true;
                            }
                        })->formatStateUsing(
                            function ($state, Creator $record) {
                                $returnvalue = "Let op : " . $record->Notities;


                                return $returnvalue;
                            }
                        )->color(function (Creator $record) {
                            if ($record->Notities != '') {
                                //return 'primary';
                                return 'danger';
                            }
                        }),
                    ])


                ]),


                TextColumn::make('Kleding_Deadline')
                    ->label('Deadline')
                    ->sortable()

                    ->searchable()
                    ->color(function (?Creator $record) {
                        if ($record->Kleding_Deadline) {
                            $deadline =  Carbon::parse($record->Kleding_Deadline);

                            $grens = Carbon::now()->add(new DateInterval('P7D'));
                            $nu = Carbon::now();

                            if ($deadline->lt($grens)) {
                                if ($deadline->lt($nu)) {
                                    return 'danger';
                                }
                                return 'primary';
                            }
                        }
                    })
                    ->formatStateUsing(
                        function ($state, Creator $record) {
                            $returnvalue = "Deadline : " . \Carbon\Carbon::parse($record->Kleding_Deadline)->format('d-m-Y');




                            return $returnvalue;
                        }
                    ),

            ])->defaultSort('Kleding_Deadline')
            ->filters([
                //
            ])
            ->recordActions([
                Action::make('Klaar')->button()
                    ->visible(function (Creator $record) {
                        return $record->Status == 'Opgepakt';
                    })
                    ->action(function ($record) {
                        $record->setStatus('Klaar');
                        $record->save();
                    }),
                Action::make('Verzonden')->button()
                    ->action(function ($record) {
                        $record->setStatus('Verzonden');
                        $record->save();
                    })
            ])
            ->toolbarActions([
                BulkAction::make('Klaar')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {

                            $record->setStatus('Klaar');
                            $record->save();
                        }
                    })->hidden(False)
                    ->deselectRecordsAfterCompletion(),
                BulkAction::make('Verzonden')
                    ->action(function (Collection $records) {
                        foreach ($records as $record) {

                            $record->setStatus('Verzonden');
                            $record->save();
                        }
                    })->hidden(False)
                    ->deselectRecordsAfterCompletion(),

                ExportBulkAction::make()->label('Opslaan')
                    ->exports([
                        ExcelExport::make()->withColumns([
                            Column::make('Code'),
                            Column::make('DatumMaakster')->heading('Datum'),
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCreators::route('/'),
            'create' => CreateCreator::route('/create'),
            'edit' => EditCreator::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('maakster_id', '=', auth()->user()->id)->wherein('Status', ['Opgepakt', 'Klaar']);
    }

    public static function getNavigationBadge(): ?string
    {

        return static::getModel()::where('maakster_id', '=', auth()->user()->id)->wherein('Status', ['Opgepakt', 'Klaar'])->count();
        $countroles = static::getModel()::where('Status', '=', 'Aangeboden')->count();
        if ($countroles == 0) {
            return null;
        } else {
            return $countroles;
        }
    }
}
