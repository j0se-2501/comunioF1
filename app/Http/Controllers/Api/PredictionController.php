<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use App\Models\Prediction;
use App\Models\Race;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class PredictionController extends Controller
{
    /**
     * Obtener la próxima carrera disponible para predecir
     */
    public function nextRace($championshipId)
    {
        $championship = Championship::findOrFail($championshipId);

        $nextRace = Race::where('season_id', $championship->season_id)
            ->where('race_date', '>', now())
            ->orderBy('race_date', 'asc')
            ->first();

        if (!$nextRace) {
            return response()->json(['message' => 'No more races available'], 404);
        }

        return response()->json($nextRace);
    }

    /**
     * Ver predicción del usuario para una carrera concreta
     */
    public function show($championshipId, $raceId)
    {
        $user = Auth::user();

        $prediction = Prediction::where([
            'championship_id' => $championshipId,
            'race_id' => $raceId,
            'user_id' => $user->id
        ])->first();

        return response()->json($prediction);
    }

    /**
     * Crear o editar predicción para una carrera
     */
    public function store(Request $request, $championshipId, $raceId)
{
    $user = Auth::user();

    // Validaciones de entrada
    $data = $request->validate([
        'position_1' => 'nullable|exists:drivers,id',
        'position_2' => 'nullable|exists:drivers,id',
        'position_3' => 'nullable|exists:drivers,id',
        'position_4' => 'nullable|exists:drivers,id',
        'position_5' => 'nullable|exists:drivers,id',
        'position_6' => 'nullable|exists:drivers,id',
        'pole'       => 'nullable|exists:drivers,id',
        'fastest_lap'=> 'nullable|exists:drivers,id',
        'last_place' => 'nullable|exists:drivers,id',
    ]);

    $championship = Championship::findOrFail($championshipId);
    $race = Race::findOrFail($raceId);

    // 1. Validar que la carrera no ha empezado (qualy como deadline)
    if ($race->qualy_date && Carbon::parse($race->qualy_date)->isPast()) {
        return response()->json(['message' => 'Predictions are closed for this race'], 403);
    }

    // 2. Validar que el usuario pertenece al championship
    if (!$championship->users()->where('user_id', $user->id)->exists()) {
        return response()->json(['message' => 'You are not part of this championship'], 403);
    }

    // 3. Crear o actualizar la predicción en ESTE championship
    $prediction = Prediction::updateOrCreate(
        [
            'championship_id' => $championshipId,
            'race_id' => $raceId,
            'user_id' => $user->id,
        ],
        $data
    );

    // 4. Replicar en otros championships donde participa el usuario (misma season)
    $sameSeasonChampionships = $user->championships()
        ->where('season_id', $championship->season_id)
        ->where('championship_id', '!=', $championshipId)
        ->get();

    foreach ($sameSeasonChampionships as $ch) {
        Prediction::updateOrCreate(
            [
                'championship_id' => $ch->id,
                'race_id' => $raceId,
                'user_id' => $user->id,
            ],
            $data
        );
    }

    return response()->json([
        'message' => 'Prediction saved successfully',
        'prediction' => $prediction
    ]);
}

}
