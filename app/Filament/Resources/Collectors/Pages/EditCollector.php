<?php

namespace App\Filament\Resources\Collectors\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Collectors\CollectorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCollector extends EditRecord
{
    protected static string $resource = CollectorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
