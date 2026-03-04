<?php

namespace App\Filament\Resources\Clothing\Pages;

use App\Filament\Resources\Clothing\ClothingResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use app\Models\Clothing;

class EditClothing extends EditRecord
{
    protected static string $resource = ClothingResource::class;
    protected static ?string $title = "Details aanvraag";

    protected $listeners = ['refreshForm' => 'refreshForm'];

    public Clothing $clothing;

    public function refreshForm()
    {
        $this->fillForm();
    }

    protected function getHeaderActions(): array
    {
        if (auth()->user()->isAdmin() || auth()->user()->isBeheerder()) {
            return [
                //Actions\DeleteAction::make(),
            ];
        } else {
            return [];
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
