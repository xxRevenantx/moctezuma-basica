<?php

namespace App\Exports\Inscripciones;

use App\Models\Ciclo;
use App\Models\Grupo;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CatalogosInscripcionesSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        $filas = [[
            'clave_grupo',
            'ciclo_escolar',
            'nivel',
            'grado',
            'semestre',
            'generacion',
            'grupo',
            'estado',
            'alumnos_activos',
            '',
            'momento_ingreso_id',
            'momento_ingreso',
        ]];

        $grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'nivel:id,nombre',
                'grado:id,nombre',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->withCount([
                'inscripciones as alumnos_activos_count' => fn ($query) => $query->where('activo', true),
            ])
            ->whereNotNull('ciclo_escolar_id')
            ->whereNotNull('clave')
            ->where('estado', 'activo')
            ->orderByDesc('ciclo_escolar_id')
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('asignacion_grupo_id')
            ->get();

        $momentosIngreso = Ciclo::query()
            ->orderBy('id')
            ->get(['id', 'ciclo']);

        $maximo = max($grupos->count(), $momentosIngreso->count(), 1);

        for ($i = 0; $i < $maximo; $i++) {
            $grupo = $grupos[$i] ?? null;
            $momento = $momentosIngreso[$i] ?? null;

            $filas[] = [
                $grupo?->clave,
                $grupo?->cicloEscolar
                    ? "{$grupo->cicloEscolar->inicio_anio} - {$grupo->cicloEscolar->fin_anio}"
                    : '',
                $grupo?->nivel?->nombre,
                $grupo?->grado?->nombre,
                $grupo?->semestre ? $grupo->semestre->numero . '° semestre' : '',
                $grupo?->generacion
                    ? "{$grupo->generacion->anio_ingreso} - {$grupo->generacion->anio_egreso}"
                    : '',
                $grupo?->asignacionGrupo?->nombre,
                $grupo?->estado,
                $grupo ? (int) $grupo->alumnos_activos_count : '',
                '',
                $momento?->id,
                $momento?->ciclo,
            ];
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:I1');

                $hoja->getStyle('A1:L1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                ]);

                $hoja->getStyle('A:L')->getAlignment()->setVertical('center');
            },
        ];
    }
}
