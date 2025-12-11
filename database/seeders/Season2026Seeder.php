<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Season;
use App\Models\Race;
use App\Models\Driver;
use App\Models\RaceResult;

class Season2026Seeder extends Seeder
{
    public function run(): void
    {
        // Crear la season 2026
        $season = Season::create([
            'name' => 'Temporada 2026',
            'description' => 'Calendario oficial Formula 1 2026',
            'year' => 2026,
            'is_current_season' => true,
        ]);

        // Lista de carreras 2026 (solo fecha)
        $races = [
            ['Australia', '2026-03-08'],
            ['China', '2026-03-15'],
            ['Japon', '2026-03-29'],
            ['Bahrein', '2026-04-12'],
            ['Arabia Saudi', '2026-04-19'],
            ['Miami', '2026-05-03'],
            ['Canada', '2026-05-24'],
            ['Monaco', '2026-06-07'],
            ['Barcelona', '2026-06-14'],
            ['Austria', '2026-06-28'],
            ['Gran Bretana', '2026-07-05'],
            ['Belgica', '2026-07-19'],
            ['Hungria', '2026-07-26'],
            ['Paises Bajos', '2026-08-23'],
            ['Italia', '2026-09-06'],
            ['Madrid', '2026-09-13'],
            ['Azerbaiyan', '2026-09-27'],
            ['Singapur', '2026-10-11'],
            ['Estados Unidos Austin', '2026-10-25'],
            ['Mexico', '2026-11-01'],
            ['Brasil', '2026-11-06'],
            ['Las Vegas', '2026-11-22'],
            ['Qatar', '2026-11-29'],
            ['Abu Dhabi', '2026-12-06'],
        ];

        // Drivers para componer resultados; se barajan por carrera
        $driverIds = Driver::orderBy('id')->pluck('id')->all();
        $driverCount = count($driverIds);

        foreach ($races as $index => $race) {

            $raceDate = $race[1] . ' 12:00:00';
            $qualyDate = date('Y-m-d 12:00:00', strtotime($race[1] . ' -1 day'));

            $raceModel = Race::create([
                'season_id'            => $season->id,
                'name'                 => $race[0],
                'round_number'         => $index + 1,
                'race_date'            => $raceDate,
                'qualy_date'           => $qualyDate,
                // Primeras 7 carreras (indices 0-6) con resultado confirmado
                'is_result_confirmed'  => $index < 7,
            ]);

            // Para las primeras 7 carreras, generar resultados completos (aleatorios)
            if ($index < 7 && $driverCount > 0) {
                $grid = $driverIds;
                shuffle($grid);

                // Elegir aleatoriamente quién hace la vuelta rápida (entre top 10 o toda la parrilla si <10)
                $fastestIndexPool = min(9, $driverCount - 1);
                $fastestLapIndex = mt_rand(0, $fastestIndexPool);

                foreach ($grid as $pos => $driverId) {
                    RaceResult::create([
                        'race_id'       => $raceModel->id,
                        'driver_id'     => $driverId,
                        'position'      => $pos + 1,
                        'is_pole'       => $pos === 0,
                        'fastest_lap'   => $pos === $fastestLapIndex,
                        'is_last_place' => $pos === $driverCount - 1,
                    ]);
                }
            }
        }
    }
}
