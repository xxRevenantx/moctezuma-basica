<?php

namespace App\Exports\Distribucion;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class DistribucionEscolarExport implements WithMultipleSheets
{
    use Exportable;

    public function __construct(
        private readonly Collection $bloques,
        private readonly Collection $listado,
    ) {}

    public function sheets(): array
    {
        $resumen = $this->bloques
            ->flatMap(function (array $bloque) {
                return collect($bloque['filas'])->map(fn (array $fila) => [
                    $bloque['ciclo'],
                    $fila['regional'],
                    $fila['zona'],
                    $fila['cct'],
                    $fila['nombre_ct'],
                    $fila['nivel'],
                    $fila['turno'],
                    $fila['grado'],
                    $fila['semestre'],
                    $fila['grupo'],
                    $fila['hombres'],
                    $fila['mujeres'],
                    $fila['total_historico'],
                    $fila['activos'],
                    $fila['inactivos'],
                    $fila['bajas'],
                    $fila['traslados'],
                    $fila['suspendidos'],
                    $fila['egresados'],
                    $fila['generacion'],
                    $fila['maestro'],
                    $fila['director'],
                ]);
            })
            ->values()
            ->all();

        $nominal = $this->listado
            ->map(fn (array $fila) => [
                $fila['ciclo'],
                $fila['matricula'],
                $fila['curp'],
                $fila['alumno'],
                $fila['genero'],
                $fila['nivel'],
                $fila['grado'],
                $fila['semestre'],
                $fila['grupo'],
                $fila['generacion'],
                $fila['estado_historico'],
                $fila['estado_actual'],
                $fila['fecha_alta'],
                $fila['fecha_baja'],
                $fila['motivo'],
                $fila['observaciones'],
                $fila['reconstruido'],
            ])
            ->values()
            ->all();

        $noActivos = $this->listado
            ->filter(fn (array $fila) => $fila['categoria_actual'] !== 'activo')
            ->map(fn (array $fila) => [
                $fila['matricula'],
                $fila['curp'],
                $fila['alumno'],
                $fila['generacion'],
                $fila['ciclo'],
                $fila['grado'],
                $fila['grupo'],
                $fila['estado_historico'],
                $fila['estado_actual'],
                $fila['fecha_baja'],
                $fila['motivo'],
                $fila['observaciones'],
            ])
            ->values()
            ->all();

        $egresados = $this->listado
            ->filter(fn (array $fila) => $fila['categoria_historica'] === 'egresado' || $fila['categoria_actual'] === 'egresado')
            ->map(fn (array $fila) => [
                $fila['matricula'],
                $fila['curp'],
                $fila['alumno'],
                $fila['generacion'],
                $fila['ciclo'],
                $fila['grado'],
                $fila['grupo'],
                $fila['estado_historico'],
                $fila['estado_actual'],
            ])
            ->values()
            ->all();

        return [
            new DistribucionEscolarSheet(
                'Resumen',
                [
                    'Referencia', 'Regional', 'Zona', 'CCT', 'Nombre CT', 'Nivel', 'Turno', 'Grado', 'Semestre', 'Grupo',
                    'Hombres', 'Mujeres', 'Total histórico', 'Activos', 'Inactivos',
                    'Bajas', 'Traslados', 'Suspendidos', 'Egresados', 'Generación',
                    'Maestro', 'Director',
                ],
                $resumen,
            ),
            new DistribucionEscolarSheet(
                'Listado nominal',
                [
                    'Referencia', 'Matrícula', 'CURP', 'Alumno', 'Género', 'Nivel',
                    'Grado', 'Semestre', 'Grupo', 'Generación', 'Estado histórico',
                    'Estado actual', 'Fecha de alta', 'Fecha de baja/término', 'Motivo',
                    'Observaciones', 'Dato reconstruido',
                ],
                $nominal,
            ),
            new DistribucionEscolarSheet(
                'Ya no están',
                [
                    'Matrícula', 'CURP', 'Alumno', 'Generación', 'Referencia',
                    'Grado', 'Grupo', 'Estado histórico', 'Estado actual', 'Fecha de baja/término',
                    'Motivo', 'Observaciones',
                ],
                $noActivos,
            ),
            new DistribucionEscolarSheet(
                'Egresados',
                [
                    'Matrícula', 'CURP', 'Alumno', 'Generación', 'Referencia',
                    'Grado', 'Grupo', 'Estado histórico', 'Estado actual',
                ],
                $egresados,
            ),
        ];
    }
}
