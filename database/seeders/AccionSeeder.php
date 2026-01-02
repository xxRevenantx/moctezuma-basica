<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class AccionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $acciones = [
            ['accion' => 'Matrícula', 'slug' => 'matricula'],
            ['accion' => 'Asignación de materias', 'slug' => 'asignacion-de-materias'],
            ['accion' => 'Horarios', 'slug' => 'horarios'],
            ['accion' => 'Calificaciones', 'slug' => 'calificaciones'],
            ['accion' => 'Bajas', 'slug' => 'bajas'],
        ];

        foreach ($acciones as $key => $value) {
            \App\Models\Accion::create($value);
        }
    }
}
