<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CicloSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ciclos = [
            ['ciclo' => "Inicio de ciclo"],
            ['ciclo' => "Medio Ciclo"],
            ['ciclo' => "Fin de ciclo"],
        ];

        foreach ($ciclos as $ciclo) {
            \App\Models\Ciclo::create($ciclo);
        }
    }

}
