<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'profile_pic',
        'country',
        'is_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

     
    public function championshipsOwned()
    {
        return $this->hasMany(Championship::class, 'admin_id');
    }

     
    public function championships()
    {
        return $this->belongsToMany(Championship::class)
            ->withPivot(['is_banned', 'total_points', 'position'])
            ->withTimestamps();
    }

    public function predictions()
    {
        return $this->hasMany(Prediction::class);
    }

    public function racePoints()
    {
        return $this->hasMany(RacePoint::class);
    }

    public function standings()
    {
        return $this->hasMany(ChampionshipStanding::class);
    }
}
