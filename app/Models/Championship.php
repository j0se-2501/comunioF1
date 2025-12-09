<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Championship extends Model
{
    protected $fillable = [
        'admin_id',
        'season_id',
        'name',
        'invitation_code',
        'status',
    ];

    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    public function season()
    {
        return $this->belongsTo(Season::class);
    }

    public function scoringSystem()
    {
        return $this->hasOne(ScoringSystem::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class)
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
