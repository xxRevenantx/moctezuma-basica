<?php

namespace App\Exports\Inscripciones;

use App\Models\Inscripcion;
use App\Services\ObservacionInscripcionService;
use Illuminate\Database\Eloquent\Builder;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class InscripcionesSheet implements FromQuery, WithMapping, WithHeadings, ShouldAutoSize, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Alumnos';
    }

    public function query(): Builder
    {
        return Inscripcion::query()
            ->with([
                'cicloEscolar',
                'nivel',                'grado',
                'generacion',
                'grupo.cicloEscolar',
                'grupo.asignacionGrupo',                'grupo.nivel',
                'grupo.grado',
                'grupo.generacion',
                'grupo.semestre',
                'semestre',
                'ciclo',
                'observacionesInscripcion.cicloEscolar',
            ])
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre');
    }

    public function headings(): array
    {
        return [
            'curp',
            'matricula',
            'folio',
            'nombre',
            'apellido_paterno',
            'apellido_materno',
            'fecha_nacimiento',
            'genero',
            'fecha_inscripcion',
            'ciclo_escolar_id',
            'ciclo_escolar',
            'tipo_ingreso',
            'estatus',
            'nivel_id',
            'nivel',
            'grado_id',
            'grado',
            'generacion_id',
            'generacion',
            'grupo_id',
            'clave_grupo',
            'grupo',
            'semestre_id',
            'semestre',
            'ciclo_id',
            'periodo_inscripcion',
            'ciclo_escolar_observacion',
            'observaciones',
        ];
    }

    public function map($alumno): array
    {
        $observacion = $alumno->observacionesInscripcion
            ->first(fn ($item) => (bool) $item->cicloEscolar?->es_actual)
            ?? $alumno->observacionesInscripcion->sortByDesc('ciclo_escolar_id')->first();

        return [
            $alumno->curp,
            $alumno->matricula,
            $alumno->folio,
            $alumno->nombre,
            $alumno->apellido_paterno,
            $alumno->apellido_materno,
            optional($alumno->fecha_nacimiento)->format('Y-m-d'),
            $alumno->genero,
            $alumno->fecha_inscripcion
                ? date('Y-m-d', strtotime($alumno->fecha_inscripcion))
                : '',

            $alumno->ciclo_escolar_id,
            $alumno->cicloEscolar
                ? "{$alumno->cicloEscolar->inicio_anio} - {$alumno->cicloEscolar->fin_anio}"
                : '',
            $alumno->tipo_ultimo_ingreso,
            $alumno->estatus,

            $alumno->nivel_id,
            $alumno->nivel?->nombre,

            $alumno->grado_id,
            $alumno->grado?->nombre,

            $alumno->generacion_id,
            $alumno->generacion
                ? "{$alumno->generacion->anio_ingreso} - {$alumno->generacion->anio_egreso}"
                : '',

            $alumno->grupo_id,
            $alumno->grupo?->clave,
            $alumno->grupo ? $this->textoGrupo($alumno->grupo) : '',

            $alumno->semestre_id,
            $alumno->semestre ? 'Semestre ' . $alumno->semestre->numero : '',

            $alumno->ciclo_id,
            $alumno->ciclo?->ciclo,

            $observacion?->cicloEscolar
                ? $observacion->cicloEscolar->inicio_anio . '-' . $observacion->cicloEscolar->fin_anio
                : '',
            app(ObservacionInscripcionService::class)->textoPlano($observacion?->contenido),
        ];
    }

    private function textoGrupo($grupo): string
    {
        $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';

        $partes = [
            $nombreGrupo,
            $grupo->grado?->nombre,
            $grupo->generacion ? "{$grupo->generacion->anio_ingreso}-{$grupo->generacion->anio_egreso}" : null,
        ];

        if ($grupo->semestre) {
            $partes[] = 'Semestre ' . $grupo->semestre->numero;
        }

        return implode(' · ', array_filter($partes));
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:AB1');

                $hoja->getStyle('A1:AB1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                ]);
            },
        ];
    }
}
