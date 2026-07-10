<?php

namespace App\Exports\MediaSuperior;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DocumentoOficialExport implements FromArray, ShouldAutoSize, WithStyles, WithTitle
{
    public function __construct(
        private readonly string $tipo,
        private readonly array $datos,
    ) {}

    public function array(): array
    {
        return match ($this->tipo) {
            'registro-escolaridad' => $this->registro(),
            'acta-resultados' => $this->acta(),
            'kardex' => $this->kardex(),
            'historial-academico' => $this->historialAcademico(),
            'certificado' => $this->certificado(),
            'plantilla-asistencias' => $this->plantillaAsistencias(),
            default => [['Sin información']],
        };
    }

    public function title(): string
    {
        return Str::limit(match ($this->tipo) {
            'registro-escolaridad' => 'Registro escolaridad',
            'acta-resultados' => 'Acta resultados',
            'kardex' => 'Kardex',
            'historial-academico' => 'Historial académico',
            'certificado' => 'Certificado',
            'plantilla-asistencias' => 'Asistencias',
            default => 'Documento',
        }, 31, '');
    }

    public function styles(Worksheet $sheet): array
    {
        $ultimaColumna = $sheet->getHighestColumn();
        $ultimaFila = $sheet->getHighestRow();
        $sheet->freezePane('A2');
        $sheet->setAutoFilter("A1:{$ultimaColumna}1");
        $sheet->getStyle("A1:{$ultimaColumna}1")->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '006492']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A1:{$ultimaColumna}{$ultimaFila}")->getAlignment()->setWrapText(true);

        return [];
    }

    private function registro(): array
    {
        $materias = collect($this->datos['asignaciones'] ?? []);
        $encabezado = array_merge(
            ['No.', 'Matrícula', 'Alumno', 'Sexo'],
            $materias->map(fn ($a) => $a->materia?->clave ?: $a->materia?->materia)->all(),
            ['No acreditadas', 'Situación escolar'],
        );

        $filas = collect($this->datos['filas'] ?? [])->map(function (array $fila): array {
            return array_merge(
                [$fila['numero'], $fila['matricula'], $fila['nombre'], $fila['sexo']],
                collect($fila['materias'])->pluck('valor')->all(),
                [$fila['asignaturas_no_acreditadas'], $fila['situacion_escolar']],
            );
        })->all();

        return array_merge([$encabezado], $filas);
    }

    private function acta(): array
    {
        $filas = collect($this->datos['filas'] ?? [])->map(fn (array $fila) => [
            $fila['numero'],
            $fila['matricula'],
            $fila['nombre'],
            $fila['calificacion_numero'],
            $fila['calificacion_letra'],
            $fila['asistencia'],
            $fila['acreditado'],
        ])->all();

        return array_merge([
            ['No. progresivo', 'Matrícula', 'Nombre del alumno', 'Calificación número', 'Calificación letra', '% asistencia', 'Acreditado'],
        ], $filas);
    }

    private function kardex(): array
    {
        $filas = [['Semestre', 'Ciclo escolar', 'Clave', 'Asignatura', 'Calificación final', '% asistencia', 'Tipo', 'Acreditada']];

        foreach ($this->datos['semestres'] ?? [] as $semestre) {
            foreach ($semestre['oficiales'] as $materia) {
                $filas[] = [
                    $semestre['numero'],
                    $semestre['ciclo']?->nombre,
                    $materia['clave'],
                    $materia['nombre'],
                    $materia['valor'],
                    $materia['asistencia'] !== null ? (float) $materia['asistencia'] : '',
                    'Oficial',
                    $materia['acreditada'] ? 'Sí' : 'No',
                ];
            }
            foreach ($semestre['extras'] as $materia) {
                $filas[] = [
                    $semestre['numero'],
                    $semestre['ciclo']?->nombre,
                    $materia['clave'],
                    $materia['nombre'],
                    $materia['valor'],
                    $materia['asistencia'] !== null ? (float) $materia['asistencia'] : '',
                    'Extra informativa',
                    'No aplica',
                ];
            }
        }

        return $filas;
    }


    private function historialAcademico(): array
    {
        $filas = [[
            'Semestre',
            'Ciclo escolar',
            'Clave',
            'Asignatura',
            'Calificación final',
            '% asistencia',
            'Tipo',
            'Acreditada',
            'Regularización 1',
            'Regularización 2',
            'Regularización 3',
        ]];

        foreach ($this->datos['semestres_historial'] ?? [] as $semestre) {
            foreach ($semestre['oficiales'] as $materia) {
                $filas[] = [
                    $semestre['numero'],
                    $semestre['ciclo_texto'] ?? '',
                    $materia['clave'],
                    $materia['nombre'],
                    $materia['valor'] !== '' ? $materia['valor'] : '',
                    $materia['asistencia'] !== null ? (float) $materia['asistencia'] : '',
                    'Oficial',
                    $materia['completa'] ? ($materia['acreditada'] ? 'Sí' : 'No') : '',
                    '',
                    '',
                    '',
                ];
            }

            if ((bool) data_get($this->datos, 'institucional.mostrar_materias_extra', true)) {
                foreach ($semestre['extras'] as $materia) {
                    $filas[] = [
                        $semestre['numero'],
                        $semestre['ciclo_texto'] ?? '',
                        $materia['clave'],
                        $materia['nombre'],
                        $materia['valor'] !== '' ? $materia['valor'] : '',
                        $materia['asistencia'] !== null ? (float) $materia['asistencia'] : '',
                        'Extra informativa',
                        'No aplica',
                        '',
                        '',
                        '',
                    ];
                }
            }
        }

        return $filas;
    }

    private function certificado(): array
    {
        $filas = [['Folio', 'Certificado', 'Semestre', 'Ciclo escolar', 'Clave', 'Asignatura', 'Calificación final', 'Tipo de materia', 'Acreditada']];
        $folio = data_get($this->datos, 'folio');
        $tipo = data_get($this->datos, 'modalidad_certificado');
        $mostrarExtras = (bool) data_get($this->datos, 'institucional.mostrar_materias_extra', true);

        foreach ($this->datos['semestres_certificados'] ?? [] as $semestre) {
            foreach ($semestre['oficiales'] as $materia) {
                $filas[] = [
                    $folio,
                    Str::ucfirst($tipo),
                    $semestre['numero'],
                    $semestre['ciclo']?->nombre,
                    $materia['clave'],
                    $materia['nombre'],
                    $materia['valor'],
                    'Oficial',
                    $materia['acreditada'] ? 'Sí' : 'No',
                ];
            }

            if ($mostrarExtras) {
                foreach ($semestre['extras'] as $materia) {
                    $filas[] = [
                        $folio,
                        Str::ucfirst($tipo),
                        $semestre['numero'],
                        $semestre['ciclo']?->nombre,
                        $materia['clave'],
                        $materia['nombre'],
                        $materia['valor'],
                        'Extra informativa',
                        'No aplica',
                    ];
                }
            }
        }

        return $filas;
    }

    private function plantillaAsistencias(): array
    {
        $filas = collect($this->datos['filas'] ?? [])->map(fn (array $fila) => [
            $fila['matricula'],
            $fila['nombre'],
            $fila['asistencia'],
        ])->all();

        return array_merge([['Matrícula', 'Alumno', 'Porcentaje de asistencia']], $filas);
    }
}
