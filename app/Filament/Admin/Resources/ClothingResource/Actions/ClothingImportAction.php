<?php

namespace App\Filament\Admin\Resources\ClothingResource\Actions;



use DateTime;
use DateInterval;
use App\Models\Agency;
use Filament\Actions\Action;
use App\Imports\ClothingsImport;
use Illuminate\Support\Facades\DB;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\FileUpload;


class ClothingImportAction extends Action
{
    public static function getDefaultName(): ?string
    {
        return 'import';
    }

    protected function setUp(): void
    {
        parent::setUp();
        $date = new DateTime(now());
        $date_start =$date->format('Y');
        $date->add(new DateInterval('P30D')); // P1D means a period of 1 day
        $date_end = $date->format('Y');

        $numbers = range($date_start, $date_end);

        $this->label('Aanvragen importeren');
        $this->modalHeading('Aanvragen importeren');
        $this->modalSubmitActionLabel('Import File');
        $this->successNotificationTitle('Importeren gelukt');
        $this->schema([
            Select::make('agency_id')->label('Instantie')->required()
                ->options(Agency::all()->where('Status', 'Actief')->pluck('Aanvrager', 'id'))
                ->searchable(),
            Select::make('Jaartal')
                ->options(array_combine($numbers, $numbers))
                ->default($date_start)
                ->required(),
            FileUpload::make('filename')
                ->label('Bestand')
                ->acceptedFileTypes(['xls', 'xlsx', 'csv', 'text/csv', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                ->storeFiles(false)
                ->visibility('private')
                ->preserveFilenames()
                ->required(),
        ]);
        // dd($this);
        $this->action(function (): void {
            $file = $this->getData()['filename'];

            $import = new ClothingsImport($this->getData());
            $import->import($file);

            $failures = $import->failures();
            //dd($import);
            foreach ($failures as $failure) {
                // TODO: display which records failed to import
            }
            // TODO: display the errors in failureNotification() or maybe a modal

            $this->sendSuccessNotification();
            $this->success();
        });
    }
}
