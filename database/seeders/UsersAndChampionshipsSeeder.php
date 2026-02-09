<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Season;
use App\Models\Championship;
use App\Models\ScoringSystem;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersAndChampionshipsSeeder extends Seeder
{
    public function run(): void
    {
         
        $randomHelmet = function () {
            $num = str_pad(rand(1, 20), 2, '0', STR_PAD_LEFT);
            $base = rtrim(config('app.url'), '/');
            return "{$base}/images/helmet_icons/{$num}.png";
        };

         
        $admin = User::create([
            'name'       => 'Admin',
            'email'      => 'admin@admin.com',
            'password'   => Hash::make('admin'),
            'is_admin'   => true,
            'country'    => '🇪🇸',
            'profile_pic'=> $randomHelmet(),
        ]);

         
        $users = [];

        for ($i = 1; $i <= 30; $i++) {
            $username = sprintf('Usuario%02d', $i);

            $users[$i] = User::create([
                'name'        => $username,
                'email'       => strtolower($username) . '@example.com',
                'password'    => Hash::make('password'),
                'is_admin'    => false,
                'country'     => '🇪🇸',
                'profile_pic' => $randomHelmet(),
            ]);
        }

         
        $userChunks = array_chunk(array_values($users), 10);

         
        $season = Season::where('year', 2026)->firstOrFail();

         
        $defaultScoring = [
            'points_p1'          => 10,
            'points_p2'          => 6,
            'points_p3'          => 4,
            'points_p4'          => 3,
            'points_p5'          => 2,
            'points_p6'          => 1,
            'points_pole'        => 3,
            'points_fastest_lap' => 1,
            'points_last_place'  => 3,
        ];

         
        $createChampionship = function (string $name, User $adminUser, array $members) use ($season, $defaultScoring) {

            $championship = Championship::create([
                'name'            => $name,
                'status'          => 'active',
                'invitation_code' => Str::upper(Str::random(10)),
                'season_id'       => $season->id,
                'admin_id'        => $adminUser->id,
            ]);

            ScoringSystem::create(array_merge(
                ['championship_id' => $championship->id],
                $defaultScoring
            ));

            foreach ($members as $user) {
                $championship->users()->attach($user->id, [
                    'is_banned'    => false,
                    'total_points' => 0,
                    'position'     => null,
                ]);
            }

            return $championship;
        };

         
        $createChampionship(
            'Liga de la clase',
            $users[1],
            $userChunks[0] ?? []
        );

         
        $createChampionship(
            'Torneo de los becarios',
            $users[11] ?? $users[2],
            $userChunks[1] ?? []
        );

         
        $createChampionship(
            'Campeonato de los colegas',
            $users[21] ?? $users[3],
            $userChunks[2] ?? []
        );
    }
}
