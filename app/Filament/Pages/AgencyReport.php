<?php

namespace App\Filament\Pages;

use App\Models\Agency;
use App\Models\AgencyPlanning;
use App\Models\CreatorHistory;
use Filament\Pages\Page;

use Filament\Tables\Columns\Summarizers\Average;
use Filament\Tables\Columns\Summarizers\Range;
use Filament\Tables\Columns\Summarizers\Sum;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\On;
use Maatwebsite\Excel\Excel;
use pxlrbt\FilamentExcel\Actions\ExportAction;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use pxlrbt\FilamentExcel\Columns\Column;
use pxlrbt\FilamentExcel\Exports\ExcelExport;

class AgencyReport extends Page implements HasTable
{

    use InteractsWithTable;


    protected string $model = Agency::class;

    protected static string | \UnitEnum | null $navigationGroup = 'Beheerders';
    protected static ?string $navigationLabel = 'Rapport';
    protected static ?string $navigationParentItem = 'Instanties';

    protected static ?int $navigationSort = 20;
    public static ?string $pluralModelLabel = 'Raport';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-clipboard-document-list';


    protected string $view = 'filament.pages.agency-report';

    #[On('refreshPage')]
    public function refreshPage(): void
    {
        rand(0, 100);
        //dd('test');
    }
    public function getHeaderActions(): array
    {
        return [

            ExportAction::make()->exports([
                ExcelExport::make()->fromTable(),

            ])
        ];
    }
    public function table(Table $table): Table
    {

        return $table
            ->query(
                Agency::query()
            )

            ->columns([
                TextColumn::make('Code')
                    ->sortable()
                    ->extraAttributes(function (?Agency $record) {
                        $bgColor = '#c1bf5c80';
                        if ($record['Code'] == "BP") {
                            $bgColor = '#5cbbc180'; // your logic to fetch the right color using the Enum here
                        }
                        return ['style' => "background-color: {$bgColor};border-radius: 10px;"];
                    }),
                TextColumn::make('Aanvrager')
                    ->extraAttributes([
                        'style' => 'max-width:260px'
                    ])
                    ->wrap(),
                TextColumn::make('clothing_count')->label('Total')
                    ->counts(function ($livewire) {
                        if ($livewire->tableFilters['clothing']['year']['value']) {
                            return [
                                'clothing' => fn(Builder $query) =>
                                $query->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                    ->withArchived()
                            ];
                        } else {
                            return [
                                'clothing' => fn(Builder $query) =>
                                $query->withArchived()
                            ];
                        }
                    })
                    ->summarize([
                        Sum::make(),
                        Range::make(),
                    ])

                    ->sortable(),

                TextColumn::make('monthjan_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthjan' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 1')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthjan' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 1')->withArchived()
                        ];
                    }
                })->summarize([
                    Sum::make()->label('Totaal'),
                ])
                    ->extraAttributes(function (?Agency $record) {
                        // dd($record);
                        $bgColor = '#c1bf5c80';
                        if ($record['Code'] == "BP") {
                            $bgColor = '#5cbbc180'; // your logic to fetch the right color using the Enum here
                        }
                        return ['style' => "background-color: {$bgColor};border-radius: 10px;"];
                    })->label('Jan')->sortable(),


                TextColumn::make('monthfeb_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthfeb' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 2')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthfeb' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 2')->withArchived()
                        ];
                    }
                })->label('Feb')->sortable(),
                TextColumn::make('monthmar_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthmar' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 3')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthmar' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 3')->withArchived()
                        ];
                    }
                })->label('Mar')->sortable(),
                TextColumn::make('monthapr_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthapr' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 4')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthapr' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 4')->withArchived()
                        ];
                    }
                })->label('Apr')->sortable(),

                TextColumn::make('monthmay_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthmay' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 5')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthmay' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 5')->withArchived()
                        ];
                    }
                })->label('May')->sortable(),
                TextColumn::make('monthjun_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthjun' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 6')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthjun' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 6')->withArchived()
                        ];
                    }
                })->label('Jun')->sortable(),
                TextColumn::make('monthjul_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthjul' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 7')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthjul' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 7')->withArchived()
                        ];
                    }
                })->label('Jul')->sortable(),
                TextColumn::make('monthaug_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthaug' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 8')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthaug' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 8')->withArchived()
                        ];
                    }
                })->label('Aug')->sortable(),
                TextColumn::make('monthsep_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthsep' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 9')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthsep' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 9')->withArchived()
                        ];
                    }
                })->label('Sep')->sortable(),
                TextColumn::make('monthokt_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthokt' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 10')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthokt' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 10')->withArchived()
                        ];
                    }
                })->label('Okt')->sortable(),
                TextColumn::make('monthnov_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthnov' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 11')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthnov' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 11')->withArchived()
                        ];
                    }
                })->label('Nov')->sortable(),
                TextColumn::make('monthdec_count')->counts(function ($livewire) {
                    if ($livewire->tableFilters['clothing']['year']['value']) {
                        return [
                            'monthdec' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 12')->where('year', $livewire->tableFilters['clothing']['year']['value'])
                                ->withArchived()
                        ];
                    } else {
                        return [
                            'monthdec' => fn(Builder $query) =>
                            $query->whereRaw('month(datummaakster) = 12')->withArchived()
                        ];
                    }
                })->label('Dec')->sortable(),






            ])
            ->toolbarActions([

            ])
            ->filters([
                SelectFilter::make('clothing.year')
                    ->label('Jaar')
                    ->options(fn(): array => CreatorHistory::query()->withArchived()->select('year')->distinct()->pluck('year', 'year')->sort()->all())

                    ->query(fn(Builder $query, $data) => $query
                        ->whereDoesntHave(
                            'clothing',
                            fn(Builder $query) => $query->where('year', $data['value'])->withArchived()
                        )
                        ->orwhereHas(
                            'clothing',
                            fn(Builder $query) => $query->where('year', $data['value'])->withArchived()
                        )),
            ]);
    }
}
