<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use BezhanSalleh\FilamentShield\Traits\HasPageShield;

class InformatieMaaksters extends Page
{
    use HasPageShield;
    protected static string | \UnitEnum | null $navigationGroup = 'Maakster';
    protected static ?string $navigationLabel = 'Handleiding maakster';
    protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-document-text';
    protected static ?string $title = 'Informatie voor jou als maakster';

    protected string $view = 'filament.pages.informatie-maaksters';
}
