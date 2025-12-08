<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class SemestreSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $semestres = [
            [
                'grado_id' => 13,
                'numero' => '1',
                'mes_id' => 1,
            ],
            [
                'grado_id' => 13,
                'numero' => '2',
                'mes_id' => 2,
            ],
            [
                'grado_id' =>14,
                'numero' => '3',
                'mes_id' => 1,
            ],
            [
                'grado_id' => 14,
                'numero' => '4',
                'mes_id' => 2,
            ],
            [
                'grado_id' => 15,
                'numero' => '5',
                'mes_id' => 1,
            ],
            [
                'grado_id' => 15,
                'numero' => '6',
                'mes_id' => 2,
            ],
        ];
        foreach ($semestres as $semestre) {
            \App\Models\Semestre::create($semestre);
        }
    }
}
