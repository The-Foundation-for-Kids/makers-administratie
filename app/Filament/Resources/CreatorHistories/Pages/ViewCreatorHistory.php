<?php

namespace App\Filament\Resources\CreatorHistories\Pages;

use App\Filament\Resources\CreatorHistories\CreatorHistoryResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewCreatorHistory extends ViewRecord
{
    protected static string $resource = CreatorHistoryResource::class;
    protected static ?string $title = "Archief aanvraag";
}
