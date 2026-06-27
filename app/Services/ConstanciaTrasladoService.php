<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\ConstanciaTraslado;
use App\Models\DocumentoAlumno;
use App\Models\Periodos;
use App\Models\TipoDocumento;
use App\Models\TrayectoriaAcademica;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ConstanciaTrasladoService
{
    public function crearGenerada(
        TrayectoriaAcademica $trayectoria,
        array $periodoIds = [],
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): ConstanciaTraslado {
        $constancia = DB::transaction(function () use ($trayectoria, $periodoIds, $observaciones, $usuarioId) {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para emitir la constancia.');
            $periodosSolicitados = collect($periodoIds)->map(fn ($id) => (int) $id)->filter()->unique()->values();

            if ($periodosSolicitados->isEmpty()) {
                $periodos = Periodos::query()
                    ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->where('generacion_id', $trayectoria->generacion_id)
                    ->when(
                        $trayectoria->semestre_id,
                        fn ($query) => $query->where('semestre_id', $trayectoria->semestre_id),
                        fn ($query) => $query->whereNull('semestre_id')
                    )
                    ->whereHas('calificaciones', fn ($query) => $query->where('inscripcion_id', $trayectoria->inscripcion_id))
                    ->orderBy('fecha_inicio')
                    ->pluck('id');
            } else {
                $periodos = Periodos::query()
                    ->whereIn('id', $periodosSolicitados)
                    ->where('ciclo_escolar_id', $trayectoria->ciclo_escolar_id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->where('generacion_id', $trayectoria->generacion_id)
                    ->when(
                        $trayectoria->semestre_id,
                        fn ($query) => $query->where('semestre_id', $trayectoria->semestre_id),
                        fn ($query) => $query->whereNull('semestre_id')
                    )
                    ->pluck('id');

                abort_if(
                    $periodos->count() !== $periodosSolicitados->count(),
                    422,
                    'Uno o más periodos no pertenecen a la trayectoria seleccionada.'
                );
            }

            abort_if($periodos->isEmpty(), 422, 'No existen calificaciones para generar la constancia de traslado.');

            return ConstanciaTraslado::query()->create([
                'inscripcion_id' => $trayectoria->inscripcion_id,
                'trayectoria_academica_id' => $trayectoria->id,
                'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                'folio' => $this->siguienteFolio(),
                'fecha_emision' => now()->toDateString(),
                'modalidad' => 'generada',
                'periodos_incluidos' => $periodos->all(),
                'observaciones' => $observaciones,
                'emitida_por' => $usuarioId,
            ]);
        });

        try {
            return $this->guardarPdfGenerado($constancia, (int) $constancia->emitida_por);
        } catch (Throwable $exception) {
            $constancia->delete();
            throw $exception;
        }
    }

    public function registrarExterna(
        TrayectoriaAcademica $trayectoria,
        DocumentoAlumno $documento,
        ?string $observaciones = null,
        ?int $usuarioId = null
    ): ConstanciaTraslado {
        return DB::transaction(function () use ($trayectoria, $documento, $observaciones, $usuarioId) {
            $usuarioId = $usuarioId ?: auth()->id();
            abort_unless($usuarioId, 422, 'No existe un usuario para registrar la constancia.');

            abort_unless(
                (int) $documento->inscripcion_id === (int) $trayectoria->inscripcion_id
                && (int) $documento->trayectoria_academica_id === (int) $trayectoria->id,
                422,
                'El documento no corresponde a la trayectoria seleccionada.'
            );

            return ConstanciaTraslado::query()->create([
                'inscripcion_id' => $trayectoria->inscripcion_id,
                'trayectoria_academica_id' => $trayectoria->id,
                'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                'folio' => $this->siguienteFolio(),
                'fecha_emision' => $documento->fecha_documento?->toDateString() ?: now()->toDateString(),
                'modalidad' => 'externa',
                'periodos_incluidos' => null,
                'observaciones' => $observaciones,
                'ruta_pdf' => $documento->ruta,
                'documento_alumno_id' => $documento->id,
                'emitida_por' => $usuarioId,
            ]);
        });
    }

    public function calificacionesPara(ConstanciaTraslado $constancia): Collection
    {
        $periodos = collect($constancia->periodos_incluidos ?? [])->map(fn ($id) => (int) $id)->filter();
        $trayectoria = $constancia->trayectoriaAcademica;

        return Calificacion::query()
            ->with(['asignacionMateria.materia', 'periodo.periodoBasica', 'periodo.parcialBachillerato'])
            ->where('inscripcion_id', $constancia->inscripcion_id)
            ->when($constancia->ciclo_escolar_id, fn ($query) => $query->where('ciclo_escolar_id', $constancia->ciclo_escolar_id))
            ->when($trayectoria, function ($query) use ($trayectoria) {
                $query->where('nivel_id', $trayectoria->nivel_id)
                    ->where('grado_id', $trayectoria->grado_id)
                    ->where('generacion_id', $trayectoria->generacion_id)
                    ->where('grupo_id', $trayectoria->grupo_id)
                    ->when(
                        $trayectoria->semestre_id,
                        fn ($sub) => $sub->where('semestre_id', $trayectoria->semestre_id),
                        fn ($sub) => $sub->whereNull('semestre_id')
                    );
            })
            ->when($periodos->isNotEmpty(), fn ($query) => $query->whereIn('periodo_id', $periodos))
            ->orderBy('periodo_id')
            ->orderBy('asignacion_materia_id')
            ->get()
            ->groupBy('periodo_id');
    }

    private function guardarPdfGenerado(ConstanciaTraslado $constancia, int $usuarioId): ConstanciaTraslado
    {
        $constancia->load([
            'inscripcion.matriculasAlumno',
            'trayectoriaAcademica.nivel',
            'trayectoriaAcademica.grado',
            'trayectoriaAcademica.grupo.asignacionGrupo',
            'trayectoriaAcademica.generacion',
            'trayectoriaAcademica.semestre',
            'cicloEscolar',
        ]);

        $trayectoria = $constancia->trayectoriaAcademica;
        abort_unless($trayectoria, 422, 'La constancia no tiene una trayectoria académica vinculada.');
        $calificaciones = $this->calificacionesPara($constancia);
        abort_if($calificaciones->isEmpty(), 422, 'No existen calificaciones para generar el documento.');

        $contenido = Pdf::loadView(
            'pdf.constancia-traslado-calificaciones',
            compact('constancia', 'calificaciones')
        )->setPaper('letter', 'portrait')->output();

        $ruta = sprintf(
            'expedientes/%d/constancia-traslado/nivel-%d/%s.pdf',
            $constancia->inscripcion_id,
            $trayectoria->nivel_id,
            mb_strtolower($constancia->folio)
        );

        Storage::disk('local')->put($ruta, $contenido);

        try {
            DB::transaction(function () use ($constancia, $trayectoria, $usuarioId, $ruta, $contenido) {
                $tipo = TipoDocumento::query()
                    ->where('slug', 'constancia-traslado-calificaciones')
                    ->firstOrFail();

                $version = ((int) DocumentoAlumno::query()
                    ->where('inscripcion_id', $constancia->inscripcion_id)
                    ->where('tipo_documento_id', $tipo->id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->max('version')) + 1;

                DocumentoAlumno::query()
                    ->where('inscripcion_id', $constancia->inscripcion_id)
                    ->where('tipo_documento_id', $tipo->id)
                    ->where('nivel_id', $trayectoria->nivel_id)
                    ->where('es_actual', true)
                    ->update(['es_actual' => false, 'estado' => 'reemplazado']);

                $documento = DocumentoAlumno::query()->create([
                    'inscripcion_id' => $constancia->inscripcion_id,
                    'tipo_documento_id' => $tipo->id,
                    'nivel_id' => $trayectoria->nivel_id,
                    'grado_id' => $trayectoria->grado_id,
                    'grupo_id' => $trayectoria->grupo_id,
                    'ciclo_escolar_id' => $trayectoria->ciclo_escolar_id,
                    'trayectoria_academica_id' => $trayectoria->id,
                    'fecha_documento' => $constancia->fecha_emision?->toDateString() ?: now()->toDateString(),
                    'folio' => $constancia->folio,
                    'origen' => 'generado',
                    'tipo_movimiento' => 'traslado',
                    'disco' => 'local',
                    'ruta' => $ruta,
                    'nombre_original' => 'constancia-traslado-' . $constancia->folio . '.pdf',
                    'mime_type' => 'application/pdf',
                    'tamano_bytes' => strlen($contenido),
                    'hash_sha256' => hash('sha256', $contenido),
                    'version' => $version,
                    'es_actual' => true,
                    'estado' => 'emitida',
                    'observaciones' => $constancia->observaciones,
                    'subido_por' => $usuarioId,
                    'validado_por' => $usuarioId,
                    'validado_at' => now(),
                ]);

                $constancia->update([
                    'ruta_pdf' => $ruta,
                    'documento_alumno_id' => $documento->id,
                ]);
            });
        } catch (Throwable $exception) {
            Storage::disk('local')->delete($ruta);
            throw $exception;
        }

        return $constancia->refresh();
    }

    private function siguienteFolio(): string
    {
        $anio = now()->year;
        $ultimo = ConstanciaTraslado::query()
            ->where('folio', 'like', "TRAS-{$anio}-%")
            ->lockForUpdate()
            ->orderByDesc('id')
            ->value('folio');
        $consecutivo = $ultimo ? ((int) substr($ultimo, -4)) + 1 : 1;

        return sprintf('TRAS-%d-%04d', $anio, $consecutivo);
    }
}
