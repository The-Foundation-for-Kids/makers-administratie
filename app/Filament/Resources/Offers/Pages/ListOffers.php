<?php

namespace App\Filament\Resources\Offers\Pages;

use Filament\Actions\CreateAction;
use Closure;
use App\Models\Offer;
use Filament\Pages\Actions;
use App\Filament\Resources\Offers\OfferResource;
use Filament\Resources\Pages\ListRecords;

class ListOffers extends ListRecords
{
    protected static string $resource = OfferResource::class;
    protected static ?string $title = "Beschikbare kleding om op te pakken";

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
    protected function getTableRecordClassUsing(): ?Closure
    {
        //Todo: hoe wordt dit gebruikt?
        dd('test');
        return function (Offer $record) {
            return match ($record->Geslacht) {
                'Jongen' => 'opacity-30',
                'reviewing' => [
                    'border-l-solid',
                    'border-l-2',
                    'border-l-orange-600',
                    'dark:border-l-orange-300' => config('filament.dark_mode'),
                    'opacity-30',
                ],
                'published' => 'border-0 border-l-solid border-l-2 border-l-orange-400',
                default => null,
            };
            return null;
        };
    }
}
