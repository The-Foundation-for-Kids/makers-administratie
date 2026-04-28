<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Pages\Actions;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Users\UserResource;
use App\Filament\Resources\Users\Widgets\UserStats;

class ListUsers extends ListRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {


        return [
            Action::make('Maaksters')->url(fn(): string => route('filament.admin.resources.user.index', 'filters[Maaksters][isActive]=true))')),
            Action::make('Beheerders')->url(fn(): string => route('filament.admin.resources.user.index', 'filters[Beheerders][isActive]=true))')),
            Action::make('Overig')->url(fn(): string => route('filament.admin.resources.user.index', 'filters[Overig][isActive]=true))')),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            UserStats::class,
        ];
    }
}
