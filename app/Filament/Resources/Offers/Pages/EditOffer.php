<?php

namespace App\Filament\Resources\Offers\Pages;

use Filament\Actions\DeleteAction;
use App\Filament\Resources\Offers\OfferResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;
use Filament\Notifications\Notification;
use Filament\Support\Components\Attributes\Unexposed;
use Illuminate\Database\Eloquent\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;

class EditOffer extends EditRecord
{
    protected static string $resource = OfferResource::class;
    protected static ?string $title = "Details aanvraag";
    protected $listeners = ['refreshForm' => 'refreshForm'];

    /**
     * De aanvraag staat niet meer onder Beschikbaar, omdat iemand anders hem
     * intussen heeft opgepakt.
     */
    protected bool $nietMeerBeschikbaar = false;

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

    /**
     * Filament zoekt de aanvraag bij elk verzoek opnieuw op binnen Beschikbaar en
     * geeft een 404 als hij daar niet meer staat. Ga in dat geval verder met de
     * aanvraag zoals hij geladen was: oppakken() controleert zelf de status en
     * meldt dan dat iemand anders hem heeft opgepakt; opslaan wordt geblokkeerd.
     */
    #[Unexposed]
    public function resolveScopedModelProperties(): void
    {
        try {
            parent::resolveScopedModelProperties();
        } catch (HttpException $exception) {
            if ($exception->getStatusCode() !== 404 || ! ($this->record instanceof Model)) {
                throw $exception;
            }

            $this->nietMeerBeschikbaar = true;
        }
    }

    public function save(bool $shouldRedirect = true, bool $shouldSendSavedNotification = true): void
    {
        if ($this->nietMeerBeschikbaar) {
            Notification::make()
                ->title('Deze aanvraag is net door iemand anders opgepakt')
                ->warning()
                ->send();

            $this->redirect($this->getResource()::getUrl('index'));

            return;
        }

        parent::save($shouldRedirect, $shouldSendSavedNotification);
    }
}
