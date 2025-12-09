<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prediction extends Model
{
    protected $fillable = [
        'race_id',
        'user_id',
        'championship_id',
        'position_1',
        'position_2',
        'position_3',
        'position_4',
        'position_5',
        'position_6',
        'pole',
        'fastest_lap',
        'last_place',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }

    public function racePoint()
    {
        return $this->hasOne(RacePoint::class);
    }
}
