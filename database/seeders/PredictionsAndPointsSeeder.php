<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Season;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Championship;
use App\Models\Driver;
use App\Models\Prediction;
use App\Models\RacePoint;
use App\Models\ChampionshipStanding;

class PredictionsAndPointsSeeder extends Seeder
{
    public function run(): void
    {
        $season = Season::where('year', 2026)->firstOrFail();

        $races = Race::where('season_id', $season->id)
            ->where('is_result_confirmed', true)
            ->orderBy('round_number')
            ->get();

        $championships = Championship::with(['users', 'scoringSystem'])
            ->where('season_id', $season->id)
            ->get();

        $driverIds = Driver::orderBy('id')->pluck('id')->all();
        $driverCount = count($driverIds);

        foreach ($races as $raceIndex => $race) {
            // Cache resultados reales
            $results = RaceResult::where('race_id', $race->id)->get();
            $resultsByPosition = $results->whereNotNull('position')->keyBy('position');
            $lastPositionResult = $results->whereNotNull('position')->sortByDesc('position')->first();
            $poleResult = $results->firstWhere('is_pole', true);
            $fastestLapResult = $results->firstWhere('fastest_lap', true);

            foreach ($championships as $championship) {
                if (!$driverCount) {
                    continue;
                }

                // Seed predictions for every user in the championship
                foreach ($championship->users as $userIndex => $user) {
                    $offset = ($raceIndex + $userIndex) % $driverCount;
                    $grid = array_merge(
                        array_slice($driverIds, $offset),
                        array_slice($driverIds, 0, $offset)
                    );

                    // Garantizar al menos 6 posiciones
                    $positions = array_pad($grid, 6, null);

                    Prediction::updateOrCreate(
                        [
                            'race_id'         => $race->id,
                            'user_id'         => $user->id,
                            'championship_id' => $championship->id,
                        ],
                        [
                            'position_1'  => $positions[0],
                            'position_2'  => $positions[1],
                            'position_3'  => $positions[2],
                            'position_4'  => $positions[3],
                            'position_5'  => $positions[4],
                            'position_6'  => $positions[5],
                            'pole'        => $positions[0],
                            'fastest_lap' => $positions[3] ?? null,
                            'last_place'  => end($grid),
                        ]
                    );
                }

                // Calcular puntos para este championship y carrera
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

                    $checkPosition = function ($predictedDriverId, $positionKey, $scoreValue, $flagKey) use (&$points, &$flags, $resultsByPosition) {
                        if (!$predictedDriverId || !isset($resultsByPosition[$positionKey])) {
                            return;
                        }

                        if ($predictedDriverId == $resultsByPosition[$positionKey]->driver_id) {
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

                    if ($prediction->pole && $poleResult && $prediction->pole == $poleResult->driver_id) {
                        $points += $scoring->points_pole;
                        $flags['guessed_pole'] = true;
                    }

                    if ($prediction->fastest_lap && $fastestLapResult && $prediction->fastest_lap == $fastestLapResult->driver_id) {
                        $points += $scoring->points_fastest_lap;
                        $flags['guessed_fastest_lap'] = true;
                    }

                    if ($prediction->last_place && $lastPositionResult && $prediction->last_place == $lastPositionResult->driver_id) {
                        $points += $scoring->points_last_place;
                        $flags['guessed_last_place'] = true;
                    }

                    RacePoint::updateOrCreate(
                        [
                            'prediction_id'   => $prediction->id,
                            'race_id'         => $race->id,
                            'championship_id' => $championship->id,
                            'user_id'         => $prediction->user_id,
                        ],
                        array_merge(['points' => $points], $flags)
                    );

                    // actualizar total_points en pivot
                    $totalPoints = RacePoint::where('championship_id', $championship->id)
                        ->where('user_id', $prediction->user_id)
                        ->sum('points');

                    $championship->users()->updateExistingPivot(
                        $prediction->user_id,
                        ['total_points' => $totalPoints]
                    );
                }

                // Recalcular posiciones en pivot
                $orderedUsers = DB::table('championship_user')
                    ->where('championship_id', $championship->id)
                    ->orderByDesc('total_points')
                    ->orderBy('user_id')
                    ->get();

                $position = 1;
                foreach ($orderedUsers as $userRow) {
                    $championship->users()->updateExistingPivot(
                        $userRow->user_id,
                        ['position' => $position]
                    );
                    $position++;
                }
            }
        }

        // Crear standings finales (tras la 7a carrera)
        $lastRace = $races->last();
        if ($lastRace) {
            foreach ($championships as $championship) {
                $orderedUsers = DB::table('championship_user')
                    ->where('championship_id', $championship->id)
                    ->orderByDesc('total_points')
                    ->orderBy('user_id')
                    ->get();

                foreach ($orderedUsers as $userRow) {
                    ChampionshipStanding::updateOrCreate(
                        [
                            'championship_id' => $championship->id,
                            'race_id'         => $lastRace->id,
                            'user_id'         => $userRow->user_id,
                        ],
                        [
                            'total_points' => $userRow->total_points,
                            'position'     => $userRow->position ?? null,
                        ]
                    );
                }
            }
        }
    }
}
