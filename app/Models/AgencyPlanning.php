<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AgencyPlanning extends Model
{
    //
    protected $table = 'agency_planning';
    use HasFactory;
    protected $fillable = [
        'name',
        'notificationMail',
        'dateExecution',
        'dateNotification',
    ];

    public function Agency(): BelongsTo
    {

        return $this->belongsTo(Agency::class);
    }
}
