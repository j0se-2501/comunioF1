<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Season;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    


    public function next()
    {
        $season = Season::where('is_current_season', true)->first();

        if (!$season) {
            return response()->json(['message' => 'No hay season marcada como actual'], 404);
        }

        $race = Race::where('season_id', $season->id)
            ->where('is_result_confirmed', false)
            ->orderBy('round', 'asc')
            ->first();

        if (!$race) {
            return response()->json(['message' => 'No quedan carreras pendientes en la season actual'], 404);
        }

        $race->load('season');

        return response()->json($race);
    }

    


    public function last()
    {
        $season = Season::where('is_current_season', true)->first();

        if (!$season) {
            return response()->json(['message' => 'No hay season marcada como actual'], 404);
        }

        $race = Race::where('season_id', $season->id)
            ->where('is_result_confirmed', true)
            ->orderBy('round', 'desc')
            ->first();

        if (!$race) {
            return response()->json(['message' => 'No hay carreras confirmadas en la season actual'], 404);
        }

        $race->load('season');

        return response()->json($race);
    }

    


    public function index($seasonId)
    {
        $season = Season::findOrFail($seasonId);

        $races = Race::where('season_id', $season->id)
            ->orderBy('round', 'asc')
            ->get();

        return response()->json($races);
    }

    


    public function show($raceId)
    {
        $race = Race::with('season')->findOrFail($raceId);

        return response()->json($race);
    }

    


    public function results($raceId)
    {
        $race = Race::findOrFail($raceId);

         
        $results = $race->results()
            ->with('driver.team')
            ->orderBy('position', 'asc')
            ->get();

        return response()->json($results);
    }
}
