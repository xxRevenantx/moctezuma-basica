<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class NivelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $niveles = [
            ['nombre' => 'Preescolar', 'logo' => null, 'slug' => 'preescolar', 'cct' => '12PJN0226W', 'director_id' => null, 'supervisor_id' => null, 'color' => '#FBBC06'],
            ['nombre' => 'Primaria', 'logo' => null, 'slug' => 'primaria', 'cct' => '12PPR0070B', 'director_id' => null, 'supervisor_id' => null, 'color' => '#006595'],
            ['nombre' => 'Secundaria', 'logo' => null, 'slug' => 'secundaria', 'cct' => '12PES0105U', 'director_id' => null, 'supervisor_id' => null, 'color' => '#8EB03A'],
            ['nombre' => 'Bachillerato', 'logo' => null, 'slug' => 'bachillerato', 'cct' => '12PBH0071R', 'director_id' => null, 'supervisor_id' => null, 'color' => '#6571FF'],
        ];

        foreach ($niveles as $nivel) {
            \App\Models\Nivel::create($nivel);
        }
    }
}
