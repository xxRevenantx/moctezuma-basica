<?php

namespace App\Livewire\Accion\Concerns;

use App\Exports\CalificacionExport;
use App\Exports\PlantillaCalificacionesImportExport;
use App\Imports\CalificacionesImport;
use App\Models\AsignacionMateria;
use App\Models\BitacoraCalificacion;
use App\Models\Calificacion as ModelsCalificacion;
use App\Models\CalificacionEntrega;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\InscripcionCiclo;
use App\Models\MateriaPromediar;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use App\Services\GroqCalificacionService;
use App\Services\CalificacionCorreccionService;
use App\Services\CicloNivelGateService;
use App\Services\HistorialCalificacionesGeneracionService;
use App\Services\ListaAcademicaService;
use App\Services\TeacherAcademicScopeService;
use App\Services\CalificacionEntregaService;
use App\Support\CalificacionBachillerato;
use App\Support\PromedioExcel;
use App\Support\ReglasMateriaBachillerato;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Livewire\Attributes\Locked;
use Throwable;
use Livewire\Component;
use Maatwebsite\Excel\Facades\Excel;

trait GestionaIntercambioCalificaciones
{
    private function periodoSeleccionadoValido(): bool
    {
        if (blank($this->periodo_id) || blank($this->ciclo_escolar_id)) {
            return false;
        }

        $query = Periodos::query()
            ->whereKey((int) $this->periodo_id)
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id);

        if ($this->esBachillerato) {
            return $query
                ->where('generacion_id', (int) $this->generacion_id)
                ->where('semestre_id', (int) $this->semestre_id)
                ->where('parcial_bachillerato_id', (int) $this->parcial_bachillerato_id)
                ->exists();
        }

        return $query
            ->where('periodo_basica_id', (int) $this->periodo_basica_id)
            ->exists();
    }

    private function assertAdministrativeAcademicToolAccess(): void
    {
        abort_if(
            $this->esProfesorAutenticado,
            403,
            'Las cuentas docentes no tienen acceso a plantillas, importaciones, boletas, reconocimientos ni herramientas de inteligencia artificial.'
        );
    }

    private function tipoPeriodoImportacion(): string
    {
        return $this->esBachillerato ? 'bachillerato' : 'basica';
    }

    private function periodoReferenciaIdImportacion(): int
    {
        return $this->esBachillerato
            ? (int) $this->parcial_bachillerato_id
            : (int) $this->periodo_basica_id;
    }

    private function etiquetaPeriodoParaArchivo(): string
    {
        if (!$this->periodoSeleccionado) {
            return $this->esBachillerato ? 'sin_parcial' : 'sin_periodo';
        }

        if ($this->esBachillerato) {
            return $this->periodoSeleccionado['parcial'] ?? 'sin_parcial';
        }

        return $this->periodoSeleccionado['parcial'] ?? 'sin_periodo';
    }

    private function nombreArchivoPlantillaImportacion(string $nivel, string $grado, string $grupo): string
    {
        $segmentoPeriodo = $this->esBachillerato ? 'PARCIAL' : 'PERIODO';

        return 'PLANTILLA_IMPORTAR_CALIFICACIONES_' .
            Str::slug($nivel, '_') .
            '_GRADO_' . Str::slug($grado, '_') .
            '_GRUPO_' . Str::slug($grupo, '_') .
            '_' . $segmentoPeriodo . '_' . Str::slug($this->etiquetaPeriodoParaArchivo(), '_') .
            '_P' . ((int) $this->periodo_id) .
            '.xlsx';
    }

    private function validarPayloadDocenteActual(bool $lock = false): array
    {
        $payload = [
            'assignment_ids' => collect($this->materias)->pluck('id')->map(fn ($id) => (int) $id)->all(),
            'student_ids' => collect($this->inscripciones)->pluck('inscripcion_id')->map(fn ($id) => (int) $id)->all(),
        ];

        if (! $this->esProfesorAutenticado) {
            return $payload;
        }

        return app(TeacherAcademicScopeService::class)->validateGradePayload(
            user: auth()->user(),
            cicloEscolarId: (int) $this->ciclo_escolar_id,
            nivelId: (int) $this->nivel_id,
            generacionId: (int) $this->generacion_id,
            gradoId: (int) $this->grado_id,
            grupoId: (int) $this->grupo_id,
            semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
            periodoId: (int) $this->periodo_id,
            payloadAssignmentIds: $payload['assignment_ids'],
            payloadStudentIds: $payload['student_ids'],
            lock: $lock,
        );
    }

    private function inscripcionCicloIdsSeguros(array $studentIds): array
    {
        if (! $this->esProfesorAutenticado) {
            return collect($this->inscripciones)
                ->filter(fn ($fila) => ! empty($fila['inscripcion_ciclo_id']))
                ->mapWithKeys(fn ($fila) => [
                    (int) $fila['inscripcion_id'] => (int) $fila['inscripcion_ciclo_id'],
                ])
                ->all();
        }

        return InscripcionCiclo::query()
            ->whereIn('inscripcion_id', $studentIds)
            ->where('ciclo_escolar_id', (int) $this->ciclo_escolar_id)
            ->where('nivel_id', (int) $this->nivel_id)
            ->where('generacion_id', (int) $this->generacion_id)
            ->where('grado_id', (int) $this->grado_id)
            ->where('grupo_id', (int) $this->grupo_id)
            ->when(
                $this->esBachillerato,
                fn ($query) => $query->where('semestre_id', (int) $this->semestre_id),
                fn ($query) => $query->whereNull('semestre_id')
            )
            ->where('estado', '!=', InscripcionCiclo::ESTADO_ANULADO)
            ->latest('id')
            ->get(['id', 'inscripcion_id'])
            ->unique('inscripcion_id')
            ->mapWithKeys(fn (InscripcionCiclo $history) => [
                (int) $history->inscripcion_id => (int) $history->id,
            ])
            ->all();
    }

    public function descargarPlantillaImportacion()
    {
        $this->assertAdministrativeAcademicToolAccess();
        if (!$this->puedeUsarPlantillaImportacion) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de descargar la plantilla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        if (!$this->periodoSeleccionadoValido()) {
            $this->dispatch('swal', [
                'title' => 'El periodo seleccionado no coincide con los filtros actuales.',
                'text' => 'Vuelve a seleccionar el periodo o parcial y descarga nuevamente la plantilla.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $this->validarPayloadDocenteActual();

        $nivel = Nivel::query()->find($this->nivel_id);
        $grado = Grado::query()->find($this->grado_id);
        $grupo = Grupo::withTrashed()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);
        $generacion = Generacion::query()->find($this->generacion_id);
        $semestre = $this->esBachillerato ? Semestre::query()->find($this->semestre_id) : null;

        $contexto = [
            'nivel_id' => (int) $this->nivel_id,
            'grado_id' => (int) $this->grado_id,
            'grupo_id' => (int) $this->grupo_id,
            'generacion_id' => (int) $this->generacion_id,
            'semestre_id' => $this->esBachillerato ? (int) $this->semestre_id : 0,
            'ciclo_escolar_id' => (int) $this->ciclo_escolar_id,
            'periodo_id' => (int) $this->periodo_id,
            'tipo_periodo' => $this->tipoPeriodoImportacion(),
            'periodo_referencia_id' => $this->periodoReferenciaIdImportacion(),
            'numero_materias_promediar' => $this->esBachillerato
                ? (int) ($this->obtenerNumeroMateriasPromediar() ?? 0)
                : null,

            'nivel' => $nivel?->nombre,
            'grado' => $grado?->nombre,
            'grupo' => $this->textoGrupo($grupo),
            'generacion' => $generacion
                ? trim(($generacion->anio_ingreso ?? '') . ' - ' . ($generacion->anio_egreso ?? ''))
                : null,
            'semestre' => $semestre?->numero,
            'periodo' => $this->etiquetaPeriodoParaArchivo(),
        ];

        $nombreArchivo = $this->nombreArchivoPlantillaImportacion(
            nivel: $nivel?->nombre ?? $this->slug_nivel,
            grado: $grado?->nombre ?? 'grado',
            grupo: $this->textoGrupo($grupo)
        );

        return Excel::download(
            new PlantillaCalificacionesImportExport(
                inscripciones: $this->inscripciones,
                materias: $this->materias,
                calificaciones: $this->calificaciones,
                observaciones: $this->observaciones,
                contexto: $contexto
            ),
            $nombreArchivo
        );
    }

    public function importarPlantillaCalificaciones(): void
    {
        $this->assertAdministrativeAcademicToolAccess();
        if (!$this->puedeUsarPlantillaImportacion) {
            $this->addError('archivo_calificaciones', 'Selecciona todos los filtros antes de importar calificaciones.');
            return;
        }

        if (!$this->periodoSeleccionadoValido()) {
            $this->addError('archivo_calificaciones', 'El periodo seleccionado no coincide con los filtros actuales. Vuelve a seleccionar el periodo o parcial.');
            return;
        }

        if (! $this->puedeImportarPlantilla) {
            $this->addError(
                'archivo_calificaciones',
                $this->esConsultaHistorica
                    ? 'Para importar en un ciclo histórico, un administrador debe habilitar primero la corrección e indicar el motivo.'
                    : 'La plantilla incluye alumnos pendientes de confirmar por generación. Administración debe habilitar la corrección e indicar el motivo antes de importar.'
            );
            return;
        }

        $this->validate([
            'archivo_calificaciones' => [
                'required',
                'file',
                'mimes:xlsx,xls',
                'max:10240',
            ],
        ], [
            'archivo_calificaciones.required' => 'Selecciona una plantilla de calificaciones.',
            'archivo_calificaciones.file' => 'El archivo seleccionado no es válido.',
            'archivo_calificaciones.mimes' => 'El archivo debe ser Excel: xlsx o xls.',
            'archivo_calificaciones.max' => 'El archivo no debe pesar más de 10 MB.',
        ]);

        try {
            abort_if(
                $this->esProfesorAutenticado && $this->entregaConfirmadaActual,
                423,
                'La entrega ya fue confirmada y no admite nuevas importaciones.'
            );

            $authorized = $this->validarPayloadDocenteActual();
            $materiasSeguras = collect($this->materias)
                ->whereIn('id', $authorized['assignment_ids'])
                ->values()
                ->all();

            $import = new CalificacionesImport(
                nivelId: (int) $this->nivel_id,
                gradoId: (int) $this->grado_id,
                grupoId: (int) $this->grupo_id,
                generacionId: (int) $this->generacion_id,
                semestreId: $this->esBachillerato ? (int) $this->semestre_id : null,
                cicloEscolarId: (int) $this->ciclo_escolar_id,
                periodoId: (int) $this->periodo_id,
                esBachillerato: $this->esBachillerato,
                tipoPeriodo: $this->tipoPeriodoImportacion(),
                periodoReferenciaId: $this->periodoReferenciaIdImportacion(),
                inscripcionIdsPermitidas: $authorized['student_ids'],
                inscripcionCicloIds: $this->inscripcionCicloIdsSeguros($authorized['student_ids']),
                materiasPermitidas: $materiasSeguras,
                userId: Auth::id(),
                ip: request()->ip(),
                motivo: ($this->esConsultaHistorica || $this->hayAlumnosConContextoPendiente)
                    ? $this->motivoCorreccionCompleto
                    : 'Importación desde plantilla Excel',
                esCorreccionHistorica: $this->esConsultaHistorica || $this->hayAlumnosConContextoPendiente
            );

            Excel::import($import, $this->archivo_calificaciones);

            $this->resumenImportacion = $import->resumen;
            $this->reset('archivo_calificaciones');
            $this->cargarDatos();

            $this->dispatch('swal', [
                'title' => 'Calificaciones importadas correctamente.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (ValidationException $e) {
            $errores = collect($e->errors())
                ->flatten()
                ->values()
                ->all();

            $this->resumenImportacion = [
                'creadas' => 0,
                'editadas' => 0,
                'eliminadas' => 0,
                'sin_cambios' => 0,
                'errores' => $errores,
            ];

            $this->addError(
                'archivo_calificaciones',
                'La plantilla tiene errores. Revisa el resumen mostrado debajo del formulario.'
            );

            $this->dispatch('swal', [
                'title' => 'La plantilla tiene errores.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } catch (Throwable $e) {
            report($e);

            $this->addError(
                'archivo_calificaciones',
                'Ocurrió un error al importar la plantilla. Verifica el archivo e inténtalo nuevamente.'
            );

            $this->dispatch('swal', [
                'title' => 'No se pudo importar la plantilla.',
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function exportarCalificaciones()
    {
        abort_unless(auth()->user()?->is_admin, 403, 'La exportación general está reservada para administración.');

        if (!$this->puedeExportarPdf) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de exportar.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $grupo = Grupo::withTrashed()
            ->with('asignacionGrupo:id,nombre')
            ->find($this->grupo_id);

        $nombreNivel = mb_strtoupper(Nivel::query()->where('id', $this->nivel_id)->value('nombre') ?? $this->slug_nivel ?? 'NIVEL');
        $nombreGrado = Grado::query()->where('id', $this->grado_id)->value('nombre') ?? 'GRADO';
        $nombreGrupo = $this->textoGrupo($grupo);

        $nombreArchivo = 'CALIFICACIONES_' .
            Str::slug($nombreNivel, '_') .
            '_GRADO_' . Str::slug($nombreGrado, '_') .
            '_GRUPO_' . Str::slug($nombreGrupo, '_') .
            '_PERIODO_' . ($this->periodo_id ?? 'SIN_PERIODO') .
            '.xlsx';

        return Excel::download(
            new CalificacionExport(
                nivel_id: $this->nivel_id ? (int) $this->nivel_id : null,
                grado_id: $this->grado_id ? (int) $this->grado_id : null,
                grupo_id: $this->grupo_id ? (int) $this->grupo_id : null,
                periodo_id: $this->periodo_id ? (int) $this->periodo_id : null,
                semestre_id: $this->semestre_id ? (int) $this->semestre_id : null,
                generacion_id: $this->generacion_id ? (int) $this->generacion_id : null,
                esBachillerato: $this->esBachillerato,
                busqueda: $this->busqueda ?? '',
                filtroEstatusHistorico: $this->filtro_estatus_historico,
                filtroRegistros: $this->filtro_registros,
            ),
            $nombreArchivo
        );
    }

    private function alumnoTieneCalificacionesNumericas(int $inscripcionId): bool
    {
        $materiasOrdenadas = $this->obtenerMateriasOrdenadasParaPromedio();

        if ($inscripcionId <= 0 || $materiasOrdenadas->isEmpty()) {
            return false;
        }

        foreach ($materiasOrdenadas as $materia) {
            $asignacionMateriaId = (int) ($materia['id'] ?? 0);

            if ($asignacionMateriaId <= 0) {
                continue;
            }

            $valor = $this->calificaciones[$inscripcionId][$asignacionMateriaId] ?? null;

            if ($this->esCalificacionNumerica($valor)) {
                return true;
            }
        }

        return false;
    }

}
