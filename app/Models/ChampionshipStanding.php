<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ChampionshipStanding extends Model
{
    protected $fillable = [
        'championship_id',
        'race_id',
        'user_id',
        'total_points',
        'position',
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
}
