<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\Season;
use Illuminate\Http\Request;

class RaceController extends Controller
{
    /**
     * Próxima carrera de la season actual (primera sin resultado confirmado)
     */
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

    /**
     * Última carrera disputada de la season actual (última confirmada)
     */
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
