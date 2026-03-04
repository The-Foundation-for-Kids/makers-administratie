<?php

namespace App\Filament\Resources\CreatorHistories\Pages;

use App\Filament\Resources\CreatorHistories\CreatorHistoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCreatorHistory extends EditRecord
{
    protected static string $resource = CreatorHistoryResource::class;

    protected function getHeaderActions(): array
    {
        return [];
    }
}
