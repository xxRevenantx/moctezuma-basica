<?php

namespace Database\Seeders;

use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GrupoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
     public function run(): void
    {
        $nombresGrupos = ['A', 'B', 'C'];

        $niveles = Nivel::query()->select('id', 'slug')->get();

        foreach ($niveles as $nivel) {

            // Tomamos la última generación activa del nivel (por anio_ingreso)
            $generacion = Generacion::query()
                ->where('nivel_id', $nivel->id)
                ->where('status', 1)
                ->orderByDesc('anio_ingreso')
                ->first();

            if (! $generacion) {
                // Si aún no hay generaciones para ese nivel, no creamos grupos
                continue;
            }

            $grados = Grado::query()
                ->where('nivel_id', $nivel->id)
                ->orderBy('orden')
                ->get();

            foreach ($grados as $grado) {

                $esBachillerato = ($nivel->slug === 'bachillerato');

                if ($esBachillerato) {
                    // En bachillerato: grupos por semestre
                    $semestres = Semestre::query()
                        ->where('grado_id', $grado->id)
                        ->orderBy('numero')
                        ->get();

                    foreach ($semestres as $semestre) {
                        foreach ($nombresGrupos as $nombre) {
                            Grupo::updateOrCreate(
                                [
                                    'nivel_id'      => $nivel->id,
                                    'grado_id'      => $grado->id,
                                    'generacion_id' => $generacion->id,
                                    'semestre_id'   => $semestre->id,
                                    'nombre'        => $nombre,
                                ],
                                []
                            );
                        }
                    }
                } else {
                    // Preescolar/Primaria/Secundaria: semestre_id = null
                    foreach ($nombresGrupos as $nombre) {
                        Grupo::updateOrCreate(
                            [
                                'nivel_id'      => $nivel->id,
                                'grado_id'      => $grado->id,
                                'generacion_id' => $generacion->id,
                                'semestre_id'   => null,
                                'nombre'        => $nombre,
                            ],
                            []
                        );
                    }
                }
            }
        }
    }
}
