<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ChampionshipController;
use App\Http\Controllers\Api\PredictionController;
use App\Http\Controllers\Api\RaceController;
use App\Http\Controllers\Api\ResultController;
use App\Http\Controllers\Api\ScoringController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| Estas rutas exponen la API REST para el frontend SPA.
| Pública: register/login.
| Protegida con auth:sanctum: todo lo demás.
*/

// Auth pública
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth protegida
    Route::post('/logout', [AuthController::class, 'logout']);

    // User
    Route::get('/me', [UserController::class, 'me']);
    Route::match(['put', 'patch'], '/user', [UserController::class, 'update']);
    Route::put('/user/password', [UserController::class, 'updatePassword']);
    Route::get('/user/championships', [UserController::class, 'championships']);
    Route::get('/users/{user}', [UserController::class, 'show']);

    // Championships
    Route::prefix('championships')->group(function () {
        Route::get('/', [ChampionshipController::class, 'index']);
        Route::post('/', [ChampionshipController::class, 'store']);
        Route::get('/{id}', [ChampionshipController::class, 'show']);
        Route::match(['put', 'patch'], '/{id}', [ChampionshipController::class, 'update']);

        Route::get('/{id}/invitation-code', [ChampionshipController::class, 'invitationCode']);
        Route::post('/{id}/invitation-code/regenerate', [ChampionshipController::class, 'regenerateInvitationCode']);

        Route::post('/join', [ChampionshipController::class, 'join']);
        Route::post('/{id}/leave', [ChampionshipController::class, 'leave']);

        Route::get('/{id}/members', [ChampionshipController::class, 'members']);
        Route::post('/{id}/ban/{userId}', [ChampionshipController::class, 'banUser']);
        Route::post('/{id}/unban/{userId}', [ChampionshipController::class, 'unbanUser']);

        Route::match(['put', 'patch'], '/{id}/scoring', [ChampionshipController::class, 'updateScoring']);
    });

    // Scoring (si se usa controlador dedicado)
    Route::prefix('championships/{id}/scoring')->group(function () {
        Route::get('/', [ScoringController::class, 'show']);
        Route::match(['put', 'patch'], '/', [ScoringController::class, 'update']);
        Route::post('/reset', [ScoringController::class, 'reset']);
    });

    // Predictions
    Route::get('/championships/{id}/races/next', [PredictionController::class, 'nextRace']);
    Route::get('/championships/{id}/races/{raceId}/prediction', [PredictionController::class, 'show']);
    Route::post('/championships/{id}/races/{raceId}/prediction', [PredictionController::class, 'store']);

    // Races (consulta)
    Route::get('/seasons/{seasonId}/races', [RaceController::class, 'index']);
    Route::get('/races/{raceId}', [RaceController::class, 'show']);
    Route::get('/races/{raceId}/results', [RaceController::class, 'results']);

    // Results & points (admin global en controlador)
    Route::post('/races/{raceId}/results', [ResultController::class, 'store']);
    Route::post('/races/{raceId}/confirm', [ResultController::class, 'confirm']);
    Route::post('/races/{raceId}/calculate', [ResultController::class, 'calculate']);
});
