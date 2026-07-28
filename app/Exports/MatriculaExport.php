<?php

namespace App\Exports;

use App\Models\InscripcionCiclo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MatriculaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(
        private Collection $rows,
        private string $nivel,
        private bool $bachillerato = false
    ) {}

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($alumno, $indice) {
            /** @var InscripcionCiclo|null $contexto */
            $contexto = $alumno->ciclosEscolaresHistorial->first();
            $estatus = $this->estatusContexto($contexto);

            $row = [
                $indice + 1,
                $contexto?->matricula ?: $alumno->matricula,
                $alumno->folio,
                $alumno->curp,
                trim("{$alumno->apellido_paterno} {$alumno->apellido_materno} {$alumno->nombre}"),
                $alumno->genero,
                $contexto?->generacion?->etiqueta,
                $contexto?->grado?->nombre,
            ];

            if ($this->bachillerato) {
                $row[] = $contexto?->semestre?->numero
                    ? 'Semestre ' . $contexto->semestre->numero
                    : '—';
            }

            $row[] = $contexto?->grupo?->asignacionGrupo?->nombre ?? '—';
            $row[] = $this->etiquetaEstatus($estatus);
            $row[] = optional($contexto?->fecha_ingreso)->format('d/m/Y');
            $row[] = optional($contexto?->fecha_salida)->format('d/m/Y')
                ?: optional($contexto?->cerrado_at)->format('d/m/Y');
            $row[] = $contexto?->motivo_cierre;

            return $row;
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
            'Generación del ciclo',
            'Grado del ciclo',
        ];

        if ($this->bachillerato) {
            $encabezados[] = 'Semestre del ciclo';
        }

        return array_merge($encabezados, [
            'Grupo del ciclo',
            'Resultado / estatus del ciclo',
            'Ingreso al ciclo',
            'Salida o cierre',
            'Motivo de cierre',
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
                    'startColor' => ['rgb' => '006492'],
                ],
            ],
        ];
    }

    private function estatusContexto(?InscripcionCiclo $contexto): string
    {
        if (! $contexto) {
            return 'inactivo';
        }

        if ($contexto->estado === 'en_curso') {
            return (string) ($contexto->estatus_actual_ciclo ?: 'activo');
        }

        return (string) ($contexto->resultado_final ?: $contexto->estatus_actual_ciclo ?: 'inactivo');
    }

    private function etiquetaEstatus(string $estatus): string
    {
        return match ($estatus) {
            'activo' => 'Activo',
            'preinscrito' => 'Preinscrito',
            'baja_temporal' => 'Baja temporal',
            'baja_definitiva' => 'Baja definitiva',
            'traslado', 'trasladado' => 'Trasladado',
            'suspendido' => 'Suspendido',
            'egresado' => 'Egresado',
            'promovido', 'promovido_grado' => 'Promovido de grado',
            'promovido_nivel' => 'Promovido al siguiente nivel',
            'grado_concluido' => 'Grado concluido',
            'pendiente_reinscripcion' => 'Pendiente de reinscripción',
            'no_reinscrito' => 'No reinscrito',
            'inactivo' => 'Inactivo',
            'reingreso' => 'Reingreso',
            'no_promovido' => 'No promovido',
            default => ucfirst(str_replace('_', ' ', $estatus)),
        };
    }
}
