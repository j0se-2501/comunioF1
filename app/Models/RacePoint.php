<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RacePoint extends Model
{
    protected $fillable = [
        'prediction_id',
        'race_id',
        'championship_id',
        'user_id',
        'points',
        'guessed_p1',
        'guessed_p2',
        'guessed_p3',
        'guessed_p4',
        'guessed_p5',
        'guessed_p6',
        'guessed_pole',
        'guessed_fastest_lap',
        'guessed_last_place',
    ];

    public function prediction()
    {
        return $this->belongsTo(Prediction::class);
    }

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
}
