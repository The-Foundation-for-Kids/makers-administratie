<?php

namespace App\Filament\Resources\Collectors\Pages;

use App\Filament\Resources\Collectors\CollectorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCollector extends CreateRecord
{
    protected static string $resource = CollectorResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
