<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class MatriculaExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles
{
    public function __construct(private Collection $rows, private string $nivel, private bool $bachillerato = false) {}

    public function collection(): Collection
    {
        return $this->rows->values()->map(function ($a, $i) {
            $row = [
                $i + 1, $a->matricula, $a->folio, $a->curp,
                trim("{$a->apellido_paterno} {$a->apellido_materno} {$a->nombre}"),
                $a->genero, $a->generacion?->etiqueta, $a->grado?->nombre,
            ];
            if ($this->bachillerato) $row[] = $a->semestre?->numero ? 'Semestre ' . $a->semestre->numero : '—';
            $row[] = $a->grupo?->asignacionGrupo?->nombre ?? '—';
            $row[] = str_replace('_', ' ', ucfirst($a->estatus ?? 'activo'));
            $row[] = optional($a->fecha_inscripcion)->format('d/m/Y');
            $row[] = optional($a->fecha_estatus)->format('d/m/Y');
            $row[] = $a->motivo_estatus;
            return $row;
        });
    }

    public function headings(): array
    {
        $h = ['No.', 'Matrícula', 'Folio', 'CURP', 'Alumno', 'Sexo', 'Generación', 'Grado'];
        if ($this->bachillerato) $h[] = 'Semestre';
        return array_merge($h, ['Grupo', 'Estatus', 'Ingreso al plantel', 'Fecha del estatus', 'Motivo']);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());
        return [1 => ['font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']], 'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '006492']]]];
    }
}
