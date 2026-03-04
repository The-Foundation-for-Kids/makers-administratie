<?php

namespace App\Filament\Resources\CreatorHistories\Pages;

use App\Models\Agency;
use Filament\Pages\Actions;
use Filament\Pages\Actions\Action;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use pxlrbt\FilamentExcel\Columns\Column;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Actions\CreateAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Konnco\FilamentImport\Actions\ImportField;
use Konnco\FilamentImport\Actions\ImportAction;
use App\Filament\Resources\CreatorHistories\CreatorHistoryResource;
use pxlrbt\FilamentExcel\Actions\Pages\ExportAction;


class ListCreatorHistories extends ListRecords
{
    protected static string $resource = CreatorHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
