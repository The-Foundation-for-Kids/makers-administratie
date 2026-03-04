<?php

namespace App\Filament\Resources\Completeds\Pages;

use App\Filament\Resources\Completeds\CompletedResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateCompleted extends CreateRecord
{
    protected static string $resource = CompletedResource::class;
}
