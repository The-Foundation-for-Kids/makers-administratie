<?php

namespace Tests\Unit;

use App\Imports\ClothingsImport;
use App\Models\Agency;
use App\Models\Clothing;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Unit tests for the ClothingsImport Excel import: row mapping and empty-row skipping.
 */
class ClothingsImportTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A spreadsheet row must be mapped onto a new Clothing model using the lowercase
     * heading-row keys, while agency, year, maker and status come from the import form.
     */
    public function test_model_maps_spreadsheet_row_to_clothing(): void
    {
        $agency = Agency::factory()->create();
        $makerId = 7;
        $import = new ClothingsImport([
            'agency_id' => $agency->id,
            'Jaartal' => '2025',
            'maakster_id' => $makerId,
            'Status' => 'Aangeboden',
        ]);

        $row = [
            'leeftijdlengte' => '5',
            'jongenmeisje' => 'jongen',
            'maat' => '110',
            'wens' => 'Broek en trui',
            'houdt_van' => 'Voetbal',
            'kleuren' => 'Blauw',
            'notitie' => 'Graag snel',
            'info_instantie' => 'Kenmerk X',
            'deadline' => '2025-12-01',
        ];

        $model = $import->model($row);

        $this->assertInstanceOf(Clothing::class, $model);
        $this->assertEquals($agency->id, $model->agency_id);
        $this->assertEquals('2025', $model->year);
        $this->assertEquals($makerId, $model->maakster_id);
        $this->assertEquals('Aangeboden', $model->Status);
        $this->assertEquals('5', $model->Leeftijd);
        $this->assertEquals('jongen', $model->Geslacht);
        $this->assertEquals('110', $model->Maat);
        $this->assertEquals('Broek en trui', $model->Wens);
        $this->assertEquals('Voetbal', $model->houdtVan);
        $this->assertEquals('Blauw', $model->Kleuren);
        $this->assertEquals('Graag snel', $model->Notities);
        $this->assertEquals('Kenmerk X', $model->Kenmerk_Instantie);
        $this->assertEquals('2025-12-01', $model->Kleding_Deadline);
    }

    /**
     * A completely empty spreadsheet row must be skipped (model() returns null) so the
     * import does not create empty clothing requests.
     */
    public function test_model_returns_null_for_empty_row(): void
    {
        $import = new ClothingsImport([
            'agency_id' => Agency::factory()->create()->id,
            'Jaartal' => '2025',
            'maakster_id' => null,
            'Status' => 'Ingevoerd',
        ]);

        $emptyRow = [
            'leeftijdlengte' => null,
            'jongenmeisje' => null,
            'maat' => null,
            'wens' => null,
            'houdt_van' => null,
            'kleuren' => null,
            'notitie' => null,
            'info_instantie' => null,
            'deadline' => null,
        ];

        $this->assertNull($import->model($emptyRow));
    }
}
