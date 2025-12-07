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
            ['nivel_id' => 1,
            'nombre'=> '1'],
            ['nivel_id' => 1,
            'nombre'=> '2'],
            ['nivel_id' => 1,
            'nombre'=> '3'],
            ['nivel_id' => 2,
            'nombre'=> '1'],
            ['nivel_id' => 2,
            'nombre'=> '2'],
            ['nivel_id' => 2,
            'nombre'=> '3'],
            ['nivel_id' => 2,
            'nombre'=> '4'],
            ['nivel_id' => 2,
            'nombre'=> '5'],
            ['nivel_id' => 2,
            'nombre'=> '6'],
            ['nivel_id' => 3,
            'nombre'=> '1'],
            ['nivel_id' => 3,
            'nombre'=> '2'],
            ['nivel_id' => 3,
            'nombre'=> '3'],
            ['nivel_id' => 4,
            'nombre'=> '1'],
            ['nivel_id' => 4,
            'nombre'=> '2'],
            ['nivel_id' => 4,
            'nombre'=> '3'],
        ];
        foreach ($grados as $grado) {
            \App\Models\Grado::create($grado);
        }
    }
}
