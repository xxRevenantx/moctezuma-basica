<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\ConstanciaTraslado;
use App\Models\DocumentoAlumno;
use App\Models\Inscripcion;
use App\Models\Periodos;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class ConstanciaTrasladoService
{
    public function crearGenerada(
        Inscripcion $alumno,
        int $cicloEscolarId,
        array $periodoIds = [],
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): ConstanciaTraslado {
        $usuarioId = $usuarioId ?: auth()->id();
        abort_unless($usuarioId, 422, 'No existe un usuario para emitir la constancia.');

        $periodos = Periodos::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $alumno->nivel_id)
            ->where('generacion_id', $alumno->generacion_id)
            ->when($alumno->semestre_id, fn ($q) => $q->where('semestre_id', $alumno->semestre_id), fn ($q) => $q->whereNull('semestre_id'))
            ->when($periodoIds !== [], fn ($q) => $q->whereIn('id', array_map('intval', $periodoIds)))
            ->whereHas('calificaciones', fn ($q) => $q->where('inscripcion_id', $alumno->id))
            ->orderBy('fecha_inicio')->pluck('id');

        abort_if($periodos->isEmpty(), 422, 'No existen calificaciones para generar la constancia de traslado.');

        $constancia = ConstanciaTraslado::query()->create([
            'inscripcion_id' => $alumno->id,
            'ciclo_escolar_id' => $cicloEscolarId,
            'folio' => $this->siguienteFolio(),
            'fecha_emision' => now()->toDateString(),
            'modalidad' => 'generada',
            'periodos_incluidos' => $periodos->all(),
            'observaciones' => $observaciones,
            'emitida_por' => $usuarioId,
        ]);

        try {
            $contenido = Pdf::loadView('pdf.constancia-traslado-calificaciones', [
                'constancia' => $constancia->load(['inscripcion.nivel', 'inscripcion.grado', 'inscripcion.grupo.asignacionGrupo', 'inscripcion.generacion', 'inscripcion.semestre', 'cicloEscolar']),
                'calificaciones' => $this->calificacionesPara($constancia),
            ])->setPaper('letter', 'portrait')->output();

            $documento = app(ExpedienteArchivoService::class)->guardarPdfGenerado(
                $alumno,
                'constancia-traslado-calificaciones',
                $contenido,
                [
                    'nivel_id' => $alumno->nivel_id,
                    'grado_id' => $alumno->grado_id,
                    'grupo_id' => $alumno->grupo_id,
                    'ciclo_escolar_id' => $cicloEscolarId,
                    'fecha_documento' => now()->toDateString(),
                    'folio' => $constancia->folio,
                    'tipo_movimiento' => 'traslado',
                    'observaciones' => $observaciones,
                ]
            );
            $constancia->update(['ruta_pdf' => $documento->ruta, 'documento_alumno_id' => $documento->id]);
        } catch (Throwable $e) {
            $constancia->delete();
            throw $e;
        }

        return $constancia->refresh();
    }

    public function registrarExterna(
        Inscripcion $alumno,
        DocumentoAlumno $documento,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): ConstanciaTraslado {
        $usuarioId = $usuarioId ?: auth()->id();
        abort_unless($usuarioId, 422, 'No existe un usuario para registrar la constancia.');
        abort_unless((int) $documento->inscripcion_id === (int) $alumno->id, 422, 'El documento no corresponde al alumno seleccionado.');

        return ConstanciaTraslado::query()->create([
            'inscripcion_id' => $alumno->id,
            'ciclo_escolar_id' => $documento->ciclo_escolar_id,
            'folio' => $this->siguienteFolio(),
            'fecha_emision' => $documento->fecha_documento?->toDateString() ?: now()->toDateString(),
            'modalidad' => 'externa',
            'observaciones' => $observaciones,
            'ruta_pdf' => $documento->ruta,
            'documento_alumno_id' => $documento->id,
            'emitida_por' => $usuarioId,
        ]);
    }

    public function calificacionesPara(ConstanciaTraslado $constancia): Collection
    {
        $periodos = collect($constancia->periodos_incluidos ?? [])->map(fn ($id) => (int) $id)->filter();
        return Calificacion::query()
            ->with(['asignacionMateria.materia', 'periodo.periodoBasica', 'periodo.parcialBachillerato'])
            ->where('inscripcion_id', $constancia->inscripcion_id)
            ->when($constancia->ciclo_escolar_id, fn ($q) => $q->where('ciclo_escolar_id', $constancia->ciclo_escolar_id))
            ->when($periodos->isNotEmpty(), fn ($q) => $q->whereIn('periodo_id', $periodos))
            ->orderBy('periodo_id')->orderBy('asignacion_materia_id')->get()->groupBy('periodo_id');
    }

    private function siguienteFolio(): string
    {
        return DB::transaction(function (): string {
            $anio = now()->year;
            $ultimo = ConstanciaTraslado::query()->where('folio', 'like', "TRAS-{$anio}-%")->lockForUpdate()->orderByDesc('id')->value('folio');
            return sprintf('TRAS-%d-%04d', $anio, $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1);
        });
    }
}
