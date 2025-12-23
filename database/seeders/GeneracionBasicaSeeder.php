<?php

namespace Database\Seeders;

use App\Models\Generacion;
use App\Models\Nivel;
use Carbon\Carbon;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GeneracionBasicaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        $niveles = Nivel::query()->select('id', 'slug')->get();

        $anioActual = (int) Carbon::now()->year;
        $inicioBase = $anioActual - 4; // 5 generaciones: (año-4) .. (año)

        foreach ($niveles as $nivel) {

            $duracion = match ($nivel->slug) {
                'primaria' => 6,
                'preescolar', 'secundaria', 'bachillerato' => 3,
                default => 3, // fallback por si aparece otro nivel
            };

            for ($i = 0; $i < 5; $i++) {
                $anioIngreso = $inicioBase + $i;
                $anioEgreso  = $anioIngreso + $duracion;

                Generacion::updateOrCreate(
                    [
                        'nivel_id'     => $nivel->id,
                        'anio_ingreso' => $anioIngreso,
                        'anio_egreso'  => $anioEgreso,
                    ],
                    [
                        'status' => 1,
                    ]
                );
            }
        }
    }

    


}
