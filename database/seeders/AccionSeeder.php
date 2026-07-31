<?php

namespace Database\Seeders;

use App\Models\Accion;
use Illuminate\Database\Seeder;

class AccionSeeder extends Seeder
{
    public function run(): void
    {
        $acciones = [
            ['accion' => 'Generales', 'slug' => 'generales', 'orden' => 1],
            ['accion' => 'Matrícula', 'slug' => 'matricula', 'orden' => 2],
            ['accion' => 'Alumnos no vigentes', 'slug' => 'alumnos-no-vigentes', 'orden' => 3],
            ['accion' => 'Asignación de materias', 'slug' => 'asignacion-de-materias', 'orden' => 4],
            ['accion' => 'Horarios', 'slug' => 'horarios', 'orden' => 5],
            ['accion' => 'Calificaciones', 'slug' => 'calificaciones', 'orden' => 6],
            ['accion' => 'Fichas', 'slug' => 'fichas', 'orden' => 7],
            ['accion' => 'Bajas', 'slug' => 'bajas', 'orden' => 8],
        ];

        foreach ($acciones as $accion) {
            Accion::query()->updateOrCreate(
                ['slug' => $accion['slug']],
                $accion
            );
        }
    }
}
