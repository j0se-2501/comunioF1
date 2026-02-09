<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Championship;
use App\Models\Prediction;
use App\Models\RacePoint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ResultController extends Controller
{
    


    private function authorizeGlobalAdmin()
    {
        if (!Auth::user() || !Auth::user()->is_admin) {
            abort(403, 'Only global admins can perform this action');
        }
    }

    


    public function store(Request $request, $raceId)
    {
        $this->authorizeGlobalAdmin();

        $race = Race::findOrFail($raceId);

        $data = $request->validate([
            'results' => 'required|array|min:1',
            'results.*.driver_id'     => 'required|exists:drivers,id',
            'results.*.position'      => 'nullable|integer|min:1',
            'results.*.is_pole'       => 'boolean',
            'results.*.fastest_lap'   => 'boolean',
            'results.*.is_last_place' => 'boolean',
        ]);

         
        RaceResult::where('race_id', $race->id)->delete();

        foreach ($data['results'] as $entry) {
            RaceResult::create([
                'race_id'       => $race->id,
                'driver_id'     => $entry['driver_id'],
                'position'      => $entry['position'] ?? null,
                'is_pole'       => $entry['is_pole'] ?? false,
                'fastest_lap'   => $entry['fastest_lap'] ?? false,
                'is_last_place' => $entry['is_last_place'] ?? false,
            ]);
        }

        return response()->json([
            'message' => 'Race results saved successfully'
        ]);
    }

    


    public function confirm($raceId)
    {
        $this->authorizeGlobalAdmin();

        $race = Race::findOrFail($raceId);

         
        $race->update(['is_result_confirmed' => true]);

        return response()->json([
            'message' => 'Race confirmed successfully'
        ]);
    }

    


    public function calculate($raceId)
    {
        $this->authorizeGlobalAdmin();

        $race = Race::with('season')->findOrFail($raceId);

        $results = RaceResult::where('race_id', $raceId)->get();

        if ($results->isEmpty()) {
            return response()->json(['message' => 'No results found'], 400);
        }

         
        $resultsByPosition = $results
            ->whereNotNull('position')
            ->keyBy('position');

         
        $lastPositionResult = $results
            ->whereNotNull('position')
            ->sortByDesc('position')
            ->first();

         
        $poleResult = $results->firstWhere('is_pole', true);
        $fastestLapResult = $results->firstWhere('fastest_lap', true);

         
        $championships = Championship::where('season_id', $race->season_id)->get();

        foreach ($championships as $championship) {
            $this->calculateForChampionship(
                $championship,
                $race,
                $resultsByPosition,
                $poleResult,
                $fastestLapResult,
                $lastPositionResult
            );
        }

        return response()->json([
            'message' => 'Points calculated successfully'
        ]);
    }

    


    private function calculateForChampionship(
        Championship $championship,
        Race $race,
        $resultsByPosition,
        $poleResult,
        $fastestLapResult,
        $lastPositionResult
    ) {
        $scoring = $championship->scoringSystem;

        $predictions = Prediction::where('championship_id', $championship->id)
            ->where('race_id', $race->id)
            ->get();

        foreach ($predictions as $prediction) {

            $points = 0;

             
            $flags = [
                'guessed_p1' => false,
                'guessed_p2' => false,
                'guessed_p3' => false,
                'guessed_p4' => false,
                'guessed_p5' => false,
                'guessed_p6' => false,
                'guessed_pole' => false,
                'guessed_fastest_lap' => false,
                'guessed_last_place' => false,
            ];

             
            $checkPosition = function ($predictedDriverId, $positionKey, $scoreValue, $flagKey) 
                use (&$points, &$flags, $resultsByPosition) 
            {
                if (!$predictedDriverId || !isset($resultsByPosition[$positionKey])) {
                    return;
                }

                $realDriver = $resultsByPosition[$positionKey]->driver_id;

                if ($predictedDriverId == $realDriver) {
                    $points += $scoreValue;
                    $flags[$flagKey] = true;
                }
            };

             
            $checkPosition($prediction->position_1, 1, $scoring->points_p1, 'guessed_p1');
            $checkPosition($prediction->position_2, 2, $scoring->points_p2, 'guessed_p2');
            $checkPosition($prediction->position_3, 3, $scoring->points_p3, 'guessed_p3');
            $checkPosition($prediction->position_4, 4, $scoring->points_p4, 'guessed_p4');
            $checkPosition($prediction->position_5, 5, $scoring->points_p5, 'guessed_p5');
            $checkPosition($prediction->position_6, 6, $scoring->points_p6, 'guessed_p6');

             
            if ($prediction->pole && $poleResult) {
                if ($prediction->pole == $poleResult->driver_id) {
                    $points += $scoring->points_pole;
                    $flags['guessed_pole'] = true;
                }
            }

             
            if ($prediction->fastest_lap && $fastestLapResult) {
                if ($prediction->fastest_lap == $fastestLapResult->driver_id) {
                    $points += $scoring->points_fastest_lap;
                    $flags['guessed_fastest_lap'] = true;
                }
            }

             
            if ($prediction->last_place && $lastPositionResult) {
                if ($prediction->last_place == $lastPositionResult->driver_id) {
                    $points += $scoring->points_last_place;
                    $flags['guessed_last_place'] = true;
                }
            }

             
            RacePoint::updateOrCreate(
                [
                    'prediction_id'   => $prediction->id,
                    'race_id'         => $race->id,
                    'championship_id' => $championship->id,
                    'user_id'         => $prediction->user_id,
                ],
                array_merge(
                    ['points' => $points],
                    $flags
                )
            );

             
            $championship->users()->updateExistingPivot(
                $prediction->user_id,
                ['total_points' => $this->totalUserPoints($championship->id, $prediction->user_id)]
            );
        }

         
        $this->updateStandings($championship);
    }

    


    private function totalUserPoints($championshipId, $userId)
    {
        return RacePoint::where('championship_id', $championshipId)
            ->where('user_id', $userId)
            ->sum('points');
    }

    


    private function updateStandings(Championship $championship)
    {
        $users = $championship->users()
            ->orderByDesc('total_points')
            ->get();

        $position = 1;

        foreach ($users as $user) {
            $championship->users()->updateExistingPivot(
                $user->id,
                ['position' => $position]
            );
            $position++;
        }
    }

    


    public function racePoints($championshipId, $raceId)
    {
        $racePoints = RacePoint::where('championship_id', $championshipId)
            ->where('race_id', $raceId)
            ->with(['user', 'prediction', 'race'])
            ->get();

        return response()->json($racePoints);
    }
}
