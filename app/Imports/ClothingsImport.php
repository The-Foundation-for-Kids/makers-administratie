<?php

namespace App\Imports;


use App\Models\Clothing;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\WithUpserts;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;


class ClothingsImport implements ToModel, WithHeadingRow, WithValidation, SkipsOnFailure

{
    use Importable;
    use SkipsFailures;

    protected $form;
    public function __construct($form)
    {
        $this->form = $form;
    }

    public function model(array $row): ?Clothing
    {

        if (!array_filter($row)) {
            return null;
        }
        $agency = $this->form['agency_id'];
        $year = $this->form['Jaartal'];

        //  map model fields to spreadsheet rows by column name

        $tt = new Clothing([
            'agency_id' => $agency,
            'year' => $year,
            'Leeftijd' => $row['leeftijdlengte'],
            'Geslacht' => $row['jongenmeisje'],
            'Maat' => $row['maat'],
            'Wens' => $row['wens'],
            'houdtVan' => $row['houdt_van'],
            'Kleuren' => $row['kleuren'],
            'Notities' => $row['notitie'],
            'Kenmerk_Instantie' => $row['info_instantie'],
            'Kleding_Deadline' => $row['deadline'],

        ]);
        return $tt;
    }

    /*

ImportField::make('Kenmerk_Instantie'),
ImportField::make('Notities'),
ImportField::make(name: 'Priority'),
ImportField::make('Kleding_Deadline'),
*/


    // rows must contain an email address, not blank
    public function rules(): array
    {
        return [
            //  '*.Geslacht' => ['required', 'Geslacht'],
        ];
    }

    // Rows with matching email already in db will be ignored.
    public function uniqueBy(): string|array
    {
        return 'email';
    }
}
