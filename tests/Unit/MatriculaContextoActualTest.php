<?php

namespace Tests\Unit;

use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use Tests\TestCase;

class MatriculaContextoActualTest extends TestCase
{
    public function test_el_listado_actual_prioriza_la_matricula_guardada_en_la_inscripcion(): void
    {
        $alumno = new Inscripcion(['matricula' => '24120143132', 'ciclo_escolar_id' => 3]);
        $contexto = new InscripcionCiclo([
            'matricula' => '2024BACHICAPC44',
            'ciclo_escolar_id' => 3,
            'estado' => 'en_curso',
        ]);

        $this->assertSame('24120143132', $alumno->matriculaParaContexto($contexto));
    }

    public function test_el_historial_de_ciclos_anteriores_conserva_su_matricula(): void
    {
        $alumno = new Inscripcion(['matricula' => '24120143132', 'ciclo_escolar_id' => 3]);
        $contexto = new InscripcionCiclo([
            'matricula' => '2024BACHICAPC44',
            'ciclo_escolar_id' => 2,
            'estado' => 'cerrado',
        ]);

        $this->assertSame('2024BACHICAPC44', $alumno->matriculaParaContexto($contexto));
    }

    public function test_no_confunde_un_ciclo_en_curso_anterior_con_el_ciclo_actual(): void
    {
        $alumno = new Inscripcion(['matricula' => '24120143132', 'ciclo_escolar_id' => 3]);
        $contexto = new InscripcionCiclo([
            'matricula' => '2024BACHICAPC44',
            'ciclo_escolar_id' => 2,
            'estado' => 'en_curso',
        ]);

        $this->assertSame('2024BACHICAPC44', $alumno->matriculaParaContexto($contexto));
    }

    public function test_sin_historial_utiliza_la_matricula_de_la_inscripcion(): void
    {
        $alumno = new Inscripcion(['matricula' => '24120143132']);

        $this->assertSame('24120143132', $alumno->matriculaParaContexto(null));
    }
}
