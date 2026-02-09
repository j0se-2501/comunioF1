<?php

namespace Tests\Unit;

use App\Models\Championship;
use App\Models\Season;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    private function user(): User
    {
        static $i = 1;
        $i++;

        return User::create([
            'name' => 'Tester'.$i,
            'email' => "tester{$i}@example.com",
            'password' => bcrypt('password'),
            'country' => '🇪🇸',
        ]);
    }

    private function season(): Season
    {
        return Season::forceCreate([
            'name' => 'S',
            'description' => 'Season',
            'year' => 2035,
            'is_current_season' => false,
        ]);
    }

    public function test_me_returns_authenticated_user(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->getJson('/api/me')
            ->assertOk()
            ->assertJsonFragment(['email' => $user->email]);
    }

    public function test_update_profile_changes_name_country_and_avatar(): void
    {
        $user = $this->user();

        $payload = [
            'name' => 'Nuevo',
            'country' => '🇫🇷',
            'profile_pic' => 'img.png',
        ];

        $this->actingAs($user)
            ->patchJson('/api/user', $payload)
            ->assertOk()
            ->assertJsonFragment(['name' => 'Nuevo', 'country' => '🇫🇷', 'profile_pic' => 'img.png']);

        $this->assertDatabaseHas('users', ['id' => $user->id, 'name' => 'Nuevo', 'country' => '🇫🇷', 'profile_pic' => 'img.png']);
    }

    public function test_update_password_requires_current_password(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->putJson('/api/user/password', [
                'current_password' => 'wrong',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ])
            ->assertStatus(403);
    }

    public function test_update_password_changes_password_when_current_is_valid(): void
    {
        $user = $this->user();

        $this->actingAs($user)
            ->putJson('/api/user/password', [
                'current_password' => 'password',
                'new_password' => 'newpass123',
                'new_password_confirmation' => 'newpass123',
            ])
            ->assertOk();

        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_championships_returns_memberships(): void
    {
        $user = $this->user();
        $season = $this->season();

        $champ = Championship::forceCreate([
            'admin_id' => $user->id,
            'season_id' => $season->id,
            'name' => 'Liga',
            'invitation_code' => 'ABC123XYZ',
            'status' => 'active',
        ]);
        $champ->users()->attach($user->id, ['is_banned' => false, 'total_points' => 0, 'position' => null]);

        $this->actingAs($user)
            ->getJson('/api/user/championships')
            ->assertOk()
            ->assertJsonFragment(['name' => 'Liga']);
    }

    public function test_show_returns_public_user_data(): void
    {
        $user = $this->user();

        $this->actingAs($this->user())
            ->getJson("/api/users/{$user->id}")
            ->assertOk()
            ->assertJsonMissing(['email'])  
            ->assertJsonFragment(['id' => $user->id, 'name' => $user->name]);
    }
}
