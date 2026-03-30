<?php

namespace App\Filament\Resources\Clothing\Pages;

use App\Filament\Admin\Resources\ClothingResource\Actions\ClothingImportAction;
use App\Filament\Admin\Resources\ClothingResource\Actions\ClothingImportSpecialAction;
use App\Filament\Resources\Clothing\ClothingResource;
use App\Filament\Resources\Clothing\Widgets\StatsOverview;
use App\Models\Agency;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\ImportAction;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Tables\Filters\Filter;
use Illuminate\Support\Str;


class ListClothing extends ListRecords
{
    protected static string $resource = ClothingResource::class;

    protected function getHeaderActions(): array
    {

        return [
            Action::make('Ingevoerd')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Ingevoerd')),
            Action::make('Aangeboden')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Aangeboden')),
            Action::make('Opgepakt')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Opgepakt')),
            Action::make('Open')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Klaar&filters[Status][values][1]=Aangeboden&filters[Status][values][2]=Opgepakt')),
            Action::make('Klaar')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Klaar')),
            Action::make('Ontvangen')->url(fn(): string => route('filament.admin.resources.clothing.index', 'filters[Status][values][0]=Ontvangen')),
            CreateAction::make()->label('Aanvraag opvoeren'),

            ClothingImportAction::make()
                ->color('primary'),
 
        ];
    }
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
        ];
    }
}
