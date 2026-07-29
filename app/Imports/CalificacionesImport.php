<?php

namespace App\Imports;

use App\Models\BitacoraCalificacion;
use App\Models\Calificacion;
use App\Models\CalificacionCorreccion;
use App\Services\HistorialCalificacionesGeneracionService;
use App\Support\CalificacionBachillerato;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;

class CalificacionesImport implements ToCollection, SkipsEmptyRows
{
    public array $resumen = [
        'creadas' => 0,
        'editadas' => 0,
        'eliminadas' => 0,
        'sin_cambios' => 0,
        'errores' => [],
    ];

    private array $inscripcionIdsPermitidas;
    private array $inscripcionCicloIds;
    private array $contextosGeneracionAsegurados = [];
    private array $materiasPermitidas;

    public function __construct(
        private int $nivelId,
        private int $gradoId,
        private int $grupoId,
        private int $generacionId,
        private ?int $semestreId,
        private int $cicloEscolarId,
        private int $periodoId,
        private bool $esBachillerato,
        private string $tipoPeriodo,
        private int $periodoReferenciaId,
        array $inscripcionIdsPermitidas,
        array $inscripcionCicloIds,
        array $materiasPermitidas,
        private ?int $userId,
        private ?string $ip,
        private ?string $motivo = null,
        private bool $esCorreccionHistorica = false
    ) {
        $this->inscripcionIdsPermitidas = array_map('intval', $inscripcionIdsPermitidas);
        $this->inscripcionCicloIds = collect($inscripcionCicloIds)
            ->mapWithKeys(fn ($id, $inscripcionId) => [(int) $inscripcionId => (int) $id])
            ->filter(fn ($id) => $id > 0)
            ->all();

        $this->materiasPermitidas = collect($materiasPermitidas)
            ->map(function ($materia) {
                return [
                    'id' => (int) ($materia['id'] ?? 0),
                    'materia' => $this->nombreMateria($materia),
                ];
            })
            ->filter(fn($materia) => $materia['id'] > 0 && $materia['materia'] !== '')
            ->values()
            ->all();
    }

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            $encabezados = $rows->get(5);
            $idsMaterias = $rows->get(2);

            if (!$encabezados instanceof Collection) {
                throw ValidationException::withMessages([
                    'archivo_calificaciones' => ['No se encontró la fila de encabezados. Descarga nuevamente la plantilla.'],
                ]);
            }

            // Se leen primero los ids ocultos de la fila 3.
            // Así la importación no depende del nombre visible de la materia,
            // ni de acentos, espacios, cambios de nombre o materias con nombres repetidos.
            $columnasMaterias = $this->obtenerColumnasMaterias($encabezados, $idsMaterias);
            $columnasTecnicas = $this->obtenerColumnasTecnicas($encabezados);

            $this->validarColumnasTecnicas($columnasTecnicas);
            $this->validarMetadatosPlantilla($rows, $columnasTecnicas);

            foreach ($this->materiasPermitidas as $materia) {
                if (!array_key_exists($materia['id'], $columnasMaterias)) {
                    $this->agregarError(6, "Falta la columna de la materia {$materia['materia']}.");
                }
            }

            if (!empty($this->resumen['errores'])) {
                throw ValidationException::withMessages([
                    'archivo_calificaciones' => $this->resumen['errores'],
                ]);
            }

            foreach ($rows as $index => $row) {
                if ($index < 6) {
                    continue;
                }

                $filaExcel = $index + 1;
                $inscripcionId = (int) ($row[0] ?? 0);

                if ($inscripcionId <= 0) {
                    continue;
                }

                if (!in_array($inscripcionId, $this->inscripcionIdsPermitidas, true)) {
                    $this->agregarError($filaExcel, "El alumno con inscripcion_id {$inscripcionId} no pertenece al grupo seleccionado.");
                    continue;
                }

                foreach ($this->materiasPermitidas as $materia) {
                    $asignacionMateriaId = (int) $materia['id'];
                    $columnaMateria = $columnasMaterias[$asignacionMateriaId] ?? null;

                    if ($columnaMateria === null) {
                        continue;
                    }

                    $valorNuevo = $this->normalizarCalificacion($row[$columnaMateria] ?? null);

                    if (!$this->validarCalificacionPermitida($valorNuevo)) {
                        $this->agregarError(
                            $filaExcel,
                            $this->esBachillerato
                                ? "La materia {$materia['materia']} tiene un valor no permitido. En bachillerato usa una calificación de 0 a 10, AC, ED, RA, NP o SD. Los decimales se truncarán a entero."
                                : "La materia {$materia['materia']} tiene un valor no permitido. Usa 0 a 10, AC, ED, RA, NP o SD."
                        );
                        continue;
                    }

                    $this->guardarCelda(
                        inscripcionId: $inscripcionId,
                        asignacionMateriaId: $asignacionMateriaId,
                        valorNuevo: $valorNuevo
                    );
                }
            }

            if (!empty($this->resumen['errores'])) {
                throw ValidationException::withMessages([
                    'archivo_calificaciones' => $this->resumen['errores'],
                ]);
            }
        });
    }

    private function obtenerColumnasMaterias(Collection $encabezados, ?Collection $idsMaterias = null): array
    {
        $columnas = [];
        $idsPermitidos = collect($this->materiasPermitidas)
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        /*
         * Primero se intenta leer la fila técnica 3.
         * En esa fila cada columna de materia tiene su asignacion_materia_id.
         * Esto evita errores cuando el encabezado tiene acentos, espacios,
         * nombres largos o materias repetidas.
         */
        if ($idsMaterias instanceof Collection) {
            foreach ($idsMaterias as $indice => $valor) {
                $asignacionMateriaId = (int) $valor;

                if ($asignacionMateriaId > 0 && in_array($asignacionMateriaId, $idsPermitidos, true)) {
                    $columnas[$asignacionMateriaId] = (int) $indice;
                }
            }
        }

        /*
         * Respaldo para plantillas antiguas: si no existe la fila técnica,
         * se intenta ubicar la columna por el nombre visible de la materia.
         */
        foreach ($this->materiasPermitidas as $materia) {
            $asignacionMateriaId = (int) $materia['id'];

            if (array_key_exists($asignacionMateriaId, $columnas)) {
                continue;
            }

            $materiaNormalizada = $this->normalizarEncabezado($materia['materia']);

            foreach ($encabezados as $indice => $encabezado) {
                if ($this->normalizarEncabezado($encabezado) === $materiaNormalizada) {
                    $columnas[$asignacionMateriaId] = (int) $indice;
                    break;
                }
            }
        }

        return $columnas;
    }

    private function obtenerColumnasTecnicas(Collection $encabezados): array
    {
        $columnas = [];

        foreach ($encabezados as $indice => $encabezado) {
            $encabezadoNormalizado = $this->normalizarEncabezadoTecnico($encabezado);

            if (str_starts_with($encabezadoNormalizado, '__')) {
                $columnas[$encabezadoNormalizado] = (int) $indice;
            }
        }

        return $columnas;
    }

    private function validarColumnasTecnicas(array $columnasTecnicas): void
    {
        $requeridas = [
            '__nivel_id',
            '__grado_id',
            '__grupo_id',
            '__generacion_id',
            '__semestre_id',
            '__ciclo_escolar_id',
            '__periodo_id',
            '__tipo_periodo',
            '__periodo_referencia_id',
        ];

        foreach ($requeridas as $columna) {
            if (!array_key_exists($columna, $columnasTecnicas)) {
                $this->agregarError(6, 'La plantilla no tiene los datos técnicos necesarios. Descarga nuevamente la plantilla desde el periodo actual.');
                return;
            }
        }
    }

    private function validarMetadatosPlantilla(Collection $rows, array $columnasTecnicas): void
    {
        if (!empty($this->resumen['errores'])) {
            return;
        }

        $filaDatos = null;
        $filaExcel = null;

        foreach ($rows as $index => $row) {
            if ($index < 6) {
                continue;
            }

            if ((int) ($row[0] ?? 0) > 0) {
                $filaDatos = $row;
                $filaExcel = $index + 1;
                break;
            }
        }

        if (!$filaDatos instanceof Collection) {
            $this->agregarError(7, 'La plantilla no contiene alumnos para importar.');
            return;
        }

        $esperado = [
            '__nivel_id' => $this->nivelId,
            '__grado_id' => $this->gradoId,
            '__grupo_id' => $this->grupoId,
            '__generacion_id' => $this->generacionId,
            '__semestre_id' => $this->esBachillerato ? (int) $this->semestreId : 0,
            '__ciclo_escolar_id' => $this->cicloEscolarId,
            '__periodo_id' => $this->periodoId,
            '__tipo_periodo' => $this->tipoPeriodo,
            '__periodo_referencia_id' => $this->periodoReferenciaId,
        ];

        foreach ($esperado as $campo => $valorEsperado) {
            $indice = $columnasTecnicas[$campo] ?? null;
            $valorPlantilla = $indice !== null ? ($filaDatos[$indice] ?? null) : null;

            if ($campo === '__tipo_periodo') {
                if ($this->normalizarEncabezadoTecnico($valorPlantilla) !== $this->normalizarEncabezadoTecnico($valorEsperado)) {
                    $this->agregarError($filaExcel, 'La plantilla pertenece a otro tipo de periodo. Descarga la plantilla correcta.');
                }

                continue;
            }

            if ((int) $valorPlantilla !== (int) $valorEsperado) {
                $nombreCampo = str_replace('__', '', $campo);
                $this->agregarError(
                    $filaExcel,
                    "La plantilla no corresponde al {$nombreCampo} seleccionado. Descarga una nueva plantilla con los filtros actuales."
                );
            }
        }
    }

    private function guardarCelda(int $inscripcionId, int $asignacionMateriaId, ?string $valorNuevo): void
    {
        $condiciones = [
            'periodo_id' => $this->periodoId,
            'inscripcion_id' => $inscripcionId,
            'asignacion_materia_id' => $asignacionMateriaId,
        ];

        $calificacionActual = Calificacion::query()
            ->where($condiciones)
            ->first();

        $valorAnterior = $this->normalizarCalificacion($calificacionActual?->calificacion);
        $observacionActual = $calificacionActual?->observacion;

        if ($valorNuevo === $valorAnterior) {
            $this->resumen['sin_cambios']++;
            return;
        }

        if ($valorNuevo === null) {
            if ($calificacionActual) {
                $atributosAnteriores = $calificacionActual->getAttributes();
                $calificacionIdAnterior = (int) $calificacionActual->id;
                $calificacionActual->delete();

                $this->registrarCorreccionHistorica(
                    calificacionId: $calificacionIdAnterior,
                    inscripcionId: $inscripcionId,
                    asignacionMateriaId: $asignacionMateriaId,
                    accion: 'eliminar',
                    anterior: $atributosAnteriores,
                    nuevo: null,
                    observacion: $observacionActual
                );

                $this->crearBitacora(
                    accion: 'eliminar',
                    inscripcionId: $inscripcionId,
                    asignacionMateriaId: $asignacionMateriaId,
                    anterior: $valorAnterior,
                    nuevo: null,
                    observacion: $observacionActual
                );

                $this->resumen['eliminadas']++;
                return;
            }

            $this->resumen['sin_cambios']++;
            return;
        }

        $accion = $calificacionActual ? 'editar' : 'crear';

        $atributosAnteriores = $calificacionActual?->getAttributes();
        $inscripcionCicloId = $this->inscripcionCicloIds[$inscripcionId] ?? null;

        if ($this->esBachillerato && ! isset($this->contextosGeneracionAsegurados[$inscripcionId])) {
            $registro = app(HistorialCalificacionesGeneracionService::class)->asegurarContexto(
                inscripcionId: $inscripcionId,
                cicloEscolarId: $this->cicloEscolarId,
                nivelId: $this->nivelId,
                gradoId: $this->gradoId,
                generacionId: $this->generacionId,
                grupoId: $this->grupoId,
                semestreId: $this->semestreId,
                periodoId: $this->periodoId,
                usuarioId: $this->userId,
                motivo: filled($this->motivo)
                    ? $this->motivo
                    : 'Confirmación del contexto académico mediante importación de calificaciones.',
            );

            $inscripcionCicloId = (int) $registro->id;
            $this->inscripcionCicloIds[$inscripcionId] = $inscripcionCicloId;
            $this->contextosGeneracionAsegurados[$inscripcionId] = true;
        }

        $calificacionGuardada = Calificacion::query()->updateOrCreate(
            $condiciones,
            [
                'inscripcion_ciclo_id' => $inscripcionCicloId,
                'nivel_id' => $this->nivelId,
                'grado_id' => $this->gradoId,
                'grupo_id' => $this->grupoId,
                'ciclo_escolar_id' => $this->cicloEscolarId,
                'generacion_id' => $this->generacionId,
                'semestre_id' => $this->esBachillerato ? $this->semestreId : null,
                'calificacion' => $valorNuevo,
                'valor_numerico' => $this->obtenerValorNumerico($valorNuevo),
                'es_numerica' => $this->esCalificacionNumerica($valorNuevo),
                'clave_especial' => $this->esCalificacionEspecial($valorNuevo) ? $valorNuevo : null,
                'observacion' => $observacionActual,
                'capturado_por' => $this->userId,
                'fecha_captura' => now(),
                'ip_captura' => $this->ip,
            ]
        );

        $this->registrarCorreccionHistorica(
            calificacionId: (int) $calificacionGuardada->id,
            inscripcionId: $inscripcionId,
            asignacionMateriaId: $asignacionMateriaId,
            accion: $accion === 'editar' ? 'actualizar' : 'crear',
            anterior: $atributosAnteriores,
            nuevo: $valorNuevo,
            observacion: $observacionActual,
            atributosNuevos: $calificacionGuardada->getAttributes()
        );

        $this->crearBitacora(
            accion: $accion,
            inscripcionId: $inscripcionId,
            asignacionMateriaId: $asignacionMateriaId,
            anterior: $valorAnterior,
            nuevo: $valorNuevo,
            observacion: $observacionActual
        );

        if ($accion === 'crear') {
            $this->resumen['creadas']++;
            return;
        }

        $this->resumen['editadas']++;
    }


    private function registrarCorreccionHistorica(
        ?int $calificacionId,
        int $inscripcionId,
        int $asignacionMateriaId,
        string $accion,
        ?array $anterior,
        mixed $nuevo,
        ?string $observacion = null,
        ?array $atributosNuevos = null
    ): void {
        if (! $this->esCorreccionHistorica) {
            return;
        }

        $ahora = now();
        $propuesto = $atributosNuevos ?? [
            'accion' => $accion,
            'inscripcion_ciclo_id' => $this->inscripcionCicloIds[$inscripcionId] ?? null,
            'asignacion_materia_id' => $asignacionMateriaId,
            'nivel_id' => $this->nivelId,
            'grado_id' => $this->gradoId,
            'grupo_id' => $this->grupoId,
            'ciclo_escolar_id' => $this->cicloEscolarId,
            'generacion_id' => $this->generacionId,
            'semestre_id' => $this->esBachillerato ? $this->semestreId : null,
            'calificacion' => $nuevo,
            'observacion' => filled($observacion) ? $observacion : null,
        ];
        $propuesto['accion'] = $accion;

        CalificacionCorreccion::query()->create([
            'calificacion_id' => $accion === 'eliminar' ? null : $calificacionId,
            'periodo_id' => $this->periodoId,
            'inscripcion_id' => $inscripcionId,
            'inscripcion_ciclo_id' => $this->inscripcionCicloIds[$inscripcionId] ?? data_get($propuesto, 'inscripcion_ciclo_id'),
            'estado' => CalificacionCorreccion::APLICADA,
            'motivo' => filled($this->motivo) ? trim((string) $this->motivo) : 'Corrección histórica mediante importación de Excel.',
            'valor_anterior' => $anterior,
            'valor_propuesto' => $propuesto,
            'solicitada_por' => $this->userId,
            'solicitada_at' => $ahora,
            'autorizada_por' => $this->userId,
            'autorizada_at' => $ahora,
            'observacion_autorizacion' => 'Corrección histórica directa realizada por administración mediante plantilla Excel.',
            'aplicada_por' => $this->userId,
            'aplicada_at' => $ahora,
        ]);
    }

    private function crearBitacora(
        string $accion,
        int $inscripcionId,
        int $asignacionMateriaId,
        mixed $anterior,
        mixed $nuevo,
        ?string $observacion = null
    ): void {
        BitacoraCalificacion::query()->create([
            'nivel_id' => $this->nivelId,
            'grado_id' => $this->gradoId,
            'grupo_id' => $this->grupoId,
            'generacion_id' => $this->generacionId,
            'semestre_id' => $this->esBachillerato ? $this->semestreId : null,
            'ciclo_escolar_id' => $this->cicloEscolarId,
            'periodo_id' => $this->periodoId,
            'inscripcion_id' => $inscripcionId,
            'inscripcion_ciclo_id' => $this->inscripcionCicloIds[$inscripcionId] ?? null,
            'asignacion_materia_id' => $asignacionMateriaId,
            'user_id' => $this->userId,
            'accion' => $accion,
            'calificacion_anterior' => $anterior,
            'calificacion_nueva' => $nuevo,
            'valor_anterior_numerico' => $this->obtenerValorNumerico($anterior),
            'valor_nuevo_numerico' => $this->obtenerValorNumerico($nuevo),
            'tipo_valor' => $this->tipoValorCalificacion($nuevo),
            'observacion' => filled($observacion) ? $observacion : null,
            'motivo' => filled($this->motivo) ? $this->motivo : 'Importación desde plantilla Excel',
            'ip' => $this->ip,
        ]);
    }

    private function clavesEspecialesPermitidas(): array
    {
        return ['AC', 'ED', 'RA', 'NP', 'SD'];
    }

    private function normalizarCalificacion($valor): ?string
    {
        $valor = strtoupper(trim((string) $valor));

        if ($valor === '') {
            return null;
        }

        if ($this->esBachillerato && is_numeric($valor)) {
            $entero = CalificacionBachillerato::truncarParcial($valor);

            return $entero !== null ? (string) $entero : $valor;
        }

        return $valor;
    }

    private function esCalificacionEspecial($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);
        return $valor !== null && in_array($valor, $this->clavesEspecialesPermitidas(), true);
    }

    private function esCalificacionNumerica($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null || !is_numeric($valor)) {
            return false;
        }

        if ($this->esBachillerato) {
            return CalificacionBachillerato::esEnteraValida($valor);
        }

        $numero = (float) $valor;
        return $numero >= 0 && $numero <= 10;
    }

    private function obtenerValorNumerico($valor): ?float
    {
        return $this->esCalificacionNumerica($valor) ? (float) $this->normalizarCalificacion($valor) : null;
    }

    private function validarCalificacionPermitida($valor): bool
    {
        $valor = $this->normalizarCalificacion($valor);

        if ($valor === null) {
            return true;
        }

        return $this->esCalificacionNumerica($valor) || $this->esCalificacionEspecial($valor);
    }

    private function tipoValorCalificacion($valor): string
    {
        if ($this->esCalificacionNumerica($valor)) {
            return 'numerico';
        }

        if ($this->esCalificacionEspecial($valor)) {
            return 'especial';
        }

        return 'vacio';
    }

    private function nombreMateria(array $materia): string
    {
        $nombre = trim((string) ($materia['materia'] ?? ''));

        if ($nombre === '') {
            return 'MATERIA ' . ((int) ($materia['id'] ?? 0));
        }

        return mb_strtoupper($nombre, 'UTF-8');
    }

    private function normalizarEncabezado($valor): string
    {
        $valor = mb_strtoupper(trim((string) $valor), 'UTF-8');
        $valor = preg_replace('/\s+/', ' ', $valor) ?: '';

        return $valor;
    }

    private function normalizarEncabezadoTecnico($valor): string
    {
        $valor = mb_strtolower(trim((string) $valor), 'UTF-8');
        $valor = preg_replace('/\s+/', '_', $valor) ?: '';

        return $valor;
    }

    private function agregarError(int $fila, string $mensaje): void
    {
        $this->resumen['errores'][] = "Fila {$fila}: {$mensaje}";
    }
}
