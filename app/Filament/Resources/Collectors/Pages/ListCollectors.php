<?php

namespace App\Filament\Resources\Collectors\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Collectors\CollectorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCollectors extends ListRecords
{
    protected static string $resource = CollectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
