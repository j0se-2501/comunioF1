<?php

namespace Tests\Unit;

use App\Models\Championship;
use App\Models\Driver;
use App\Models\Prediction;
use App\Models\Race;
use App\Models\RaceResult;
use App\Models\ScoringSystem;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResultControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSeasonRace(): array
    {
        $season = Season::forceCreate([
            'name' => 'Season Calc',
            'description' => 'Calc tests',
            'year' => 2032,
            'is_current_season' => false,
        ]);

        $race = Race::forceCreate([
            'season_id' => $season->id,
            'name' => 'GP Calc',
            'round_number' => 1,
            'race_date' => Carbon::now()->addDays(3),
            'qualy_date' => Carbon::now()->addDays(2),
            'is_result_confirmed' => false,
        ]);

        return [$season, $race];
    }

    private function createDrivers(): array
    {
        $team = Team::create(['name' => 'Calc Team']);

        $p1 = Driver::forceCreate([
            'team_id' => $team->id,
            'name' => 'Pilot One',
            'country' => '🇬🇧',
            'short_code' => 'P1',
            'number' => 44,
        ]);

        $pole = Driver::forceCreate([
            'team_id' => $team->id,
            'name' => 'Pole Man',
            'country' => '🇪🇸',
            'short_code' => 'P2',
            'number' => 14,
        ]);

        return [$p1, $pole];
    }

    private function createChampionship(Season $season, User $user): Championship
    {
        $championship = Championship::create([
            'admin_id' => $user->id,
            'season_id' => $season->id,
            'name' => 'Liga Calc',
            'invitation_code' => 'CALC12345',
            'status' => 'active',
        ]);

        ScoringSystem::create([
            'championship_id' => $championship->id,
            'points_p1' => 10,
            'points_p2' => 6,
            'points_p3' => 4,
            'points_p4' => 3,
            'points_p5' => 2,
            'points_p6' => 1,
            'points_pole' => 3,
            'points_fastest_lap' => 1,
            'points_last_place' => 3,
        ]);

        $championship->users()->attach($user->id, [
            'total_points' => 0,
            'is_banned' => false,
            'position' => null,
        ]);

        return $championship;
    }

    public function test_calculate_assigns_points_and_updates_total(): void
    {
        [$season, $race] = $this->createSeasonRace();
        [$driverWin, $driverPole] = $this->createDrivers();

        $admin = User::create([
            'name' => 'Global Admin',
            'email' => 'admin@calc.com',
            'password' => bcrypt('password'),
            'is_admin' => true,
        ]);

        $player = User::create([
            'name' => 'Player',
            'email' => 'player@calc.com',
            'password' => bcrypt('password'),
        ]);

        $championship = $this->createChampionship($season, $player);

         
        Prediction::create([
            'race_id' => $race->id,
            'user_id' => $player->id,
            'championship_id' => $championship->id,
            'position_1' => $driverWin->id,
            'pole' => $driverPole->id,
        ]);

         
        $this->actingAs($admin)->postJson("/api/races/{$race->id}/results", [
            'results' => [
                ['driver_id' => $driverWin->id, 'position' => 1, 'is_pole' => false, 'fastest_lap' => false, 'is_last_place' => false],
                ['driver_id' => $driverPole->id, 'position' => 2, 'is_pole' => true, 'fastest_lap' => false, 'is_last_place' => false],
            ],
        ])->assertStatus(200);

         
        $this->actingAs($admin)->postJson("/api/races/{$race->id}/calculate")
            ->assertStatus(200);

         
        $this->assertDatabaseHas('race_points', [
            'championship_id' => $championship->id,
            'race_id' => $race->id,
            'user_id' => $player->id,
            'points' => 13,  
            'guessed_p1' => true,
            'guessed_pole' => true,
        ]);

         
        $this->assertDatabaseHas('championship_user', [
            'championship_id' => $championship->id,
            'user_id' => $player->id,
            'total_points' => 13,
        ]);
    }

    public function test_store_requires_admin(): void
    {
        [$season, $race] = $this->createSeasonRace();
        $drivers = $this->createDrivers();
        $user = User::create(['name' => 'Player', 'email' => 'x@x.com', 'password' => bcrypt('pw')]);

        $this->actingAs($user)
            ->postJson("/api/races/{$race->id}/results", [
                'results' => [
                    ['driver_id' => $drivers[0]->id, 'position' => 1, 'is_pole' => false, 'fastest_lap' => false, 'is_last_place' => false],
                ],
            ])
            ->assertStatus(403);
    }

    public function test_store_saves_results_replacing_previous(): void
    {
        [$season, $race] = $this->createSeasonRace();
        [$d1, $d2] = $this->createDrivers();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@store.com', 'password' => bcrypt('pw'), 'is_admin' => true]);

         
        $this->actingAs($admin)
            ->postJson("/api/races/{$race->id}/results", [
                'results' => [
                    ['driver_id' => $d1->id, 'position' => 1, 'is_pole' => true, 'fastest_lap' => false, 'is_last_place' => false],
                ],
            ])
            ->assertOk();

         
        $this->actingAs($admin)
            ->postJson("/api/races/{$race->id}/results", [
                'results' => [
                    ['driver_id' => $d2->id, 'position' => 1, 'is_pole' => true, 'fastest_lap' => true, 'is_last_place' => false],
                ],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('race_results', ['driver_id' => $d1->id]);
        $this->assertDatabaseHas('race_results', ['driver_id' => $d2->id, 'fastest_lap' => true]);
    }

    public function test_confirm_marks_race_as_confirmed(): void
    {
        [$season, $race] = $this->createSeasonRace();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@confirm.com', 'password' => bcrypt('pw'), 'is_admin' => true]);

        $this->actingAs($admin)
            ->postJson("/api/races/{$race->id}/confirm")
            ->assertOk();

        $this->assertTrue((bool) $race->fresh()->is_result_confirmed);
    }
}
