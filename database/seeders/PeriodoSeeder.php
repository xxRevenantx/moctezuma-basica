<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PeriodoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $periodos = [
            ['numero' => 1, 'nombre' => 'Primer periodo', 'uso' => 'basico'],
            ['numero' => 2, 'nombre' => 'Segundo periodo', 'uso' => 'basico'],
            ['numero' => 3, 'nombre' => 'Tercer periodo', 'uso' => 'basico'],
        ];

        foreach ($periodos as $periodo) {
            \App\Models\Periodo::create($periodo);
        }
    }
}
