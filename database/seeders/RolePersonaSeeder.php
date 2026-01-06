<?php

namespace Database\Seeders;

use App\Models\RolePersona;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class RolePersonaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

       $roles = [
    // ====== Académicos / Docencia ======
    [
        'nombre' => 'Maestro(a) frente a grupo',
        'slug' => 'maestro_frente_a_grupo',
        'descripcion' => 'Docente titular frente a grupo',
    ],
    [
        'nombre' => 'Docente',
        'slug' => 'docente',
        'descripcion' => 'Profesor frente a grupo (genérico)',

    ],
    [
        'nombre' => 'Tutor',
        'slug' => 'tutor',
        'descripcion' => 'Tutor del grupo (si lo manejas como rol separado)',

    ],
    [
        'nombre' => 'Maestro de Inglés',
        'slug' => 'maestro_ingles',
        'descripcion' => 'Docente de inglés',

    ],
    [
        'nombre' => 'Docente de Educación Física',
        'slug' => 'docente_educacion_fisica',
        'descripcion' => 'Docente de educación física',

    ],
    [
        'nombre' => 'Docente de Artes',
        'slug' => 'docente_artes',
        'descripcion' => 'Docente de artes',

    ],
    [
        'nombre' => 'Docente de Computación',
        'slug' => 'docente_computacion',
        'descripcion' => 'Docente de computación',

    ],


    // ====== Directivos ======
    [
        'nombre' => 'Director(a) con grupo',
        'slug' => 'director_con_grupo',
        'descripcion' => 'Director(a) que también atiende grupo',

    ],
    [
        'nombre' => 'Director(a) sin grupo',
        'slug' => 'director_sin_grupo',
        'descripcion' => 'Director(a) solo con funciones directivas',

    ],
    [
        'nombre' => 'Subdirector de la escuela',
        'slug' => 'subdirector_escuela',
        'descripcion' => 'Subdirección del plantel',

    ],
    [
        'nombre' => 'Asesor Técnico Pedagógico',
        'slug' => 'asesor_tecnico_pedagogico',
        'descripcion' => 'ATP (asesoría/acompañamiento pedagógico)',

    ],
    [
        'nombre' => 'Prefecto',
        'slug' => 'prefecto',
        'descripcion' => 'Prefectura (secundaria/bachillerato)',

    ],

    [
        'nombre' => 'Coordinador Académico',
        'slug' => 'coordinador_academico',
        'descripcion' => 'Coordinación académica del nivel',

    ],

    // ====== Administrativos ======
    [
        'nombre' => 'Administrativo',
        'slug' => 'administrativo',
        'descripcion' => 'Personal administrativo (genérico)',

    ],
    [
        'nombre' => 'Administrativo (personal de apoyo administrativo y de servicio)',
        'slug' => 'administrativo_apoyo_servicio',
        'descripcion' => 'Personal de apoyo administrativo y de servicio',

    ],
    [
        'nombre' => 'Administrativo con funciones de control escolar',
        'slug' => 'administrativo_control_escolar',
        'descripcion' => 'Administrativo enfocado a control escolar',

    ],

    [
        'nombre' => 'Control Escolar',
        'slug' => 'control_escolar',
        'descripcion' => 'Área de control escolar (genérico)',

    ],

    [
        'nombre' => 'Conserje',
        'slug' => 'conserje',
        'descripcion' => 'Conserjería',

    ],
    [
        'nombre' => 'Intendente',
        'slug' => 'intendente',
        'descripcion' => 'Limpieza / intendencia',

    ],

    // ====== Otros ======
    [
        'nombre' => 'Otro',
        'slug' => 'otro',
        'descripcion' => 'Otro tipo de personal no clasificado',

    ],
];

foreach ($roles as $data) {
    RolePersona::updateOrCreate(
        ['slug' => $data['slug']],
        [
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,

            'status' => true,
        ]
    );
}


    }
}
