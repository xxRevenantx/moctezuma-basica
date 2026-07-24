<?php

namespace Tests\Unit;

use App\Support\Documentos\RangoPaginas;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RangoPaginasTest extends TestCase
{
    #[DataProvider('rangosValidos')]
    public function test_interpreta_rangos_validos(string $entrada, int $maximo, array $esperado): void
    {
        $this->assertSame($esperado, RangoPaginas::interpretar($entrada, $maximo));
    }

    public static function rangosValidos(): array
    {
        return [
            'vacío' => ['', 10, []],
            'individuales' => ['1,3,5', 5, [1, 3, 5]],
            'rangos' => ['1-3,5,7-8', 8, [1, 2, 3, 5, 7, 8]],
            'inverso' => ['4-2', 5, [2, 3, 4]],
            'duplicados' => ['1-3,2,3', 5, [1, 2, 3]],
        ];
    }

    public function test_rechaza_segmentos_invalidos(): void
    {
        $this->expectException(ValidationException::class);
        RangoPaginas::interpretar('1,a,3', 5);
    }

    public function test_rechaza_paginas_fuera_del_archivo(): void
    {
        $this->expectException(ValidationException::class);
        RangoPaginas::interpretar('1-6', 5);
    }
}
