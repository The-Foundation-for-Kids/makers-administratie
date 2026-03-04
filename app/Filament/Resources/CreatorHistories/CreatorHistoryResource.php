<?php

namespace App\Filament\Resources\CreatorHistories;

use DateInterval;
use Filament\Forms;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\Action;
use Filament\Schemas\Schema;
use Maatwebsite\Excel\Excel;
use App\Models\CreatorHistory;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Infolists\Components\TextEntry;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use PhpOffice\PhpSpreadsheet\RichText\TextElement;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\CreatorHistoryResource\Pages;
use App\Filament\Resources\CreatorHistoryResource\RelationManagers;
use App\Filament\Resources\CreatorHistories\Pages\EditCreatorHistory;
use App\Filament\Resources\CreatorHistories\Pages\ViewCreatorHistory;
use App\Filament\Resources\CreatorHistories\Pages\CreateCreatorHistory;
use App\Filament\Resources\CreatorHistories\Pages\ListCreatorHistories;

class CreatorHistoryResource extends Resource
{
    protected static ?string $model = CreatorHistory::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Maakster';
    protected static ?string $navigationLabel = 'Archief verzonden';
    protected static ?string $slug = 'archief';
    public static ?string $pluralModelLabel = 'archief';
    protected static ?int $navigationSort = 13;


    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Code')->unique(ignoreRecord: true)->disabled(),
                TextInput::make('Status')->disabled(),

                Section::make()
                    ->schema([
                        TextEntry::make('Kleding')
                            ->label('Verzoek')
                            ->state(function (CreatorHistory $record) {
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
                    ->hidden(fn(?CreatorHistory $record) => $record === null),
                Section::make()
                    ->schema([
                        TextEntry::make('verzenden')
                            ->label('Verzenden naar')
                            ->state(function (CreatorHistory $record) {
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
                    ->hidden(fn(?CreatorHistory $record) => $record === null),
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

                TextColumn::make('Code')->searchable()->grow(false)
                    ->tooltip(fn(CreatorHistory $record): string => "Adres: {$record->adres()}"),

                TextColumn::make('Details')->searchable(['Geslacht', 'Wens', 'Maat'])->grow(false)
                    ->formatStateUsing(
                        function ($state, CreatorHistory $record) {
                            $returnvalue = $record->Geslacht;
                            $returnvalue .= " / " . $record->Wens;
                            $returnvalue .= " / " . $record->Maat;

                            return $returnvalue;
                        }
                    ),
                TextColumn::make('DatumMaakster')

                    ->label('Datum')
                    ->date($format = 'd-m-Y')
                    ->searchable()




            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('year')
                    ->label('Jaar')
                    ->options(fn(): array =>CreatorHistory::query()->withArchived()->where('maakster_id', '=', auth()->user()->id)->wherenotin('Status', ['Opgepakt', 'Klaar'])->select('year')->distinct()->pluck('year', 'year')->sort()->all())
                    ->default(now()->format('Y')),
            ])
            ->recordActions([])
            ->toolbarActions([

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
                            ->withFilename('ArchiefExport -' . date('Y-m-d'))
                    ])

            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->where('maakster_id', '=', auth()->user()->id)->wherenotin('Status', ['Opgepakt', 'Klaar'])->withArchived();
    }

    protected function getFormActions(): array
    {
        return [
            // ...parent::getFormActions(),
            Action::make('close')->action('saveAndClose'),
        ];
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
            'index'  => ListCreatorHistories::route('/'),
            'create' => CreateCreatorHistory::route('/create'),
            'view'   => ViewCreatorHistory::route('/{record}'),
            'edit'   => EditCreatorHistory::route('/{record}/edit'),
        ];
    }
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('maakster_id', '=', auth()->user()->id)->wherenotin('Status', ['Opgepakt', 'Klaar'])->withArchived()->count();
    }
}
