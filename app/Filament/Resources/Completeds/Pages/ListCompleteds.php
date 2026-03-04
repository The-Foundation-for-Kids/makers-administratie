<?php

namespace App\Filament\Resources\Completeds\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Completeds\CompletedResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCompleteds extends ListRecords
{
    protected static string $resource = CompletedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
