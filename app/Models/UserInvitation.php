<?php

namespace App\Models;

use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Model;

class UserInvitation extends Model
{
    //
    use HasRoles;
    protected $table = 'user_invitations';

    protected $fillable = [
        'email',
        'code',
    ];
}
