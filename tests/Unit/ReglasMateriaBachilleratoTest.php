<?php

namespace Tests\Unit;

use App\Support\ReglasMateriaBachillerato;
use PHPUnit\Framework\TestCase;

class ReglasMateriaBachilleratoTest extends TestCase
{
    public function test_materia_oficial_es_promediable(): void
    {
        $materia = [
            'calificable' => true,
            'extra' => false,
            'receso' => false,
        ];

        $this->assertTrue(ReglasMateriaBachillerato::esPromediable($materia));
        $this->assertTrue(ReglasMateriaBachillerato::esCapturable($materia));
    }

    public function test_materia_extra_es_capturable_pero_no_promediable(): void
    {
        $materia = [
            'calificable' => true,
            'extra' => true,
            'receso' => false,
        ];

        $this->assertFalse(ReglasMateriaBachillerato::esPromediable($materia));
        $this->assertTrue(ReglasMateriaBachillerato::esExtraInformativa($materia));
        $this->assertTrue(ReglasMateriaBachillerato::esCapturable($materia));
    }

    public function test_receso_no_es_capturable_ni_promediable(): void
    {
        $materia = [
            'calificable' => true,
            'extra' => true,
            'receso' => true,
        ];

        $this->assertFalse(ReglasMateriaBachillerato::esPromediable($materia));
        $this->assertFalse(ReglasMateriaBachillerato::esExtraInformativa($materia));
        $this->assertFalse(ReglasMateriaBachillerato::esCapturable($materia));
    }

    public function test_normaliza_extra_de_bachillerato_sin_hacerla_oficial(): void
    {
        $resultado = ReglasMateriaBachillerato::normalizarAtributos([
            'nivel_id' => 4,
            'calificable' => false,
            'extra' => true,
            'receso' => false,
            'participa_en_calificacion_oficial' => true,
        ]);

        $this->assertTrue($resultado['calificable']);
        $this->assertTrue($resultado['extra']);
        $this->assertFalse($resultado['receso']);
        $this->assertFalse($resultado['participa_en_calificacion_oficial']);
    }
}
