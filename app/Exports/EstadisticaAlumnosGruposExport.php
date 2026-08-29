<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class EstadisticaAlumnosGruposExport implements FromCollection, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly array $datos)
    {
    }

    public function collection(): Collection
    {
        $contexto = $this->datos['contexto'] ?? [];
        $columnas = $this->datos['columnas'] ?? [];
        $slug = $contexto['nivel_slug'] ?? '';
        $filas = [];

        $filas[] = ['DESGLOSE SEP · ALUMNADO Y GRUPOS'];
        $filas[] = ['Formato', $contexto['codigo_formato'] ?? ''];
        $filas[] = ['Nivel', $contexto['nivel'] ?? ''];
        $filas[] = ['CCT', $contexto['cct'] ?? ''];
        $filas[] = ['Ciclo escolar', $contexto['ciclo'] ?? ''];
        $filas[] = ['Matrícula al', $contexto['fecha_corte_texto'] ?? ''];
        $filas[] = ['Edad al', $contexto['fecha_edad_texto'] ?? ''];
        $filas[] = [];

        $encabezado = ['Grado', 'Sexo'];
        if ($slug !== 'preescolar') {
            $encabezado[] = 'Condición';
        }
        $encabezado = array_merge($encabezado, array_values($columnas), ['Total']);
        if ($slug === 'secundaria') {
            $encabezado[] = 'Grupos';
        }
        $filas[] = $encabezado;

        $gruposPorGrado = collect(data_get($this->datos, 'grupos.por_grado', []))->keyBy('grado_id');

        foreach ($this->datos['grados'] ?? [] as $grado) {
            if ($slug === 'preescolar') {
                foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'subtotal' => 'Subtotal'] as $clave => $etiqueta) {
                    $fila = [$grado['grado'].'°', $etiqueta];
                    foreach (array_keys($columnas) as $edad) {
                        $sombreada = in_array(
                            $edad,
                            data_get($grado, 'sombreadas.'.($clave === 'subtotal' ? 'subtotal' : 'simple'), []),
                            true
                        );
                        $fila[] = $sombreada ? '' : data_get($grado, "{$clave}.edades.{$edad}", 0);
                    }
                    $fila[] = data_get($grado, "{$clave}.total", 0);
                    $filas[] = $fila;
                }
                continue;
            }

            foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres'] as $sexo => $sexoEtiqueta) {
                foreach (['nuevo_ingreso' => 'Nuevo ingreso', 'repetidores' => 'Repetidores'] as $condicion => $condicionEtiqueta) {
                    $fila = [$grado['grado'].'°', $sexoEtiqueta, $condicionEtiqueta];
                    foreach (array_keys($columnas) as $edad) {
                        $sombreada = in_array($edad, data_get($grado, "sombreadas.{$condicion}", []), true);
                        $fila[] = $sombreada ? '' : data_get($grado, "{$sexo}.{$condicion}.edades.{$edad}", 0);
                    }
                    $fila[] = data_get($grado, "{$sexo}.{$condicion}.total", 0);
                    if ($slug === 'secundaria') {
                        $fila[] = '';
                    }
                    $filas[] = $fila;
                }
            }

            $fila = [$grado['grado'].'°', '', 'Subtotal'];
            foreach (array_keys($columnas) as $edad) {
                $sombreada = in_array($edad, data_get($grado, 'sombreadas.subtotal', []), true);
                $fila[] = $sombreada ? '' : data_get($grado, "subtotal.edades.{$edad}", 0);
            }
            $fila[] = data_get($grado, 'subtotal.total', 0);
            if ($slug === 'secundaria') {
                $fila[] = (int) data_get($gruposPorGrado->get($grado['grado_id']), 'total', 0);
            }
            $filas[] = $fila;
        }

        // Totales generales, siguiendo el cierre visual del formato 911.
        if ($slug === 'preescolar') {
            foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'total' => 'Total'] as $clave => $etiqueta) {
                $fila = ['Total', $etiqueta];
                foreach (array_keys($columnas) as $edad) {
                    $fila[] = data_get($this->datos, "totales.{$clave}.edades.{$edad}", 0);
                }
                $fila[] = data_get($this->datos, "totales.{$clave}.total", 0);
                $filas[] = $fila;
            }
        } else {
            $filasTotales = [
                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
            ];

            foreach ($filasTotales as $total) {
                $fila = ['Total', $total['sexo_etiqueta'], $total['condicion_etiqueta']];
                foreach (array_keys($columnas) as $edad) {
                    $sombreada = $total['condicion'] === 'repetidores'
                        && (($slug === 'primaria' && $edad === 'menos_6') || ($slug === 'secundaria' && $edad === 'menos_12'));
                    $fila[] = $sombreada
                        ? ''
                        : data_get($this->datos, "totales.{$total['sexo']}.{$total['condicion']}.edades.{$edad}", 0);
                }
                $fila[] = data_get($this->datos, "totales.{$total['sexo']}.{$total['condicion']}.total", 0);
                if ($slug === 'secundaria') {
                    $fila[] = '';
                }
                $filas[] = $fila;
            }

            $fila = ['Total', '', 'Total general'];
            foreach (array_keys($columnas) as $edad) {
                $fila[] = data_get($this->datos, "totales.total.edades.{$edad}", 0);
            }
            $fila[] = data_get($this->datos, 'totales.total.total', 0);
            if ($slug === 'secundaria') {
                $fila[] = data_get($this->datos, 'grupos.total', 0);
            }
            $filas[] = $fila;
        }

        $filas[] = [];
        $filas[] = ['GRUPOS POR GRADO'];
        $filas[] = ['Grado', 'Grupos'];
        foreach (data_get($this->datos, 'grupos.por_grado', []) as $grupo) {
            $filas[] = [$grupo['grado'].'°', $grupo['total']];
        }
        $filas[] = ['Total', data_get($this->datos, 'grupos.total', 0)];

        if (! empty($this->datos['incidencias'])) {
            $filas[] = [];
            $filas[] = ['INCIDENCIAS QUE REQUIEREN REVISIÓN'];
            $filas[] = ['Matrícula', 'Alumno', 'Grado', 'Motivo'];
            foreach ($this->datos['incidencias'] as $incidencia) {
                $filas[] = [
                    $incidencia['matricula'] ?? '',
                    $incidencia['alumno'] ?? '',
                    $incidencia['grado'] ?? '',
                    $incidencia['motivo'] ?? '',
                ];
            }
        }

        return collect($filas);
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->getStyle('A1:Z1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A9:Z9')->getFont()->setBold(true);
        $sheet->getStyle('A9:Z9')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8EEF8');
        $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->freezePane('A10');

        return [];
    }
}
