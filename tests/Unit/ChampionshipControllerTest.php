<?php

namespace Tests\Unit;

use App\Models\Championship;
use App\Models\ScoringSystem;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChampionshipControllerTest extends TestCase
{
    use RefreshDatabase;

    private function createSeason(): Season
    {
        return Season::forceCreate([
            'name' => 'Temporada Test',
            'description' => 'Temp de pruebas',
            'year' => 2030,
            'is_current_season' => false,
        ]);
    }

    public function test_admin_creates_championship_with_scoring_and_membership(): void
    {
        $season = $this->createSeason();
        $user = User::create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ]);

        $response = $this->actingAs($user)->postJson('/api/championships', [
            'season_id' => $season->id,
            'name' => 'Liga Test',
        ]);

        $response->assertStatus(200);
        $championshipId = $response->json('championship.id');

        $this->assertDatabaseHas('championships', [
            'id' => $championshipId,
            'admin_id' => $user->id,
        ]);

        $this->assertDatabaseHas('scoring_systems', [
            'championship_id' => $championshipId,
        ]);

        $this->assertDatabaseHas('championship_user', [
            'championship_id' => $championshipId,
            'user_id' => $user->id,
            'total_points' => 0,
        ]);
    }

    public function test_index_returns_member_and_owned_without_duplicates(): void
    {
        $season = $this->createSeason();
        $user = User::create([
            'name' => 'User',
            'email' => 'user@test.com',
            'password' => bcrypt('password'),
        ]);

        $owned = Championship::forceCreate([
            'admin_id' => $user->id,
            'season_id' => $season->id,
            'name' => 'Owned',
            'invitation_code' => 'OWN1234567',
            'status' => 'active',
        ]);
        $owned->users()->attach($user->id, ['is_banned' => false, 'total_points' => 0, 'position' => null]);

        $member = Championship::forceCreate([
            'admin_id' => $owned->admin_id,
            'season_id' => $season->id,
            'name' => 'Member',
            'invitation_code' => 'MEM1234567',
            'status' => 'active',
        ]);
        $member->users()->attach($user->id, ['is_banned' => false, 'total_points' => 0, 'position' => null]);

        $this->actingAs($user)
            ->getJson('/api/championships')
            ->assertOk()
            ->assertJsonCount(2);
    }

    public function test_invitation_code_and_regenerate_only_admin(): void
    {
        $season = $this->createSeason();
        $admin = User::create(['name' => 'A', 'email' => 'a@test.com', 'password' => bcrypt('pw')]);
        $other = User::create(['name' => 'B', 'email' => 'b@test.com', 'password' => bcrypt('pw')]);

        $champ = Championship::forceCreate([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'CODEABCDEF',
            'status' => 'active',
        ]);

        $this->actingAs($other)
            ->getJson("/api/championships/{$champ->id}/invitation-code")
            ->assertStatus(403);

        $this->actingAs($admin)
            ->postJson("/api/championships/{$champ->id}/invitation-code/regenerate")
            ->assertOk()
            ->assertJsonStructure(['invitation_code']);
    }

    public function test_join_with_code_and_banned_blocked(): void
    {
        $season = $this->createSeason();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin3@test.com', 'password' => bcrypt('pw')]);
        $player = User::create(['name' => 'Player', 'email' => 'player@test.com', 'password' => bcrypt('pw')]);

        $champ = Championship::forceCreate([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'JOINCODE1',
            'status' => 'active',
        ]);

        // Ban the player first
        $champ->users()->attach($player->id, ['is_banned' => true, 'total_points' => 0, 'position' => null]);

        $this->actingAs($player)
            ->postJson('/api/championships/join', ['invitation_code' => 'JOINCODE1'])
            ->assertStatus(403);

        // Unban and try again
        $champ->users()->updateExistingPivot($player->id, ['is_banned' => false]);
        $this->actingAs($player)
            ->postJson('/api/championships/join', ['invitation_code' => 'JOINCODE1'])
            ->assertOk();
    }

    public function test_members_lists_pivot_fields(): void
    {
        $season = $this->createSeason();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin4@test.com', 'password' => bcrypt('pw')]);
        $member = User::create(['name' => 'Member', 'email' => 'member@test.com', 'password' => bcrypt('pw')]);

        $champ = Championship::forceCreate([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'MEMBERS1',
            'status' => 'active',
        ]);
        $champ->users()->attach($member->id, ['is_banned' => false, 'total_points' => 5, 'position' => 2]);

        $this->actingAs($admin)
            ->getJson("/api/championships/{$champ->id}/members")
            ->assertOk()
            ->assertJsonFragment(['total_points' => 5, 'position' => 2]);
    }

    public function test_ban_and_unban_user(): void
    {
        $season = $this->createSeason();
        $admin = User::create(['name' => 'Admin', 'email' => 'admin5@test.com', 'password' => bcrypt('pw')]);
        $member = User::create(['name' => 'Member', 'email' => 'member2@test.com', 'password' => bcrypt('pw')]);

        $champ = Championship::forceCreate([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'BAN1234567',
            'status' => 'active',
        ]);
        $champ->users()->attach($member->id, ['is_banned' => false, 'total_points' => 0, 'position' => null]);

        $this->actingAs($admin)
            ->postJson("/api/championships/{$champ->id}/ban/{$member->id}")
            ->assertOk();

        $this->assertDatabaseHas('championship_user', [
            'championship_id' => $champ->id,
            'user_id' => $member->id,
            'is_banned' => true,
        ]);

        $this->actingAs($admin)
            ->postJson("/api/championships/{$champ->id}/unban/{$member->id}")
            ->assertOk();

        $this->assertDatabaseHas('championship_user', [
            'championship_id' => $champ->id,
            'user_id' => $member->id,
            'is_banned' => false,
        ]);
    }

    public function test_admin_leaving_deletes_championship(): void
    {
        $season = $this->createSeason();
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin2@test.com',
            'password' => bcrypt('password'),
        ]);

        $championship = Championship::create([
            'admin_id' => $admin->id,
            'season_id' => $season->id,
            'name' => 'Liga a borrar',
            'invitation_code' => 'CODE123456',
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

        $championship->users()->attach($admin->id, [
            'total_points' => 0,
            'is_banned' => false,
            'position' => null,
        ]);

        $response = $this->actingAs($admin)->postJson("/api/championships/{$championship->id}/leave");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('championships', ['id' => $championship->id]);
    }
}
