<?php

namespace Tests\Unit;

use App\Services\AsistenciaBimestralService;
use Tests\TestCase;

class AsistenciaBimestralServiceTest extends TestCase
{
    public function test_genera_40_dias_de_lunes_a_viernes_en_ocho_semanas(): void
    {
        $calendario = app(AsistenciaBimestralService::class)->construir('2026-09-09');

        $this->assertSame('2026-09-07', $calendario['inicio']);
        $this->assertSame('2026-10-30', $calendario['fin']);
        $this->assertCount(8, $calendario['semanas']);
        $this->assertSame(40, $calendario['totalDias']);
        $this->assertSame(40, array_sum(array_column($calendario['meses'], 'dias')));
        $this->assertSame(['2026-09', '2026-10'], array_column($calendario['meses'], 'clave'));

        foreach ($calendario['semanas'] as $semana) {
            $this->assertCount(5, $semana['dias']);
            $this->assertSame(['L', 'M', 'M', 'J', 'V'], array_column($semana['dias'], 'dia'));
        }
    }

    public function test_separa_por_mes_calendario_si_ocho_semanas_alcanzan_tres_meses(): void
    {
        $calendario = app(AsistenciaBimestralService::class)->construir('2026-10-13');

        $this->assertSame('2026-10-12', $calendario['inicio']);
        $this->assertSame('2026-12-04', $calendario['fin']);
        $this->assertSame(['2026-10', '2026-11', '2026-12'], array_column($calendario['meses'], 'clave'));
        $this->assertSame(40, array_sum(array_column($calendario['meses'], 'dias')));
    }
}
