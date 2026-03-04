<?php

namespace App\Filament\Resources\Creators\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Creators\CreatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreator extends EditRecord
{
    protected static string $resource = CreatorResource::class;
    protected static ?string $title = "Details aanvraag";

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
