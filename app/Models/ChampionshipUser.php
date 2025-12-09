<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class ChampionshipUser extends Pivot
{
    protected $table = 'championship_user';

    protected $fillable = [
        'championship_id',
        'user_id',
        'is_banned',
        'total_points',
        'position',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function championship()
    {
        return $this->belongsTo(Championship::class);
    }
}
