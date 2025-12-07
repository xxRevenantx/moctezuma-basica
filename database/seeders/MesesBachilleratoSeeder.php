<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class MesesBachilleratoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $meses = [[
            'meses' => 'AGOSTO/ENERO',
            'meses_corto' => 'AGO/ENE',
        ], [
            'meses' => 'FEBRERO/AGOSTO',
            'meses_corto' => 'FEB/AGO',
        ],
        ];

        foreach ($meses as $mes) {
            \App\Models\MesesBachillerato::create($mes);
        }

    }
}
