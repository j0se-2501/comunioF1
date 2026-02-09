<?php

namespace Tests\Unit;

use App\Models\Championship;
use App\Models\Driver;
use App\Models\Prediction;
use App\Models\Race;
use App\Models\ScoringSystem;
use App\Models\Season;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PredictionControllerTest extends TestCase
{
    use RefreshDatabase;

    private function baseSeasonRace(): array
    {
        $season = Season::forceCreate([
            'name' => 'Temp Test',
            'description' => 'Temp predicciones',
            'year' => 2031,
            'is_current_season' => false,
        ]);

        $race = Race::forceCreate([
            'season_id' => $season->id,
            'name' => 'GP Test',
            'round_number' => 1,
            'race_date' => Carbon::now()->addDays(5),
            'qualy_date' => Carbon::now()->addDays(4),
            'is_result_confirmed' => false,
        ]);

        return [$season, $race];
    }

    private function makeDrivers(int $count = 3): array
    {
        $team = Team::create(['name' => 'Team Test']);
        $drivers = [];

        for ($i = 1; $i <= $count; $i++) {
            $drivers[] = Driver::forceCreate([
                'team_id' => $team->id,
                'name' => "Driver {$i}",
                'country' => '🇪🇸',
                'short_code' => str_pad((string)$i, 3, '0', STR_PAD_LEFT),
                'number' => $i,
            ]);
        }

        return $drivers;
    }

    private function createChampionshipWithUser(Season $season, User $user, string $name): Championship
    {
        $championship = Championship::create([
            'admin_id' => $user->id,
            'season_id' => $season->id,
            'name' => $name,
            'invitation_code' => 'CODE' . rand(100, 999),
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
            'is_banned' => false,
            'total_points' => 0,
            'position' => null,
        ]);

        return $championship;
    }

    public function test_store_prediction_clones_to_other_championships_same_season(): void
    {
        [$season, $race] = $this->baseSeasonRace();
        $user = User::create([
            'name' => 'Player',
            'email' => 'player@test.com',
            'password' => bcrypt('password'),
        ]);
        $drivers = $this->makeDrivers(2);

        $championshipA = $this->createChampionshipWithUser($season, $user, 'Liga A');
        $championshipB = $this->createChampionshipWithUser($season, $user, 'Liga B');

        $payload = [
            'position_1' => $drivers[0]->id,
            'pole' => $drivers[1]->id,
        ];

        $response = $this->actingAs($user)->postJson(
            "/api/championships/{$championshipA->id}/races/{$race->id}/prediction",
            $payload
        );

        $response->assertStatus(200);

         
        $this->assertDatabaseHas('predictions', [
            'championship_id' => $championshipA->id,
            'race_id' => $race->id,
            'user_id' => $user->id,
            'position_1' => $drivers[0]->id,
            'pole' => $drivers[1]->id,
        ]);

         
        $this->assertDatabaseHas('predictions', [
            'championship_id' => $championshipB->id,
            'race_id' => $race->id,
            'user_id' => $user->id,
            'position_1' => $drivers[0]->id,
            'pole' => $drivers[1]->id,
        ]);

        $this->assertEquals(
            2,
            Prediction::where('race_id', $race->id)->where('user_id', $user->id)->count()
        );
    }

    public function test_next_race_returns_future_race(): void
    {
        [$season, $race] = $this->baseSeasonRace();
        $user = User::create(['name' => 'User', 'email' => 'user@next.com', 'password' => bcrypt('pw')]);
        $champ = $this->createChampionshipWithUser($season, $user, 'Liga');

        $this->actingAs($user)
            ->getJson("/api/championships/{$champ->id}/races/next")
            ->assertOk()
            ->assertJsonFragment(['id' => $race->id]);
    }

    public function test_store_blocks_after_qualy_date(): void
    {
        [$season, $race] = $this->baseSeasonRace();
        $race->update([
            'qualy_date' => Carbon::now()->subHour(),
            'race_date' => Carbon::now()->addDay(),
        ]);

        $user = User::create(['name' => 'User', 'email' => 'user@lock.com', 'password' => bcrypt('pw')]);
        $drivers = $this->makeDrivers(1);
        $champ = $this->createChampionshipWithUser($season, $user, 'Liga');

        $this->actingAs($user)
            ->postJson("/api/championships/{$champ->id}/races/{$race->id}/prediction", [
                'position_1' => $drivers[0]->id,
            ])
            ->assertStatus(403);
    }
}
