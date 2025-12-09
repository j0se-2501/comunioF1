<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Team;
use App\Models\Driver;

class TeamsAndDriversSeeder extends Seeder
{
    public function run(): void
    {
        $teams = [

            'Mercedes' => [
                'drivers' => [
                    [
                        'name'       => 'George Russell',
                        'short_code' => 'RUS',
                        'number'     => 63,
                        'country'    => '🇬🇧',
                    ],
                    [
                        'name'       => 'Andrea Kimi Antonelli',
                        'short_code' => 'ANT',
                        'number'     => 7,
                        'country'    => '🇮🇹',
                    ],
                ],
            ],

            'Red Bull' => [
                'drivers' => [
                    [
                        'name'       => 'Max Verstappen',
                        'short_code' => 'VER',
                        'number'     => 1,
                        'country'    => '🇳🇱',
                    ],
                    [
                        'name'       => 'Isack Hadjar',
                        'short_code' => 'HAD',
                        'number'     => 20,
                        'country'    => '🇫🇷',
                    ],
                ],
            ],

            'McLaren' => [
                'drivers' => [
                    [
                        'name'       => 'Lando Norris',
                        'short_code' => 'NOR',
                        'number'     => 4,
                        'country'    => '🇬🇧',
                    ],
                    [
                        'name'       => 'Oscar Piastri',
                        'short_code' => 'PIA',
                        'number'     => 81,
                        'country'    => '🇦🇺',
                    ],
                ],
            ],

            'Aston Martin' => [
                'drivers' => [
                    [
                        'name'       => 'Fernando Alonso',
                        'short_code' => 'ALO',
                        'number'     => 14,
                        'country'    => '🇪🇸',
                    ],
                    [
                        'name'       => 'Lance Stroll',
                        'short_code' => 'STR',
                        'number'     => 18,
                        'country'    => '🇨🇦',
                    ],
                ],
            ],

            'Alpine' => [
                'drivers' => [
                    [
                        'name'       => 'Pierre Gasly',
                        'short_code' => 'GAS',
                        'number'     => 10,
                        'country'    => '🇫🇷',
                    ],
                    [
                        'name'       => 'Franco Colapinto',
                        'short_code' => 'COL',
                        'number'     => 43,
                        'country'    => '🇦🇷',
                    ],
                ],
            ],

            'Ferrari' => [
                'drivers' => [
                    [
                        'name'       => 'Charles Leclerc',
                        'short_code' => 'LEC',
                        'number'     => 16,
                        'country'    => '🇲🇨',
                    ],
                    [
                        'name'       => 'Lewis Hamilton',
                        'short_code' => 'HAM',
                        'number'     => 44,
                        'country'    => '🇬🇧',
                    ],
                ],
            ],

            'Racing Bulls' => [
                'drivers' => [
                    [
                        'name'       => 'Liam Lawson',
                        'short_code' => 'LAW',
                        'number'     => 30,
                        'country'    => '🇳🇿',
                    ],
                    [
                        'name'       => 'Arvid Lindblad',
                        'short_code' => 'LIN',
                        'number'     => 31,
                        'country'    => '🇬🇧',
                    ],
                ],
            ],

            'Audi' => [
                'drivers' => [
                    [
                        'name'       => 'Nico Hulkenberg',
                        'short_code' => 'HUL',
                        'number'     => 27,
                        'country'    => '🇩🇪',
                    ],
                    [
                        'name'       => 'Gabriel Bortoleto',
                        'short_code' => 'BOR',
                        'number'     => 34,
                        'country'    => '🇧🇷',
                    ],
                ],
            ],

            'Haas' => [
                'drivers' => [
                    [
                        'name'       => 'Esteban Ocon',
                        'short_code' => 'OCO',
                        'number'     => 31,
                        'country'    => '🇫🇷',
                    ],
                    [
                        'name'       => 'Oliver Bearman',
                        'short_code' => 'BEA',
                        'number'     => 50,
                        'country'    => '🇬🇧',
                    ],
                ],
            ],

            'Williams' => [
                'drivers' => [
                    [
                        'name'       => 'Alex Albon',
                        'short_code' => 'ALB',
                        'number'     => 23,
                        'country'    => '🇹🇭',
                    ],
                    [
                        'name'       => 'Carlos Sainz',
                        'short_code' => 'SAI',
                        'number'     => 55,
                        'country'    => '🇪🇸',
                    ],
                ],
            ],

            'Cadillac' => [
                'drivers' => [
                    [
                        'name'       => 'Valtteri Bottas',
                        'short_code' => 'BOT',
                        'number'     => 77,
                        'country'    => '🇫🇮',
                    ],
                    [
                        'name'       => 'Sergio Pérez',
                        'short_code' => 'PER',
                        'number'     => 11,
                        'country'    => '🇲🇽',
                    ],
                ],
            ],

        ];

        foreach ($teams as $teamName => $data) {

            // Aquí solo usamos 'name' porque es lo único que existe en la tabla teams
            $team = Team::create([
                'name' => $teamName,
            ]);

            foreach ($data['drivers'] as $driver) {
                Driver::create([
                    'team_id'    => $team->id,
                    'name'       => $driver['name'],
                    'short_code' => strtoupper($driver['short_code']),
                    'number'     => $driver['number'],
                    'country'    => $driver['country'],
                ]);
            }
        }
    }
}
