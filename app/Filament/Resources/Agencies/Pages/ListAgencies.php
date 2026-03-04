<?php

namespace App\Filament\Resources\Agencies\Pages;

use Filament\Actions\CreateAction;
use App\Filament\Resources\Agencies\AgencyResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Konnco\FilamentImport\Actions\ImportAction;
use Konnco\FilamentImport\Actions\ImportField;
use Filament\Forms\Components\TextInput;

class ListAgencies extends ListRecords
{
    protected static string $resource = AgencyResource::class;


    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Instantie opvoeren'),
            /*            ImportAction::make()
            ->fields([
                TextInput::make('Status'),

                ImportField::make('Code')
                    ->label('Code'),
                // ImportField::make('Status')
                //     ->label('Status'),
                ImportField::make('Aanvrager')
                    ->label('Aanvrager'),
                ImportField::make('Adres')
                    ->label('Adres'),
                ImportField::make('Postcode')
                    ->label('Postcode'),
                ImportField::make('Vestigingsplaats')
                    ->label('Vestigingsplaats'),
                ImportField::make('Naam_Contactpersoon')
                    ->label('Naam_Contactpersoon'),
                ImportField::make('Emailadres_Contactpersoon')
                    ->label('Emailadres_Contactpersoon'),
                ImportField::make('Bezorg_Naam')
                    ->label('Bezorg_Naam'),
                ImportField::make('Bezorg_Email')
                    ->label('Bezorg_Email'),
                ImportField::make('Bezorg_Adres')
                    ->label('Bezorg_Adres'),
                ImportField::make('Bezorg_Postcode')
                    ->label('Bezorg_Postcode'),
                ImportField::make('Bezorg_Plaats')
                    ->label('Bezorg_Plaats'),
                ImportField::make('Overige_Mail')
                    ->label('Overige_Mail'),
                ImportField::make('KVK-nummer')
                    ->label('KVK-nummer'),
                ImportField::make('Partner_2022')
                    ->label('Partner_2022'),
                ImportField::make('Extra_info')
                    ->label('Extra_info'),
                ImportField::make('Bezoek')
                    ->label('Bezoek'),


                ])

*/

        ];
    }
}
