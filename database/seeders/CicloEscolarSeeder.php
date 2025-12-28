<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class CicloEscolarSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $ciclos = [
            ['inicio_anio' => 2015, 'fin_anio' => 2016],
            ['inicio_anio' => 2016, 'fin_anio' => 2017],
            ['inicio_anio' => 2017, 'fin_anio' => 2018],
            ['inicio_anio' => 2018, 'fin_anio' => 2019],
            ['inicio_anio' => 2019, 'fin_anio' => 2020],
            ['inicio_anio' => 2020, 'fin_anio' => 2021],
            ['inicio_anio' => 2021, 'fin_anio' => 2022],
            ['inicio_anio' => 2022, 'fin_anio' => 2023],
            ['inicio_anio' => 2023, 'fin_anio' => 2024],
            ['inicio_anio' => 2024, 'fin_anio' => 2025],
            ['inicio_anio' => 2025, 'fin_anio' => 2026],
        ];

        foreach ($ciclos as $ciclo) {
            \App\Models\CicloEscolar::create($ciclo);
        }
    }
}
