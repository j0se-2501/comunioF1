<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Season extends Model
{
    protected $fillable = [
        'year',
        'is_current_season',
    ];

    public function races()
    {
        return $this->hasMany(Race::class);
    }

    public function championships()
    {
        return $this->hasMany(Championship::class);
    }
}
