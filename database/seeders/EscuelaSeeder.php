<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class EscuelaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $escuela = [
            'nombre' => 'Centro Universitario Moctezuma A.C.',
            'calle' => 'Francisco I. Madero Ote.',
            'no_exterior' => '800',
            'no_interior' => null,
            'colonia' => 'Esquipula',
            'codigo_postal' => '40665',
            'ciudad' => 'Altamirano',
            'municipio' => 'Pungarabato',
            'estado' => 'Guerrero',
            'telefono' => '7676880774',
            'correo' => 'centrouniversitariomoctezuma@gmail.com',
            'pagina_web' => 'https://centrouniversitariomoctezuma.com',
            'lema' => '2025, Año de la mujer indígena',
        ];

        \App\Models\Escuela::create($escuela);
    }
}
