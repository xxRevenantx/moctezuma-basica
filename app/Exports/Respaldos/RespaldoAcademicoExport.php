<?php

namespace App\Exports\Respaldos;

use App\Support\RespaldoAcademico;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class RespaldoAcademicoExport implements WithMultipleSheets
{
    /** @var array<string,mixed> */
    private array $configuracion;

    public function __construct(private readonly string $tipo)
    {
        $this->configuracion = RespaldoAcademico::configuracion($this->tipo);

        foreach (array_keys($this->configuracion['tablas']) as $tabla) {
            if (!Schema::hasTable($tabla)) {
                throw new RuntimeException(
                    "No existe la tabla {$tabla}. Ejecuta primero todas las migraciones del historial académico."
                );
            }
        }
    }

    public function sheets(): array
    {
        $hojas = [
            new InstruccionesRespaldoSheet($this->configuracion),
            new MetadataRespaldoSheet($this->configuracion),
        ];

        foreach ($this->configuracion['tablas'] as $tabla => $datos) {
            $hojas[] = new TablaRespaldoSheet(
                tabla: $tabla,
                tituloHoja: $datos['hoja'],
                descripcion: $datos['descripcion'],
            );
        }

        return $hojas;
    }
}
