<?php

namespace Tests\Unit;

use App\Models\Race;
use App\Models\RaceResult;
use App\Models\Season;
use App\Models\Team;
use App\Models\Driver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class RaceControllerTest extends TestCase
{
    use RefreshDatabase;

    private function season(): Season
    {
        return Season::forceCreate([
            'name' => 'Season',
            'description' => 'Races',
            'year' => 2036,
            'is_current_season' => false,
        ]);
    }

    private function race(Season $season, string $name, int $round): Race
    {
        return Race::forceCreate([
            'season_id' => $season->id,
            'name' => $name,
            'round_number' => $round,
            'race_date' => Carbon::now()->addDays($round + 2),
            'qualy_date' => Carbon::now()->addDays($round + 1),
            'is_result_confirmed' => false,
        ]);
    }

    private function driver(): Driver
    {
        $team = Team::create(['name' => 'Team']);
        return Driver::forceCreate([
            'team_id' => $team->id,
            'name' => 'Pilot',
            'country' => '🇪🇸',
            'short_code' => 'PIL',
            'number' => 11,
        ]);
    }

    public function test_index_returns_races_ordered(): void
    {
        $season = $this->season();
        $r1 = $this->race($season, 'GP0', 1);
        $r2 = $this->race($season, 'GP1', 2);

        $this->actingAs($this->fakeUser())
            ->getJson("/api/seasons/{$season->id}/races")
            ->assertOk()
            ->assertJsonPath('0.name', 'GP0')
            ->assertJsonPath('1.name', 'GP1');
    }

    public function test_show_returns_race_with_season(): void
    {
        $season = $this->season();
        $race = $this->race($season, 'GP', 1);

        $this->actingAs($this->fakeUser())
            ->getJson("/api/races/{$race->id}")
            ->assertOk()
            ->assertJsonFragment(['name' => 'GP'])
            ->assertJsonPath('season.id', $season->id);
    }

    public function test_results_returns_ordered_results(): void
    {
        $season = $this->season();
        $race = $this->race($season, 'GP', 1);
        $driver = $this->driver();

        RaceResult::create([
            'race_id' => $race->id,
            'driver_id' => $driver->id,
            'position' => 1,
            'is_pole' => true,
            'fastest_lap' => false,
            'is_last_place' => false,
        ]);

        $this->actingAs($this->fakeUser())
            ->getJson("/api/races/{$race->id}/results")
            ->assertOk()
            ->assertJsonPath('0.position', 1)
            ->assertJsonPath('0.driver_id', $driver->id);
    }

    private function fakeUser()
    {
        return \App\Models\User::create([
            'name' => 'Viewer',
            'email' => 'viewer@race.com',
            'password' => bcrypt('pw'),
        ]);
    }
}
