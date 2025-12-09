<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Season;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    /**
     * Listar todas las carreras de una season
     */
    public function index($seasonId)
    {
        $season = Season::findOrFail($seasonId);

        $races = Race::where('season_id', $season->id)
            ->orderBy('round', 'asc')
            ->get();

        return response()->json($races);
    }

    /**
     * Ver información básica de una carrera
     */
    public function show($raceId)
    {
        $race = Race::with('season')->findOrFail($raceId);

        return response()->json($race);
    }

    /**
     * Ver los resultados reales de una carrera (si existen)
     */
    public function results($raceId)
    {
        $race = Race::findOrFail($raceId);

        // Cargar resultados oficiales ordenados por posición
        $results = $race->results()
            ->with('driver.team')
            ->orderBy('position', 'asc')
            ->get();

        return response()->json($results);
    }
}
