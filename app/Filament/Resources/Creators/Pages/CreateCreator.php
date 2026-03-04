<?php

namespace App\Filament\Resources\Creators\Pages;

use App\Filament\Resources\Creators\CreatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCreator extends CreateRecord
{
    protected static string $resource = CreatorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
