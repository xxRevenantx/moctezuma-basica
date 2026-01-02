<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class GradoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $grados =[
            ['nivel_id' => 1, 'nombre' => '1', 'slug' => 'primero_preescolar'],
            ['nivel_id' => 1, 'nombre' => '2', 'slug' => 'segundo_preescolar'],
            ['nivel_id' => 1, 'nombre' => '3', 'slug' => 'tercero_preescolar'],

            ['nivel_id' => 2, 'nombre' => '1', 'slug' => 'primero_primaria'],
            ['nivel_id' => 2, 'nombre' => '2', 'slug' => 'segundo_primaria'],
            ['nivel_id' => 2, 'nombre' => '3', 'slug' => 'tercero_primaria'],
            ['nivel_id' => 2, 'nombre' => '4', 'slug' => 'cuarto_primaria'],
            ['nivel_id' => 2, 'nombre' => '5', 'slug' => 'quinto_primaria'],
            ['nivel_id' => 2, 'nombre' => '6', 'slug' => 'sexto_primaria'],

            ['nivel_id' => 3, 'nombre' => '1', 'slug' => 'primero_secundaria'],
            ['nivel_id' => 3, 'nombre' => '2', 'slug' => 'segundo_secundaria'],
            ['nivel_id' => 3, 'nombre' => '3', 'slug' => 'tercero_secundaria'],

            ['nivel_id' => 4, 'nombre' => '1', 'slug' => 'primero_bachillerato'],
            ['nivel_id' => 4, 'nombre' => '2', 'slug' => 'segundo_bachillerato'],
            ['nivel_id' => 4, 'nombre' => '3', 'slug' => 'tercero_bachillerato'],
        ];
        foreach ($grados as $grado) {
            \App\Models\Grado::create($grado);
        }
    }
}
