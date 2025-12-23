<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Persona>
 */
class PersonaFactory extends Factory
{
    public function definition(): array
    {
        $nombre  = $this->faker->firstName();
        $apPat   = $this->faker->lastName();
        $apMat   = $this->faker->boolean(70) ? $this->faker->lastName() : null; // 70% con materno
        $genero  = $this->faker->randomElement(['H', 'M']); // ajusta si tu BD usa otro formato
        $fecha   = $this->faker->dateTimeBetween('-55 years', '-18 years')->format('Y-m-d');

        // CURP "fake" de 18 chars (no válida oficialmente, pero sirve para seed)
        $curp = strtoupper(
            $this->faker->unique()->bothify('????######??#####?')
        );
        $curp = substr($curp, 0, 18);

        // RFC opcional de 13 chars
        $rfc = $this->faker->boolean(45)
            ? strtoupper($this->faker->unique()->bothify('????######???'))
            : null;

        return [
            // ✅ SOLO estos campos
            'curp'             => $curp,
            'nombre'           => $nombre,
            'apellido_paterno' => $apPat,
            'apellido_materno' => $apMat,
            'fecha_nacimiento' => $fecha,
            'genero'           => $genero,
            'rfc'              => $rfc,
            'status'          => 1,
            'especialidad'    => 'Licenciado(a)',

            // ✅ TODO lo demás en null
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
            'estado'              => null,
            'codigo_postal'   => null,
        ];
    }
}
