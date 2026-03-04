<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Filament\Panel;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Notifications\Notifiable;
use Filament\Models\Contracts\FilamentUser;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Rappasoft\LaravelAuthenticationLog\Traits\AuthenticationLoggable;

class User extends Authenticatable implements FilamentUser
{

    use HasApiTokens, HasFactory, Notifiable, HasRoles, AuthenticationLoggable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'active',
        'fb_id',
        'voortoekenning',
        'notes',
        'selected_year'
    ];

    public function agency()
    {
        return $this->hasMany(Agency::class, 'verzamel_user_id');
    }



    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    /**
     */
    function canAccessPanel(Panel $panel): bool
    {
        if ($this->active == true) {
            return  true;
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return true;
        if ($this->name == 'henk') {
            return true;
        } else {
            return false;
        }
    }

    public function isBeheerder(): bool
    {
        if ($this->name == 'bettie') {
            return true;
        } else {
            return false;
        }
    }



    public function clothing(): HasMany
    {
        return $this->hasMany(Clothing::class, 'maakster_id');
    }
}
