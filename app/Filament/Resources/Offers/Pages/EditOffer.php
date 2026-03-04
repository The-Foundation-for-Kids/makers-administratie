<?php

namespace App\Filament\Resources\Offers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Offers\OfferResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;
    protected static ?string $title = "Details aanvraag";
    protected $listeners = ['refreshForm' => 'refreshForm'];

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
    public function refreshForm()
    {
        $this->fillForm();
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
