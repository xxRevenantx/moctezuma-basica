<?php

namespace App\Imports;

use App\Http\Controllers\FichaController;
use App\Models\FichaDescriptiva;
use App\Models\FichaEstado;
use App\Models\FichaHistorial;
use App\Models\Inscripcion;
use App\Models\Periodos;
use App\Services\HtmlSanitizerService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

/** Importación atómica: validación total antes de modificar cualquier ficha. */
class FichaDescriptivaImport implements ToCollection, WithHeadingRow
{
    private ?int $periodoOficialId;

    private const COLUMNAS = [
        'lenguajes' => ['campo_lenguajes', 'lenguajes'],
        'saberes' => ['campo_saberes', 'saberes', 'campo_saberes_y_pensamiento_cientifico', 'saberes_y_pensamiento_cientifico', 'campo_saberes_y_pensamiento_cien_tifico'],
        'etica' => ['campo_etica', 'etica', 'campo_etica_naturaleza_y_sociedades', 'etica_naturaleza_y_sociedades', 'campo_etica_naturaleza_y_socie_dades'],
        'humano' => ['campo_humano', 'humano', 'campo_de_lo_humano_y_lo_comunitario', 'de_lo_humano_y_lo_comunitario', 'campo_de_lo_humano_y_lo_comu_nitario'],
        'recomendaciones' => ['recomendaciones', 'recomendacione_s'],
    ];

    public function __construct(
        private readonly int $nivelId,
        private readonly int $gradoId,
        private readonly ?int $grupoId,
        private readonly ?int $generacionId,
        private readonly int $cicloEscolarId,
        private readonly int $periodo
    ) {
        $this->periodoOficialId = Periodos::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->whereHas('periodoBasica', fn ($query) => $query->where('periodo', $this->periodo))
            ->value('id');
    }

    public function analizarFilas(Collection $rows): array
    {
        $this->verificarMigracion();
        [$resultado] = $this->preparar($rows);
        return $resultado;
    }

    public function collection(Collection $rows): void
    {
        $this->verificarMigracion();
        [$resumen, $operaciones] = $this->preparar($rows);
        if ($resumen['errores'] !== []) {
            throw ValidationException::withMessages([
                'archivo_fichas' => implode("\n", array_slice($resumen['errores'], 0, 20)),
            ]);
        }

        DB::transaction(function () use ($operaciones) {
            foreach ($operaciones as $operacion) {
                /** @var Inscripcion $alumno */
                $alumno = $operacion['alumno'];
                // Comprobar otra vez el bloqueo durante el guardado.
                $estado = FichaEstado::query()->where('inscripcion_id', $alumno->id)
                    ->where('ciclo_escolar_id', $this->cicloEscolarId)
                    ->where('periodo', $this->periodo)->lockForUpdate()->first();
                if ($estado?->estado === 'finalizada') {
                    throw ValidationException::withMessages(['archivo_fichas' => 'Una ficha se finalizó desde la previsualización. Vuelve a validar el archivo.']);
                }
                foreach ($operacion['campos'] as $campo => $descripcion) {
                    $claves = [
                        'inscripcion_id' => $alumno->id,
                        'ciclo_escolar_id' => $this->cicloEscolarId,
                        'periodo' => $this->periodo,
                        'campo' => $campo,
                    ];
                    $anterior = (string) (FichaDescriptiva::query()->where($claves)->lockForUpdate()->value('descripcion') ?? '');
                    if ($anterior === $descripcion) {
                        continue;
                    }
                    FichaDescriptiva::query()->updateOrCreate($claves, [
                        'periodo_id' => $this->periodoOficialId,
                        'nivel_id' => $alumno->nivel_id,
                        'grado_id' => $alumno->grado_id,
                        'grupo_id' => $alumno->grupo_id,
                        'generacion_id' => $alumno->generacion_id,
                        'descripcion' => $descripcion,
                        'capturado_por' => Auth::id(),
                        'fecha_captura' => now(),
                    ]);
                    FichaHistorial::query()->create([
                        'inscripcion_id' => $alumno->id,
                        'ciclo_escolar_id' => $this->cicloEscolarId,
                        'periodo' => $this->periodo,
                        'campo' => $campo,
                        'accion' => 'importacion',
                        'valor_anterior' => $anterior,
                        'valor_nuevo' => $descripcion,
                        'user_id' => Auth::id(),
                    ]);
                }
                if ($estado && $estado->estado === 'en_revision') {
                    $estado->update(['estado' => 'borrador', 'revisado_por' => null, 'revisado_at' => null]);
                }
            }
        });
    }

    private function verificarMigracion(): void
    {
        if (!Schema::hasTable('ficha_estados') || !Schema::hasTable('ficha_historiales')) {
            throw ValidationException::withMessages([
                'archivo_fichas' => 'Primero debes ejecutar la migración de Fichas PRO: php artisan migrate.',
            ]);
        }
    }

    private function preparar(Collection $rows): array
    {
        $errores = [];
        $seen = [];
        $operaciones = [];
        $recuento = ['total' => $rows->count(), 'validos' => 0, 'apartados' => 0, 'reemplazos' => 0, 'errores' => []];
        if (!$this->periodoOficialId) {
            $errores[] = 'No hay un periodo oficial configurado para el ciclo y nivel seleccionados.';
        }
        foreach ($rows as $indice => $row) {
            $numeroFila = $indice + 2;
            $id = (int) ($row['id_inscripcion'] ?? 0);
            if (!$id) {
                $errores[] = "Fila {$numeroFila}: falta ID_INSCRIPCION.";
                continue;
            }
            if (isset($seen[$id])) {
                $errores[] = "Fila {$numeroFila}: ID_INSCRIPCION duplicado ({$id}).";
                continue;
            }
            $seen[$id] = true;
            $alumno = Inscripcion::query()->visiblesEnListas()->whereKey($id)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('nivel_id', $this->nivelId)
                ->where('grado_id', $this->gradoId)
                ->when($this->grupoId, fn ($q) => $q->where('grupo_id', $this->grupoId))
                ->when($this->generacionId, fn ($q) => $q->where('generacion_id', $this->generacionId))
                ->first();
            if (!$alumno) {
                $errores[] = "Fila {$numeroFila}: alumno fuera del grado/grupo/ciclo seleccionado.";
                continue;
            }
            if (FichaEstado::query()->where('inscripcion_id', $id)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('periodo', $this->periodo)->where('estado', 'finalizada')->exists()) {
                $errores[] = "Fila {$numeroFila}: ficha finalizada, no se puede sobrescribir.";
                continue;
            }
            $campos = [];
            foreach (self::COLUMNAS as $campo => $alias) {
                $texto = '';
                foreach ($alias as $columna) {
                    if (filled($row[$columna] ?? null)) {
                        $texto = (string) $row[$columna];
                        break;
                    }
                }
                $texto = app(HtmlSanitizerService::class)->sanitize($texto);
                if ($texto !== '') {
                    if (mb_strlen($texto) > 5000) {
                        $errores[] = "Fila {$numeroFila}: {$campo} supera 5000 caracteres.";
                    } else {
                        $campos[$campo] = $texto;
                    }
                }
            }
            if ($campos === []) {
                $errores[] = "Fila {$numeroFila}: no tiene descripciones para importar.";
                continue;
            }
            $existentes = FichaDescriptiva::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('periodo', $this->periodo)
                ->whereIn('campo', array_keys($campos))
                ->pluck('descripcion', 'campo');
            foreach ($campos as $campo => $texto) {
                if (filled($existentes[$campo] ?? null) && (string) $existentes[$campo] !== $texto) {
                    $recuento['reemplazos']++;
                }
            }
            $operaciones[] = ['alumno' => $alumno, 'campos' => $campos];
            $recuento['validos']++;
            $recuento['apartados'] += count($campos);
        }
        if ($rows->isEmpty()) {
            $errores[] = 'El archivo Excel no contiene alumnos.';
        }
        $recuento['errores'] = $errores;
        return [$recuento, $operaciones];
    }
}
