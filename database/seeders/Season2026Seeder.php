<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Season;
use App\Models\Race;

class Season2026Seeder extends Seeder
{
    public function run(): void
    {
        // Crear la season 2026
        $season = Season::create([
            'name' => 'Temporada 2026',
            'description' => 'Calendario oficial Fórmula 1 2026',
            'year' => 2026,
            'is_current_season' => true,
        ]);

        // Lista de carreras 2026 (solo fecha)
        $races = [
            ['Australia', '2026-03-08'],
            ['China', '2026-03-15'],
            ['Japón', '2026-03-29'],
            ['Bahrein', '2026-04-12'],
            ['Arabia Saudí', '2026-04-19'],
            ['Miami', '2026-05-03'],
            ['Canadá', '2026-05-24'],
            ['Mónaco', '2026-06-07'],
            ['Barcelona', '2026-06-14'],
            ['Austria', '2026-06-28'],
            ['Gran Bretaña', '2026-07-05'],
            ['Bélgica', '2026-07-19'],
            ['Hungría', '2026-07-26'],
            ['Países Bajos', '2026-08-23'],
            ['Italia', '2026-09-06'],
            ['Madrid', '2026-09-13'],
            ['Azerbaiyán', '2026-09-27'],
            ['Singapur', '2026-10-11'],
            ['Estados Unidos Austin', '2026-10-25'],
            ['México', '2026-11-01'],
            ['Brasil', '2026-11-06'],
            ['Las Vegas', '2026-11-22'],
            ['Qatar', '2026-11-29'],
            ['Abu Dhabi', '2026-12-06'],
        ];

        foreach ($races as $index => $race) {

            $raceDate = $race[1] . ' 12:00:00';
            $qualyDate = date('Y-m-d 12:00:00', strtotime($race[1] . ' -1 day'));

            Race::create([
                'season_id'       => $season->id,
                'name'            => $race[0],
                'round_number'    => $index + 1,
                'race_date'       => $raceDate,
                'qualy_date'      => $qualyDate,
                'is_result_confirmed' => false,
            ]);
        }
    }
}
