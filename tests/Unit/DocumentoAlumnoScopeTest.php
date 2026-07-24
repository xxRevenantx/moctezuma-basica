<?php

namespace Tests\Unit;

use App\Models\DocumentoAlumno;
use Tests\TestCase;

class DocumentoAlumnoScopeTest extends TestCase
{
    public function test_el_scope_disponibles_excluye_archivos_fuente(): void
    {
        $sql = DocumentoAlumno::query()->disponibles()->toSql();

        $this->assertStringContainsString('es_fuente', $sql);
        $this->assertStringContainsString('es_actual', $sql);
    }
}
