<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DirectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $directivos = [
        [
            'titulo'           => 'M.C.',
            'nombre'           => 'José Rubén',
            'apellido_paterno' => 'Solórzano',
            'apellido_materno' => 'Carbajal',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'rector',
            'identificador'    => 'rector',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'M.S.P.',
            'nombre'           => 'Silvia',
            'apellido_paterno' => 'Agustín',
            'apellido_materno' => 'Magaña',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'Directora General',
            'identificador'    => 'directora-general',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'M.C.',
            'nombre'           => 'Angélica',
            'apellido_paterno' => 'Ocampo',
            'apellido_materno' => 'Agustín',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'Directora',
            'identificador'    => 'directora',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'Lic.',
            'nombre'           => 'Mariano',
            'apellido_paterno' => 'Marcelo',
            'apellido_materno' => 'Mendez',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'subdirector',
            'identificador'    => 'subdirector',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'Profr.',
            'nombre'           => 'Nahún',
            'apellido_paterno' => 'Rivera',
            'apellido_materno' => 'Milián',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'Supervisor Escolar',
            'identificador'    => 'supervisor-02',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'Profr.',
            'nombre'           => 'Adelfo',
            'apellido_paterno' => 'Martínez',
            'apellido_materno' => 'Martínez',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'Supervisor Escolar',
            'identificador'    => 'supervisor-030',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
        [
            'titulo'           => 'Dra.',
            'nombre'           => 'Yamileth',
            'apellido_paterno' => 'García',
            'apellido_materno' => 'Durán',
            'curp'             => null,
            'rfc'              => null,
            'cargo'            => 'Supervisora Escolar',
            'identificador'    => 'supervisora-137',
            'zona_escolar'     => null,
            'telefono'         => null,
            'correo'           => null,
            'status'           => true,
        ],
    ];

    foreach ($directivos as $directivo) {
        \App\Models\Director::create($directivo);
    }
    }
}
