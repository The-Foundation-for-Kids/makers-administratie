<?php

namespace App\Models;

use DateTime;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;
use Spatie\Activitylog\LogOptions;
use SebastianBergmann\Type\NullType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use LaravelArchivable\Archivable;
use PhpOffice\PhpSpreadsheet\Calculation\Logical\Boolean;

class Clothing extends Model
{
    use HasFactory;
    use LogsActivity;
    use Archivable;
    protected $table = 'clothing';
    protected $dates = ['Kleding_Deadline'];
    protected $fillable = [
        'Status',
        'year',
        'Code',
        'agency_id',
        'Leeftijd',
        'Geslacht',
        'Maat',
        'Wens',
        'houdtVan',
        'Kleuren',
        'DatumPlaatsing',
        'Maakster',
        'maakster_id',
        'DatumMaakster',
        'DatumKlaar',
        'DatumOntvangen',
        'DatumContact1',
        'WieContact1',
        'DatumContact2',
        'WieContact2',
        'Datum_Ingevoerd',
        'Datum_Aangeboden',
        'Datum_Opgepakt',
        'Datum_Klaar',
        'Datum_Ontvangen',
        'Datum_Afgehandeld',
        'Gebruiker_Ingevoerd',
        'Gebruiker_Aangeboden',
        'Gebruiker_Opgepakt',
        'Gebruiker_Klaar',
        'Gebruiker_Ontvangen',
        'Gebruiker_Afgehandeld',
        'Kleding_Deadline',
        'Notities',
        'TrackAndTraceCode',
        'Kenmerk_Instantie',
        'Activity',
        'Gebruiker_Voortoekenning',
        'Datum_Verlopen_Voortoekenning',
        'priority',
        'year'

    ];


    public function isIngediend(): bool
    {
        if ($this->Status == 'Ingediend') {
            return true;
        } else {
            return false;
        }
    }
    public function isStatus(string $status): bool
    {
        if ($this->Status == $status) {
            return true;
        } else {
            return false;
        }
    }

    public function isAfterCurrentStatus(string $status): bool
    {
        $after = false;
        foreach ($this->validStatus() as $status_loop) {
            if ($this->Status == $status_loop) {
                $after = true;
            }
            if ($status == $status_loop) {
                return $after;
            }
        }
        return $after;
    }


    public static function validStatus(): array
    {
        return array(
            'Ingevoerd',
            'Aangeboden',
            'Opgepakt',
            'Klaar',
            'Verzonden',
            'Ontvangen',
            'Afgehandeld',
        );
    }

    public function setStatus(string $status, string $prev_status = null): bool
    {
        if ($prev_status != null) {
            if ($prev_status != $this->Status) {
                return false;
            }
        }

        if (in_array($status, $this->validstatus(), true)) {
            $clear = false;
            $this->Status = $status;
            if ($status == 'Opgepakt') {
                $this->DatumMaakster = Carbon::now();
                if (!$this->Kleding_Deadline) {
                    if (str_contains(strtolower($this->Wens), 'verjaardag')) {
                        $this->Kleding_Deadline = now()->addDays(43);
                    } elseif (str_contains(strtolower($this->Wens), 'december')) {
                        $this->Kleding_Deadline = now()->addDays(43);
                    } else {
                        $this->Kleding_Deadline =  now()->addDays(29);
                    }
                }
            }
            if (in_array($status, ['Ingevoerd', 'Aangeboden'], true)) {

                $this->Kleding_Deadline = null;
                $this->maakster_id = null;
                $this->Maakster = null;
                $this->DatumMaakster = null;
            }
            //$this->Activity = $this->Activity . Carbon::now(). " -> " . $this->Status . " door " . auth()->user()->name . "\n";
            return true;
        }
        return true;
    }
    public function adres(): string
    {


        $adres = $this->agency->Aanvrager . ", ";
        if ($this->agency->Bezorg_Naam) {
            $adres .= "T.a.v. " . $this->agency->Bezorg_Naam . ", ";
        }
        $adres .= $this->agency->Bezorg_Adres . ", " . $this->agency->Bezorg_Postcode . "  " . $this->agency->Bezorg_Plaats;
        return new HtmlString($adres);
    }

    public function agency(): BelongsTo
    {

        return $this->belongsTo(Agency::class);
    }
    public function maakster(): BelongsTo
    {

        return $this->belongsTo(User::class);
    }
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logFillable()->logOnlyDirty();
    }

    public  function ExcelToPHPObject($dateValue = 0)
    {
        $dateTime = self::ExcelToPHP($dateValue);
        $days = floor($dateTime / 86400);
        $time = round((($dateTime / 86400) - $days) * 86400);
        $hours = round($time / 3600);
        $minutes = round($time / 60) - ($hours * 60);
        $seconds = round($time) - ($hours * 3600) - ($minutes * 60);

        $dateObj = date_create('1-Jan-1970+' . $days . ' days');
        $dateObj->setTime($hours, $minutes, $seconds);

        return $dateObj;
    }    //    function ExcelToPHPObject()

    public static function boot()
    {
        parent::boot();
        self::creating(function (Clothing $item) {

            $code = $item->agency->Code;
            $year = substr($item->year, -2);
            if ($year == "") {
                $year  =  substr(date("Y"), -2);
            }
            $filter = $code . $year . "%";
            $prev = Clothing::where('code', 'like', $filter)->latest('id')->first();
            if ($prev) {
                $prev_code = $prev->Code;
                $prev_number = str_replace($code, "", $prev_code);
                $new_code = $code . strval((int) $prev_number + 1);
            } else {
                $new_code = $code . strval((int) $year * 10000 + 1);
            }
            $item->code = $new_code;
            $item->Status = 'Ingevoerd';
            $geslacht = $item->Geslacht;

            if (stripos($geslacht, "jong") === 0) {
                error_log("jongen");
                $item->Geslacht = "Jongen";
            } elseif (stripos($geslacht, "meis") === 0) {
                error_log("meis");
                $item->Geslacht = "Meisje";
            }

            if (is_numeric($item->Leeftijd)) {

                $UNIX_DATE = ($item->Leeftijd - 25569) * 86400;

                $item->Leeftijd = gmdate("d-m-Y", $UNIX_DATE);
            }
            //$item->Datum_Ingevoerd = Carbon::now();
            $item->Gebruiker_Ingevoerd = auth()->user()->id;
            $item->Activity = $item->Activity . Carbon::now() . " -> " . $item->Status . " door " . auth()->user()->name . "\n";
        });

        self::updating(function ($item) {
            if ($item->isDirty('Status')) {
                $item->Activity = $item->Activity . Carbon::now() . " -> " . $item->Status . " door " . auth()->user()->name . "\n";
            }
        });
    }
}
