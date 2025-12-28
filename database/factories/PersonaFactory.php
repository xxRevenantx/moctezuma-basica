<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PersonaFactory extends Factory
{
    public function definition(): array
    {
        $nombre  = $this->faker->firstName();
        $apPat   = $this->faker->lastName();
        $apMat   = $this->faker->boolean(70) ? $this->faker->lastName() : null; // 70% con materno
        $genero  = $this->faker->randomElement(['H', 'M']); // H=Hombre, M=Mujer (según tu BD)
        $fecha   = $this->faker->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d');

        // CURP "fake"
        $curp = strtoupper($this->faker->unique()->bothify('????######??#####?'));
        $curp = substr($curp, 0, 18);

        // RFC opcional
        $rfc = $this->faker->boolean(45)
            ? strtoupper($this->faker->unique()->bothify('????######???'))
            : null;

        // ✅ Títulos por género
        $titulosH = ['Lic.', 'Profr.', 'Dr.', 'C.P.', 'Ing.', 'Arq.', 'Mtro.', 'M.S.P.', 'M.C.'];
        $titulosM = ['Lic.', 'Profra.', 'Dra.', 'C.P.', 'Ing.', 'Arq.', 'Mtra.', 'M.S.P.', 'M.C.'];

        $titulo = $genero === 'M'
            ? $this->faker->randomElement($titulosM)
            : $this->faker->randomElement($titulosH);

        return [
            'titulo'           => $titulo,
            'curp'             => $curp,
            'nombre'           => $nombre,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
            'fecha_nacimiento' => $fecha,
            'genero'           => $genero,
            'rfc'              => $rfc,
            'status'           => 1,

            // (opcional) también puedes ajustar especialidad por género:
            'especialidad'     => $genero === 'M' ? 'Licenciada' : 'Licenciado',

            'foto'            => null,
            'correo'          => null,
            'telefono_movil'  => null,
            'telefono_fijo'   => null,
            'grado_estudios'  => null,

            'calle'           => null,
            'numero_exterior' => null,
            'numero_interior' => null,
            'colonia'         => null,
            'municipio'       => null,
            'estado'          => null,
            'codigo_postal'   => null,
        ];
    }
}
