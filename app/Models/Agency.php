<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Agency extends Model
{
    protected $table = 'agencies';
    use HasFactory;
    protected $fillable = [
        'Code',
        'Status',
        'Aanvrager',
        'Adres',
        'Postcode',
        'Vestigingsplaats',
        'Naam_Contactpersoon',
        'Emailadres_Contactpersoon',
        'Bezorg_Naam',
        'Bezorg_Email',
        'Bezorg_Adres',
        'Bezorg_Postcode',
        'Bezorg_Plaats',
        'Overige_Mail',
        'KVK-nummer',
        'Partner_2022',
        'Extra_info',
        'Bezoek',
        'verzamel_user_id'

    ];

    //public function planning(): HasMany
    //{
    //    return $this->hasMany(AgencyPlanning::class);
    //}

    public function verzamel_user()
    {

        return $this->belongsTo(User::class);
    }

    public function clothing(): HasMany
    {
        return $this->hasMany(Clothing::class);
    }

    public function open(): HasMany
    {
        return $this->clothing()->where('Status', '!=', 'Afgehandeld');
    }



    public function monthjan()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 1')->withArchived();
    }
    public function monthfeb()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 2')->withArchived();
    }
    public function monthmar()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 3')->withArchived();
    }
    public function monthapr()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 4')->withArchived();
    }
    public function monthmay()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 5')->withArchived();
    }
    public function monthjun()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 6')->withArchived();
    }
    public function monthjul()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 7')->withArchived();
    }
    public function monthaug()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 8')->withArchived();
    }
    public function monthsep()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 9')->withArchived();
    }
    public function monthokt()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 10')->withArchived();
    }
    public function monthnov()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 11')->withArchived();
    }
    public function monthdec()
    {
        return $this->clothing()->whereRaw('month(datummaakster) = 12')->withArchived();
    }

    public function ingevoerd()
    {
        return $this->clothing()->where('Status', '=', 'Ingevoerd');
    }

    public function aangeboden()
    {
        return $this->clothing()->where('Status', '=', 'Aangeboden');
    }

    public function opgepakt()
    {
        return $this->clothing()->where('Status', '=', 'Opgepakt');
    }

    public function verzonden()
    {
        return $this->clothing()->where('Status', '=', 'Verzonden');
    }

    public function klaar()
    {
        return $this->clothing()->where('Status', '=', 'Klaar');
    }
    public function ontvangen()
    {
        return $this->clothing()->where('Status', '=', 'Ontvangen');
    }
    public function afgehandeld()
    {
        return $this->clothing()->where('Status', '=', 'Afgehandeld');
    }

    public static function booted()
    {
        static::creating(function ($model) {
            //$model->created_by_user_id = \Auth::user()?->getKey();
        });

        static::updating(function ($model) {
            //$model->updated_by_user_id = \Auth::user()?->getKey();
        });
    }
}
