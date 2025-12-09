<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ScoringSystem extends Model
{
    protected $fillable = [
        'championship_id',
        'points_p1',
        'points_p2',
        'points_p3',
        'points_p4',
        'points_p5',
        'points_p6',
        'points_pole',
        'points_fastest_lap',
        'points_last_place',
    ];

    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
}
