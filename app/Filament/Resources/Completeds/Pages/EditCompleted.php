<?php

namespace App\Filament\Resources\Completeds\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Completeds\CompletedResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCompleted extends EditRecord
{
    protected static string $resource = CompletedResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
