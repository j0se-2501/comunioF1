<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Driver extends Model
{
    protected $fillable = [
        'team_id',
        'name',
        'country',
        'code',
        'number',
    ];

    public function team()
    {
        return $this->belongsTo(Team::class);
    }

    public function raceResults()
    {
        return $this->hasMany(RaceResult::class);
    }
}
