<?php

namespace app\Filament\Pages;

use Illuminate\Support\HtmlString;
use Spatie\Permission\Models\Role;
use Filament\Pages\Dashboard as BasePage;
use Illuminate\Contracts\Support\Htmlable;

class Dashboard extends BasePage
{

    protected static ?string $navigationLabel = 'Informatie';
    //protected static ?string $title = 'Informatie';


    public function getTitle(): string
    {
        return "Welkom bij The Foundation for Kids - aanvraagadministratie";
    }
    public function getSubheading(): string | Htmlable | null
    {

        if (! auth()->user()->hasAnyRole(Role::all())) {
            return new HtmlString('Wil je graag kleding voor ons maken?<br><br>Neem contact op met de een van de beheerders via facebook om toegelaten te worden tot deze site.');
        }

        return new HtmlString('Wil je graag kleding voor ons maken? Neem snel een kijkje welke aanvragen er openstaan.<br><br>

        Als je voor het eerst hier bent, adviseren wij je om eerst even te kijken bij de \'handleiding maakster\'  in de linkerbalk op deze pagina. Hier vind je alle informatie over hoe je aanvragen op kunt pakken.
        ');
    }
}
