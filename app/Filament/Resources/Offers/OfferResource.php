<?php

namespace App\Filament\Resources\Offers;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use App\Filament\Resources\Offers\Pages\ListOffers;
use App\Filament\Resources\Offers\Pages\CreateOffer;
use App\Filament\Resources\Offers\Pages\EditOffer;
use Filament\Forms;
use Filament\Tables;
use App\Models\Offer;
use App\Models\Agency;
use Filament\Tables\Table;
use Illuminate\Support\Carbon;
use Filament\Resources\Resource;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Filament\Tables\Filters\Filter;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\Layout\Grid;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Eloquent\Collection;
use App\Filament\Resources\OfferResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\OfferResource\RelationManagers;
use Filament\Infolists\Components\TextEntry;
use Filament\Tables\Columns\IconColumn;

class OfferResource extends Resource
{
    protected static ?string $model = Offer::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Maakster';
    protected static ?string $navigationLabel = 'Beschikbaar';
    protected static ?string $slug = 'beschikbaar';
    public static ?string $pluralModelLabel = 'Beschikbaar';
    protected static ?int $navigationSort = 10;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('Code')->unique(ignoreRecord: true)->disabled(),
                Select::make('Status')
                    ->options([
                        'Aangeboden' => 'Aangeboden',
                        'Opgepakt' => 'Oppakken',
                    ])->reactive()
                    ->afterStateUpdated(function (Offer $record, callable $get) {

                        $record->setStatus($get('Status'));
                        if ($get('Status') == "Opgepakt") {
                            $record->maakster_id = auth()->user()->id;
                            $record->Maakster = auth()->user()->name;
                        }

                        $record->save();
                    }),

                Section::make()
                    ->schema([
                        TextEntry::make('Kleding')
                            ->label('Verzoek')
                            ->state(function (Offer $record) {
                                $kleding = "<table><tr><td>";
                                $kleding .= "<tr><td>Voor wie<td>: " . $record->Geslacht . "";
                                $kleding .= "<tr><td>Kledingwens&nbsp;<td>: " . $record->Wens . "";
                                $kleding .= "<tr><td>Kleur<td>: " . $record->Kleuren . "";
                                $kleding .= "<tr><td>Maat<td>: " . $record->Maat . "";
                                $kleding .= "<tr><td>Leeftijd<td>: " . $record->Leeftijd . "";
                                $kleding .= "<tr><td>Houdt van<td>: " . $record->houdtVan . "";
                                $kleding .= "</table>";
                                return new HtmlString($kleding);
                            }),
                    ])
                    ->columnSpan(['lg' => 1])
                    ->hidden(fn(?Offer $record) => $record === null),
                Section::make()
                    ->schema([
                        TextEntry::make('verzenden')
                            ->label('Verzenden naar')
                            ->state(function (Offer $record) {
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
                    ->hidden(fn(?Offer $record) => $record === null),
                Textarea::make('Notities')
                    ->rows(10)
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
                        ->tooltip(fn(Offer $record): string => "Adres: {$record->adres()}"),

                    TextColumn::make('Maat')->searchable(['Geslacht', 'Wens', 'Maat'])->grow(false)
                        ->formatStateUsing(
                            function ($state, Offer $record) {
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
                                function ($state, Offer $record) {
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
                            function ($state, Offer $record) {
                                $returnvalue = "Let op : " . $record->Notities;


                                return $returnvalue;
                            }
                        )->color(function (Offer $record) {
                            if ($record->Notities != '') {
                                //return 'primary';
                                return 'danger';
                            }
                        }),
                    ])


                ]),
            ])
            ->filters([
                Filter::make('alleen-bs')->label('Alleen BS')
                    ->query(fn(Builder $query): Builder => $query->whereHas('agency', function ($q) {
                        $q->where('Code', 'BS');
                    }))->hidden(),
                Filter::make('geen-bs')->label('Geen BS')
                    ->query(fn(Builder $query): Builder => $query->whereHas('agency', function ($q) {
                        $q->where('Code', '!=', 'BS');
                    }))->hidden(),
                SelectFilter::make('agency_id')->label('Instantie')
                    ->multiple()
                    ->options(fn() => Agency::whereRaw("id in (select distinct agency_id from clothing where status = 'aangeboden')")->orderBy('Code')->pluck('Code', 'id')->toArray())


            ])
            ->recordActions([
                Action::make('Oppakken')->button()
                    ->action(function ($livewire, $record) {
                        if ($record->isStatus('Aangeboden')) {
                            $record->setStatus('Opgepakt');
                            $record->maakster_id = auth()->user()->id;
                            $record->Maakster = auth()->user()->name;
                            $record->save();
                            Notification::make()
                                ->title('Aanvraag ' .  $record->Code . ' is toegewezen aan jou')
                                ->success()
                                ->send();
                            $livewire->dispatch('refreshForm');
                        }
                    })


            ])
            ->toolbarActions([

                BulkAction::make('Oppakken')
                    ->action(function (Collection $records) {

                        $toegewezen = array();
                        foreach ($records as $record) {
                            if ($record->isStatus('Aangeboden')) {

                                $record->setStatus('Opgepakt');
                                $record->maakster_id = auth()->user()->id;
                                $record->Maakster = auth()->user()->name;
                                $record->save();

                                array_push($toegewezen, $record->Code);
                            }
                        }
                        Notification::make()
                            ->title('De volgende ' . count($toegewezen) . ' aanvragen zijn aan jou toegewezen: ' .  implode(',', $toegewezen))
                            ->success()
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion(),
            ])
            ->defaultSort('priority', 'desc');
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
            'index' => ListOffers::route('/'),
            'create' => CreateOffer::route('/create'),
            'edit' => EditOffer::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        //dd(parent::getEloquentQuery());
        return parent::getEloquentQuery()
            ->where('Status', '=', 'Aangeboden')
            ->where(
                function ($q) {
                    $q->where('Datum_Verlopen_Voortoekenning', '<=', Carbon::now())
                        ->orwhere('Gebruiker_Voortoekenning', auth()->user()->id)
                        ->orwherenull('Datum_Verlopen_Voortoekenning');
                }

            );
    }

    public static function getNavigationBadge(): ?string
    {
        $countroles = static::getEloquentQuery()->count();

        if ($countroles == 0) {
            return null;
        } else {
            return $countroles;
        }
    }
}
