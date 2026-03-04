<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AuthenticationLog extends Model
{
    protected $table = 'authentication_log';
    use HasFactory;
    protected $fillable = [


    ];


}
