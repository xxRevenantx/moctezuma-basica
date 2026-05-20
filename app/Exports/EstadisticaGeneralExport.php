<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class EstadisticaGeneralExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private string $nivelNombre,
        private string $cicloEscolarTexto,
        private string $generacionTexto,
        private array $inicioCurso,
        private array $totalesInicioCurso,
        private array $medioCurso,
        private array $totalesMedioCurso,
        private array $finCurso,
        private array $totalesFinCurso,
    ) {}

    public function sheets(): array
    {
        return [
            new EstadisticaCorteSheet(
                titulo: 'Inicio de curso',
                nivelNombre: $this->nivelNombre,
                cicloEscolarTexto: $this->cicloEscolarTexto,
                generacionTexto: $this->generacionTexto,
                filas: $this->inicioCurso,
                totales: $this->totalesInicioCurso,
                bloques: [
                    'inicial' => 'INSCRIPCIÓN INICIAL',
                    'altas' => 'ALTAS',
                    'inscripcion_total' => 'INSCRIPCIÓN TOTAL',
                    'bajas' => 'BAJAS',
                    'existencia' => 'EXISTENCIA',
                ],
            ),

            new EstadisticaCorteSheet(
                titulo: 'Medio curso',
                nivelNombre: $this->nivelNombre,
                cicloEscolarTexto: $this->cicloEscolarTexto,
                generacionTexto: $this->generacionTexto,
                filas: $this->medioCurso,
                totales: $this->totalesMedioCurso,
                bloques: [
                    'inicial' => 'INSCRIPCIÓN INICIAL',
                    'altas' => 'ALTAS',
                    'inscripcion_total' => 'INSCRIPCIÓN TOTAL',
                    'bajas' => 'BAJAS',
                    'existencia' => 'EXISTENCIA',
                ],
            ),

            new EstadisticaCorteSheet(
                titulo: 'Fin de curso',
                nivelNombre: $this->nivelNombre,
                cicloEscolarTexto: $this->cicloEscolarTexto,
                generacionTexto: $this->generacionTexto,
                filas: $this->finCurso,
                totales: $this->totalesFinCurso,
                bloques: [
                    'altas' => 'ALTAS',
                    'inscripcion_total' => 'INSCRIPCIÓN TOTAL',
                    'bajas' => 'BAJAS',
                    'existencia' => 'EXISTENCIA',
                    'promovidos' => 'PROMOVIDOS',
                    'no_promovidos' => 'NO PROMOVIDOS',
                ],
            ),
        ];
    }
}
