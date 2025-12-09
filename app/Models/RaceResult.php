<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RaceResult extends Model
{
    protected $fillable = [
        'race_id',
        'driver_id',
        'position',
        'is_pole',
        'fastest_lap',
        'is_last_place',
    ];

    public function race()
    {
        return $this->belongsTo(Race::class);
    }

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
}
