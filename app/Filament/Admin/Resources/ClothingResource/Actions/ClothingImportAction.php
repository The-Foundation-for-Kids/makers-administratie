<?php

namespace App\Filament\Admin\Resources\ClothingResource\Actions;



use App\Imports\ClothingsImport;
use App\Models\Agency;
use App\Models\User;
use DateInterval;
use DateTime;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Support\Facades\DB;


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
        $date_start = $date->format('Y');
        $date->add(new DateInterval('P30D')); // P1D means a period of 1 day
        $date_end = $date->format('Y');

        $numbers = range($date_start, $date_end);

        $this->label('Aanvragen importeren');
        $this->modalHeading('Aanvragen importeren');
        $this->modalSubmitActionLabel('Import File');
        $this->successNotificationTitle('Importeren gelukt');
        $this->schema([
            Tabs::make('Heading')->columns(1)->columnSpan('full')
                ->tabs([
                    Tab::make('Import')
                        ->schema([
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
                        ]),
                    Tab::make('special')
                        ->schema([
                            Select::make('maakster_id')->label('Maakster')
                                ->options(User::role('maakster')->pluck('name', 'id'))
                                ->searchable(),
                            Select::make('Status')
                                ->options([
                                    'Ingevoerd' => 'Ingevoerd',
                                    'Aangeboden' => 'Aangeboden',
                                    'Opgepakt' => 'Opgepakt',
                                    'Klaar' => 'Klaar',
                                    'Verzonden' => 'Verzonden',
                                    'Ontvangen' => 'Ontvangen',
                                    'Afgehandeld' => 'Afgehandeld',
                                ])->reactive()
                                ->default('Ingevoerd'),

                        ])
                ])
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
