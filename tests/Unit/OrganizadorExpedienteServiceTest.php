<?php

namespace Tests\Unit;

use App\Services\Expedientes\OrganizadorExpedienteService;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class OrganizadorExpedienteServiceTest extends TestCase
{
    public function test_normaliza_rotaciones_a_cuadrantes_validos(): void
    {
        $servicio = new OrganizadorExpedienteService();
        $metodo = new ReflectionMethod($servicio, 'normalizarRotacion');
        $metodo->setAccessible(true);

        $this->assertSame(0, $metodo->invoke($servicio, 360));
        $this->assertSame(270, $metodo->invoke($servicio, -90));
        $this->assertSame(0, $metodo->invoke($servicio, 91));
        $this->assertSame(0, $metodo->invoke($servicio, 181));
    }

    public function test_la_clave_de_contexto_separa_nivel_grado_grupo_y_ciclo(): void
    {
        $servicio = new OrganizadorExpedienteService();
        $metodo = new ReflectionMethod($servicio, 'claveContexto');
        $metodo->setAccessible(true);

        $clave = $metodo->invoke($servicio, [
            'tipo_documento_id' => 8,
            'nivel_id' => 3,
            'grado_id' => 12,
            'grupo_id' => 4,
            'ciclo_escolar_id' => 7,
        ]);

        $this->assertSame('8|3|12|4|7', $clave);
    }

    public function test_la_firma_cambia_al_reordenar_o_girar_paginas(): void
    {
        $servicio = new OrganizadorExpedienteService();
        $metodo = new ReflectionMethod($servicio, 'firmaGrupo');
        $metodo->setAccessible(true);

        $base = new Collection([
            ['fuente_id' => 1, 'pagina' => 1, 'orden' => 1, 'rotacion' => 0, 'contexto_clave' => '1|0|0|0|0'],
            ['fuente_id' => 1, 'pagina' => 2, 'orden' => 2, 'rotacion' => 0, 'contexto_clave' => '1|0|0|0|0'],
        ]);
        $girada = new Collection([
            ['fuente_id' => 1, 'pagina' => 1, 'orden' => 1, 'rotacion' => 90, 'contexto_clave' => '1|0|0|0|0'],
            ['fuente_id' => 1, 'pagina' => 2, 'orden' => 2, 'rotacion' => 0, 'contexto_clave' => '1|0|0|0|0'],
        ]);
        $reordenada = new Collection([
            ['fuente_id' => 1, 'pagina' => 1, 'orden' => 2, 'rotacion' => 0, 'contexto_clave' => '1|0|0|0|0'],
            ['fuente_id' => 1, 'pagina' => 2, 'orden' => 1, 'rotacion' => 0, 'contexto_clave' => '1|0|0|0|0'],
        ]);

        $firmaBase = $metodo->invoke($servicio, $base);
        $this->assertNotSame($firmaBase, $metodo->invoke($servicio, $girada));
        $this->assertNotSame($firmaBase, $metodo->invoke($servicio, $reordenada));
    }

    public function test_la_firma_completa_detecta_cambios_de_clasificacion_sin_depender_del_orden_del_arreglo(): void
    {
        $servicio = new OrganizadorExpedienteService();
        $metodo = new ReflectionMethod($servicio, 'firmaAsignacionesCompleta');
        $metodo->setAccessible(true);

        $base = [
            [
                'fuente_id' => 5,
                'pagina' => 2,
                'tipo_documento_id' => 3,
                'contexto_clave' => '3|0|0|0|0',
                'orden' => 2,
                'rotacion' => 0,
            ],
            [
                'fuente_id' => 5,
                'pagina' => 1,
                'tipo_documento_id' => 3,
                'contexto_clave' => '3|0|0|0|0',
                'orden' => 1,
                'rotacion' => 0,
            ],
        ];

        $mismoContenidoOtroOrden = array_reverse($base);
        $reclasificada = $base;
        $reclasificada[0]['tipo_documento_id'] = 4;
        $reclasificada[0]['contexto_clave'] = '4|0|0|0|0';

        $firmaBase = $metodo->invoke($servicio, $base);

        $this->assertSame($firmaBase, $metodo->invoke($servicio, $mismoContenidoOtroOrden));
        $this->assertNotSame($firmaBase, $metodo->invoke($servicio, $reclasificada));
    }
}
