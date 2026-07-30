<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AnaliticaInstitucionalExport implements FromCollection, ShouldAutoSize, WithStyles, WithEvents
{
    public function __construct(private readonly array $datos) {}

    public function collection(): Collection
    {
        $filas = [];
        $contexto = $this->datos['contexto'] ?? [];
        $resumen = $this->datos['resumen'] ?? [];

        $filas[] = ['ANALÍTICA INSTITUCIONAL AVANZADA'];
        $filas[] = ['Ciclo', $contexto['ciclo'] ?? ''];
        $filas[] = ['Comparación', $contexto['ciclo_comparacion'] ?? 'Sin comparación'];
        $filas[] = ['Nivel', $contexto['nivel'] ?? ''];
        $filas[] = ['Generación', $contexto['generacion'] ?? ''];
        $filas[] = ['Grado', $contexto['grado'] ?? ''];
        $filas[] = ['Grupo', $contexto['grupo'] ?? ''];
        $filas[] = ['Generado', $this->datos['generado_at'] ?? ''];
        $filas[] = [];

        $filas[] = ['INDICADOR', 'VALOR'];
        foreach ([
            'Matrícula' => $resumen['matricula'] ?? 0,
            'Variación de matrícula (%)' => $resumen['variacion_matricula'] ?? 0,
            'Permanencia (%)' => $resumen['permanencia'] ?? 0,
            'Promoción (%)' => $resumen['promocion'] ?? 0,
            'Promedio general' => $resumen['promedio'] ?? 0,
            'Reprobación (%)' => $resumen['reprobacion'] ?? 0,
            'Riesgo alto/crítico (%)' => $resumen['riesgo_alto_critico'] ?? 0,
            'Cobertura documental (%)' => $resumen['documentacion'] ?? 0,
            'Casos críticos de integridad' => $resumen['integridad_critica'] ?? 0,
            'Seguimientos activos' => $resumen['seguimientos_activos'] ?? 0,
            'Conflictos críticos de horario' => $resumen['conflictos_horario'] ?? 0,
        ] as $etiqueta => $valor) {
            $filas[] = [$etiqueta, $valor];
        }

        $filas[] = [];
        $filas[] = ['TENDENCIA POR CICLO'];
        $filas[] = ['Ciclo', 'Matrícula', 'Promedio', 'Permanencia %', 'Promoción %', 'Riesgo alto/crítico %'];
        foreach ($this->datos['tendencia_ciclos'] ?? [] as $fila) {
            $filas[] = [$fila['ciclo'], $fila['matricula'], $fila['promedio'], $fila['permanencia'], $fila['promocion'], $fila['riesgo']];
        }

        $filas[] = [];
        $filas[] = ['INDICADORES POR GRUPO'];
        $filas[] = ['Grado/Semestre', 'Grupo', 'Alumnos', 'Promedio', 'Alumnos en riesgo', 'Riesgo %'];
        foreach ($this->datos['grupos'] ?? [] as $fila) {
            $filas[] = [$fila['grado'], $fila['grupo'], $fila['alumnos'], $fila['promedio'], $fila['riesgo'], $fila['riesgo_porcentaje']];
        }

        $filas[] = [];
        $filas[] = ['MATERIAS CON REPROBACIÓN'];
        $filas[] = ['Materia', 'Evaluaciones', 'Reprobadas', 'Promedio', 'Reprobación %'];
        foreach ($this->datos['rendimiento']['materias_reprobacion'] ?? [] as $fila) {
            $filas[] = [$fila['materia'], $fila['evaluaciones'], $fila['reprobadas'], $fila['promedio'], $fila['porcentaje']];
        }

        $filas[] = [];
        $filas[] = ['CARGA DOCENTE PUBLICADA'];
        $filas[] = ['Docente', 'Bloques', 'Grupos', 'Sesiones compartidas', 'Traslapes excepcionales'];
        foreach ($this->datos['carga_docente'] ?? [] as $fila) {
            $filas[] = [$fila['docente'], $fila['bloques'], $fila['grupos'], $fila['compartidas'], $fila['excepcionales']];
        }

        $filas[] = [];
        $filas[] = ['ALERTAS DIRECTIVAS'];
        $filas[] = ['Severidad', 'Categoría', 'Título', 'Mensaje'];
        foreach ($this->datos['alertas'] ?? [] as $alerta) {
            $filas[] = [ucfirst($alerta['severidad']), ucfirst($alerta['categoria']), $alerta['titulo'], $alerta['mensaje']];
        }

        return collect($filas);
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true, 'size' => 16, 'color' => ['argb' => 'FF006492']]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $sheet->freezePane('A10');
                $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setVertical(Alignment::VERTICAL_TOP)->setWrapText(true);

                foreach (range(1, $sheet->getHighestRow()) as $row) {
                    $value = (string) $sheet->getCell('A'.$row)->getValue();
                    if (in_array($value, ['INDICADOR', 'Ciclo', 'Grado/Semestre', 'Materia', 'Docente', 'Severidad'], true)) {
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF006492']],
                        ]);
                    }
                    if (in_array($value, ['TENDENCIA POR CICLO', 'INDICADORES POR GRUPO', 'MATERIAS CON REPROBACIÓN', 'CARGA DOCENTE PUBLICADA', 'ALERTAS DIRECTIVAS'], true)) {
                        $sheet->getStyle('A'.$row.':F'.$row)->applyFromArray([
                            'font' => ['bold' => true, 'color' => ['argb' => 'FF66851D']],
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFEAF3D4']],
                        ]);
                    }
                }
            },
        ];
    }
}
