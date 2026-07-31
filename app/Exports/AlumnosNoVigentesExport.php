<?php

namespace App\Exports;

use App\Services\AlumnosNoVigentesService;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AlumnosNoVigentesExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private readonly Collection $rows,
        private readonly bool $bachillerato = false,
    ) {}

    public function collection(): Collection
    {
        $service = app(AlumnosNoVigentesService::class);

        return $this->rows->values()->map(function ($alumno, int $indice) use ($service): array {
            $contexto = $service->contextoDe($alumno);
            $estatus = $service->estatusDe($contexto, $alumno);

            $fila = [
                $indice + 1,
                $service->matriculaDe($contexto, $alumno),
                $alumno->folio,
                $alumno->curp,
                trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
                $alumno->genero,
                $contexto?->generacion?->etiqueta,
                $contexto?->grado?->nombre,
            ];

            if ($this->bachillerato) {
                $fila[] = $contexto?->semestre?->numero
                    ? 'Semestre ' . $contexto->semestre->numero
                    : '—';
            }

            return array_merge($fila, [
                $contexto?->grupo?->asignacionGrupo?->nombre ?? '—',
                $service->etiquetaEstatus($estatus),
                $service->etiquetaCategoria($service->categoriaDe($contexto, $alumno)),
                optional($service->fechaIngresoDe($contexto))->format('d/m/Y'),
                optional($service->fechaSalidaDe($contexto))->format('d/m/Y'),
                $service->motivoDe($contexto, $alumno),
                $alumno->trashed() ? 'Sí' : 'No',
            ]);
        });
    }

    public function headings(): array
    {
        $encabezados = [
            'No.',
            'Matrícula del ciclo',
            'Folio',
            'CURP',
            'Alumno',
            'Sexo',
            'Generación',
            'Grado',
        ];

        if ($this->bachillerato) {
            $encabezados[] = 'Semestre';
        }

        return array_merge($encabezados, [
            'Grupo',
            'Estatus',
            'Clasificación',
            'Ingreso al ciclo',
            'Salida o cierre',
            'Motivo',
            'Archivado',
        ]);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => [
                'font' => [
                    'bold' => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType' => 'solid',
                    'startColor' => ['rgb' => '7C3AED'],
                ],
            ],
        ];
    }
}
