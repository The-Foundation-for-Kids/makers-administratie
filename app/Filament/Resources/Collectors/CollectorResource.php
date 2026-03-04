<?php

namespace App\Filament\Resources\Collectors;


use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Maatwebsite\Excel\Excel;
use App\Filament\Resources\Collectors\Pages\ListCollectors;
use App\Filament\Resources\Collectors\Pages\CreateCollector;
use Filament\Forms;

use Filament\Tables;

use App\Models\Agency;
use App\Models\Collector;

use Illuminate\Support\Str;
use Filament\Tables\Table;
use Filament\Facades\Filament;
use Filament\Resources\Resource;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Filament\Tables\Columns\TextColumn;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Spatie\SimpleExcel\SimpleExcelWriter;
use Illuminate\Database\Eloquent\Collection;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use AlperenErsoy\FilamentExport\FilamentExport;
use App\Filament\Resources\CollectorResource\Pages;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use pxlrbt\FilamentExcel\Actions\ExportBulkAction;
use App\Filament\Resources\CollectorResource\RelationManagers;
use AlperenErsoy\FilamentExport\Actions\FilamentExportBulkAction;

class CollectorResource extends Resource
{
    protected static ?string $model = Collector::class;
    protected static string | \UnitEnum | null $navigationGroup = 'Inzamelpunt';
    protected static ?string $navigationLabel = 'Inzamelpunt';
    public static ?string $pluralModelLabel = 'Inzamelpunt';
    protected static ?string $slug = 'Inzamelpunt';
    protected static ?int $navigationSort = 15;
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(\Filament\Schemas\Schema $schema): \Filament\Schemas\Schema
    {
        return $schema
            ->components([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('Status')->sortable()->searchable(),
                TextColumn::make('Code')->sortable()->searchable(),
                TextColumn::make('Geslacht')->sortable()->searchable(),
                TextColumn::make('Wens')->sortable()->searchable(),
                TextColumn::make('Maat')->sortable()->searchable(),
                TextColumn::make('maakster.name')->sortable()->searchable(),
                TextColumn::make('Kenmerk_Instantie')->sortable()->searchable(),


            ])
            ->filters([
                SelectFilter::make('Status')
                    ->options([
                        'Klaar' => 'Klaar',
                        'Aangeboden' => 'Aangeboden',
                        'Opgepakt' => 'Opgepakt',
                        'Verzonden' => 'Verzonden',
                    ])->default('Verzonden'),
            ])
            ->recordActions([
                Action::make('Ontvangen')->button()
                    ->action(function ($record) {
                        if ($record->isStatus('Verzonden') || $record->isStatus('Klaar')) {
                            $record->setStatus('Ontvangen');
                            $record->save();
                        }
                    })
                    ->visible(fn($record) => $record->isStatus('Verzonden') || $record->isStatus('Klaar'))

            ])
            ->toolbarActions([

                BulkAction::make('Verzonden/Klaar->Ontvangen')
                    ->action(function (Collection $records) {
                        /**
                         * @var object $record
                         */
                        foreach ($records as $record) {
                            if ($record->isStatus('Verzonden') || $record->isStatus('Klaar')) {
                                $record->setStatus('Ontvangen');
                                $record->save();
                            }
                        }
                    })
                    ->deselectRecordsAfterCompletion(),

                ExportBulkAction::make()->exports([
                    ExcelExport::make()->withColumns([
                        Column::make('Code'),
                        Column::make('Kenmerk_Instantie'),

                    ])->withWriterType(Excel::XLSX)
                        ->withFilename('Instantietabel -' . date('Y-m-d'))
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
            'index' => ListCollectors::route('/'),
            'create' => CreateCollector::route('/create'),

        ];
    }

    public static function getEloquentQuery(): Builder
    {
        //$users = User::where('vip', true)->get();
        $agency = Agency::where('verzamel_user_id', '=', auth()->user()->id)->get();
        if (count($agency) == 0) {
            return parent::getEloquentQuery()->where('id', '=', '0');
        } else {
            return parent::getEloquentQuery()->whereBelongsTo($agency)->wherein('Status', ['Aangeboden', 'Opgepakt', 'Klaar', 'Verzonden']);
        }
    }
}
