<?php

namespace App\Filament\Resources\Creators\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Creators\CreatorResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCreators extends ListRecords
{
    protected static string $resource = CreatorResource::class;
    protected static ?string $title = "Mijn aanvragen";
    protected ?string $subheading = 'Alle aanvragen die verstuurd zijn komen terug in het archief';
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
