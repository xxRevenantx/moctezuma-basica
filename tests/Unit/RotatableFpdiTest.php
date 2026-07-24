<?php

namespace Tests\Unit;

use App\Support\Pdf\RotatableFpdi;
use ReflectionMethod;
use Tests\TestCase;

class RotatableFpdiTest extends TestCase
{
    public function test_el_identificador_de_plantilla_acepta_entero_o_cadena(): void
    {
        $metodo = new ReflectionMethod(RotatableFpdi::class, 'placeTemplateRotated');
        $tipo = (string) $metodo->getParameters()[0]->getType();

        $this->assertStringContainsString('int', $tipo);
        $this->assertStringContainsString('string', $tipo);
    }
}
