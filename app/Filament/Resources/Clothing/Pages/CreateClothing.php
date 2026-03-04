<?php

namespace App\Filament\Resources\Clothing\Pages;

use App\Filament\Resources\Clothing\ClothingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClothing extends CreateRecord
{
    protected static string $resource = ClothingResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
