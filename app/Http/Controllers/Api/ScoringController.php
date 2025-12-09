<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Championship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScoringController extends Controller
{
    /**
     * Ver sistema de puntuación de un championship
     */
    public function show($championshipId)
    {
        $championship = Championship::with('scoringSystem')->findOrFail($championshipId);

        // Solo miembros o admin deberían poder verlo; si quieres restringir más, se puede ajustar
        if (!$championship->users()->where('user_id', Auth::id())->exists()
            && $championship->admin_id !== Auth::id()) {
            abort(403, 'You are not part of this championship');
        }

        return response()->json($championship->scoringSystem);
    }

    /**
     * Actualizar sistema de puntuación (solo admin del championship)
     */
    public function update(Request $request, $championshipId)
    {
        $championship = Championship::with('scoringSystem')->findOrFail($championshipId);

        $this->authorizeAdmin($championship);

        $data = $request->validate([
            'points_p1'          => 'required|integer',
            'points_p2'          => 'required|integer',
            'points_p3'          => 'required|integer',
            'points_p4'          => 'required|integer',
            'points_p5'          => 'required|integer',
            'points_p6'          => 'required|integer',
            'points_pole'        => 'required|integer',
            'points_fastest_lap' => 'required|integer',
            'points_last_place'  => 'required|integer',
        ]);

        $championship->scoringSystem->update($data);

        return response()->json([
            'message'        => 'Scoring system updated successfully',
            'scoring_system' => $championship->scoringSystem,
        ]);
    }

    /**
     * Resetear sistema de puntuación a valores por defecto (solo admin)
     */
    public function reset($championshipId)
    {
        $championship = Championship::with('scoringSystem')->findOrFail($championshipId);

        $this->authorizeAdmin($championship);

        $defaults = [
            'points_p1'          => 10,
            'points_p2'          => 6,
            'points_p3'          => 4,
            'points_p4'          => 3,
            'points_p5'          => 2,
            'points_p6'          => 1,
            'points_pole'        => 3,
            'points_fastest_lap' => 1,
            'points_last_place'  => 1,
        ];

        $championship->scoringSystem->update($defaults);

        return response()->json([
            'message'        => 'Scoring system reset to default values',
            'scoring_system' => $championship->scoringSystem,
        ]);
    }

    /**
     * Comprobar que el user autenticado es admin del championship
     */
    private function authorizeAdmin(Championship $championship)
    {
        if ($championship->admin_id !== Auth::id()) {
            abort(403, 'Only the championship admin can perform this action');
        }
    }
}
