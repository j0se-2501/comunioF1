<?php

namespace Tests\Unit;

use App\Models\Championship;
use App\Models\ScoringSystem;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ScoringControllerTest extends TestCase
{
    use RefreshDatabase;

    private function baseData(): array
    {
        $admin = User::create(['name' => 'Admin', 'email' => 'admin@score.com', 'password' => bcrypt('pw')]);
        $member = User::create(['name' => 'Member', 'email' => 'member@score.com', 'password' => bcrypt('pw')]);

        $season = Season::forceCreate([
            'name' => 'Season',
            'description' => 'Scoring',
            'year' => 2037,
            'is_current_season' => false,
        ]);

        $champ = Championship::forceCreate([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'SCOR123456',
            'status' => 'active',
        ]);

        $scoring = ScoringSystem::create([
            'championship_id' => $champ->id,
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

        $champ->users()->attach($member->id, ['is_banned' => false, 'total_points' => 0, 'position' => null]);

        return [$admin, $member, $champ, $scoring];
    }

    public function test_show_denied_if_not_member_or_admin(): void
    {
        [$admin, $member, $champ] = $this->baseData();
        $stranger = User::create(['name' => 'Out', 'email' => 'out@test.com', 'password' => bcrypt('pw')]);

        $this->actingAs($stranger)
            ->getJson("/api/championships/{$champ->id}/scoring")
            ->assertStatus(403);
    }

    public function test_update_only_admin(): void
    {
        [$admin, $member, $champ] = $this->baseData();

        $payload = [
            'points_p1' => 25,
            'points_p2' => 18,
            'points_p3' => 15,
            'points_p4' => 12,
            'points_p5' => 10,
            'points_p6' => 8,
            'points_pole' => 3,
            'points_fastest_lap' => 1,
            'points_last_place' => 2,
        ];

        $this->actingAs($member)
            ->putJson("/api/championships/{$champ->id}/scoring", $payload)
            ->assertStatus(403);

        $this->actingAs($admin)
            ->putJson("/api/championships/{$champ->id}/scoring", $payload)
            ->assertOk();

        $this->assertDatabaseHas('scoring_systems', [
            'championship_id' => $champ->id,
            'points_p1' => 25,
            'points_last_place' => 2,
        ]);
    }

    public function test_reset_restores_defaults(): void
    {
        [$admin, , $champ] = $this->baseData();

        $this->actingAs($admin)
            ->postJson("/api/championships/{$champ->id}/scoring/reset")
            ->assertOk();

        $this->assertDatabaseHas('scoring_systems', [
            'championship_id' => $champ->id,
            'points_p1' => 10,
            'points_last_place' => 1,
        ]);
    }
}
