<?php

namespace App\Exports;

use App\Models\FichaDescriptiva;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FichaDescriptivaExport implements FromArray, WithHeadings, ShouldAutoSize, WithStyles, WithTitle, WithEvents
{
    public function __construct(
        private readonly int $nivelId,
        private readonly ?int $generacionId,
        private readonly ?int $gradoId,
        private readonly ?int $grupoId,
        private readonly ?int $cicloEscolarId,
        private readonly int $periodo,
        private readonly array $campos
    ) {
    }

    public function title(): string
    {
        return 'Fichas descriptivas';
    }

    public function headings(): array
    {
        return array_merge([
            '#',
            'Matrícula',
            'CURP',
            'Nombre completo',
            'Nivel',
            'Grado',
            'Grupo',
            'Generación',
            'Periodo',
        ], collect($this->campos)->pluck('label')->values()->all());
    }

    public function array(): array
    {
        $alumnos = Inscripcion::query()
            ->with([
                'nivel:id,nombre',
                'grado:id,nombre',
                'grupo:id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
            ])
            ->where('nivel_id', $this->nivelId)
            ->when($this->generacionId, fn($q) => $q->where('generacion_id', $this->generacionId))
            ->when($this->gradoId, fn($q) => $q->where('grado_id', $this->gradoId))
            ->when($this->grupoId, fn($q) => $q->where('grupo_id', $this->grupoId))
            ->where('activo', true)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        $fichas = FichaDescriptiva::query()
            ->whereIn('inscripcion_id', $alumnos->pluck('id'))
            ->when($this->cicloEscolarId, fn($q) => $q->where('ciclo_escolar_id', $this->cicloEscolarId))
            ->where('periodo', $this->periodo)
            ->get()
            ->groupBy('inscripcion_id')
            ->map(fn(Collection $items) => $items->keyBy('campo'));

        $nombrePeriodo = match ($this->periodo) {
            1 => 'Primera evaluación diagnóstica',
            2 => 'Segunda evaluación',
            3 => 'Tercera evaluación',
            default => 'Periodo ' . $this->periodo,
        };

        return $alumnos->values()->map(function (Inscripcion $alumno, int $index) use ($fichas, $nombrePeriodo) {
            $generacion = $alumno->generacion
                ? $alumno->generacion->anio_ingreso . '-' . $alumno->generacion->anio_egreso
                : 'Sin generación';

            $fila = [
                $index + 1,
                $alumno->matricula,
                $alumno->curp,
                trim($alumno->nombre . ' ' . $alumno->apellido_paterno . ' ' . $alumno->apellido_materno),
                $alumno->nivel?->nombre,
                $alumno->grado?->nombre,
                $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G',
                $generacion,
                $nombrePeriodo,
            ];

            foreach ($this->campos as $clave => $campo) {
                $fila[] = $fichas->get($alumno->id)?->get($clave)?->descripcion ?? '';
            }

            return $fila;
        })->all();
    }

    public function styles(Worksheet $sheet): array
    {
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

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                $highestColumn = $sheet->getHighestColumn();
                $highestRow = $sheet->getHighestRow();

                $sheet->freezePane('A2');

                if ($highestRow >= 1) {
                    $sheet->setAutoFilter("A1:{$highestColumn}{$highestRow}");
                }

                $sheet->getStyle("A1:{$highestColumn}{$highestRow}")
                    ->getAlignment()
                    ->setVertical('top')
                    ->setWrapText(true);

                $sheet->getStyle("A1:{$highestColumn}1")
                    ->getAlignment()
                    ->setHorizontal('center');

                for ($row = 2; $row <= $highestRow; $row++) {
                    $sheet->getRowDimension($row)->setRowHeight(45);
                }
            },
        ];
    }
}
