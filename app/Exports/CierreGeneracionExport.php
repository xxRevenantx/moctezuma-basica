<?php

namespace App\Exports;

use App\Models\ProcesoCierreCiclo;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class CierreGeneracionExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize, WithStyles
{
    public function __construct(private readonly ProcesoCierreCiclo $proceso) {}

    public function collection(): Collection
    {
        return $this->proceso->detalles()
            ->with([
                'inscripcion' => fn ($relacion) => $relacion->withTrashed(),
                'inscripcionCicloOrigen.grado',
                'inscripcionCicloOrigen.grupo.asignacionGrupo',
                'inscripcionCicloOrigen.semestre',
                'inscripcionCicloDestino.nivel',
                'inscripcionCicloDestino.grado',
                'inscripcionCicloDestino.generacion',
                'inscripcionCicloDestino.grupo.asignacionGrupo',
                'inscripcionCicloDestino.semestre',
                'proyeccionContinuidad.cicloDestino',
                'proyeccionContinuidad.nivelDestino',
                'proyeccionContinuidad.gradoDestino',
                'proyeccionContinuidad.semestreDestino',
                'proyeccionContinuidad.generacionDestino',
                'proyeccionContinuidad.grupoDestino.asignacionGrupo',
            ])
            ->orderBy('resultado')
            ->orderBy('id')
            ->get();
    }

    public function headings(): array
    {
        return [
            'No.',
            'Matrícula origen',
            'Apellido paterno',
            'Apellido materno',
            'Nombre(s)',
            'Resultado',
            'Tipo de proyección',
            'Resultado histórico de origen',
            'Grado/Semestre origen',
            'Grupo origen',
            'Nivel destino',
            'Generación destino',
            'Grado/Semestre destino',
            'Grupo destino',
            'Matrícula destino',
            'Estado de proyección',
            'Fecha efectiva',
            'Escuela destino',
            'Observación',
            'Integridad del proceso',
            'Firma de respaldo individual',
            'Revertido',
        ];
    }

    public function map($detalle): array
    {
        static $numero = 0;
        $numero++;
        $alumno = $detalle->inscripcion;
        $origen = $detalle->inscripcionCicloOrigen;
        $destino = $detalle->inscripcionCicloDestino;
        $proyeccion = $detalle->proyeccionContinuidad;
        $propuesto = $detalle->destino_propuesto ?? [];

        $origenAcademico = $origen?->semestre?->numero
            ? 'Semestre '.$origen->semestre->numero
            : ($origen?->grado?->nombre ?? 'Sin grado');
        $destinoAcademico = $destino?->semestre?->numero
            ? 'Semestre '.$destino->semestre->numero
            : ($destino?->grado?->nombre
                ?? ($proyeccion?->semestreDestino?->numero
                    ? 'Semestre '.$proyeccion->semestreDestino->numero
                    : ($proyeccion?->gradoDestino?->nombre ?? 'No aplica')));

        return [
            $numero,
            $detalle->estado_anterior['matricula'] ?? $origen?->matricula ?? $alumno?->matricula,
            $alumno?->apellido_paterno,
            $alumno?->apellido_materno,
            $alumno?->nombre,
            $this->etiquetaResultado((string) $detalle->resultado),
            $proyeccion?->etiqueta_tipo ?? 'No aplica',
            $proyeccion?->etiqueta_resultado_origen ?? $this->etiquetaResultado((string) ($origen?->resultado_final ?? $detalle->resultado)),
            $origenAcademico,
            $origen?->grupo?->asignacionGrupo?->nombre ?? 'Sin grupo',
            $destino?->nivel?->nombre ?? $proyeccion?->nivelDestino?->nombre ?? 'No aplica',
            $destino?->generacion?->etiqueta ?? $proyeccion?->generacionDestino?->etiqueta ?? 'No aplica',
            $destinoAcademico,
            $destino?->grupo?->asignacionGrupo?->nombre ?? $proyeccion?->grupoDestino?->asignacionGrupo?->nombre ?? 'Por asignar',
            $destino ? ($detalle->estado_nuevo['matricula'] ?? $destino?->matricula) : ($proyeccion?->matricula_sugerida ?? 'Por confirmar'),
            $proyeccion?->etiqueta_estado ?? ($destino ? 'Proyección formalizada' : 'No aplica'),
            $propuesto['fecha_efectiva'] ?? $this->proceso->fecha_efectiva?->format('Y-m-d'),
            $propuesto['escuela_destino'] ?? '',
            $detalle->observacion,
            str_starts_with((string) $this->proceso->integridad_estado, 'verificado') ? 'Respaldo verificado' : 'Proceso anterior / sin firma',
            $detalle->respaldo_hash ?: 'No disponible',
            $detalle->revertido_at ? 'Sí' : 'No',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $sheet->freezePane('A2');
        $sheet->setAutoFilter($sheet->calculateWorksheetDimension());

        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['argb' => 'FF006492']],
            ],
        ];
    }
    private function etiquetaResultado(string $resultado): string
    {
        return match ($resultado) {
            'continuidad_interna' => 'Proyección de promoción o continuidad',
            'no_reinscrito' => 'Acreditó, pero no se reinscribirá',
            'egresado' => 'Egresado sin continuidad interna',
            'traslado' => 'Traslado a otra institución',
            'baja_definitiva' => 'Baja definitiva',
            'no_promovido' => 'No promovido / repetición proyectada',
            'sin_cambio' => 'Sin cambio histórico',
            default => ucfirst(str_replace('_', ' ', $resultado)),
        };
    }

}
