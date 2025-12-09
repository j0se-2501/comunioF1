<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Race extends Model
{
    protected $fillable = [
        'season_id',
        'name',
        'circuit',
        'round',
        'race_date',
        'qualy_date',
        'status',
    ];

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function results()
    {
        return $this->hasMany(RaceResult::class);
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
