<?php

namespace App\Services;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Semestre;
use App\Models\TrayectoriaAcademica;
use Illuminate\Support\Collection;
use Throwable;

class PrepararCicloEscolarService
{
    public function __construct(
        private readonly TrayectoriaAcademicaService $trayectorias,
    ) {}

    /**
     * Crea las trayectorias iniciales del nuevo ciclo con una asignación conservadora:
     * conserva generación y grupo, avanza al grado/semestre siguiente y respeta no promovidos.
     */
    public function ejecutar(
        CicloEscolar $cicloOrigen,
        CicloEscolar $cicloDestino,
        ?int $usuarioId = null
    ): array {
        if ($cicloOrigen->is($cicloDestino)) {
            return $this->resumenVacio();
        }

        $inicioOrigen = (int) $cicloOrigen->inicio_anio;
        $inicioDestino = (int) $cicloDestino->inicio_anio;

        if ($inicioDestino <= $inicioOrigen) {
            return array_merge($this->resumenVacio(), [
                'errores' => ['No se prepararon trayectorias porque el ciclo destino no es posterior al ciclo anterior.'],
            ]);
        }

        if ($inicioDestino !== $inicioOrigen + 1) {
            return array_merge($this->resumenVacio(), [
                'errores' => ['No se prepararon trayectorias porque los ciclos no son consecutivos. Usa Promoción masiva o reconstrucción histórica.'],
            ]);
        }

        $corteDestino = Ciclo::query()->orderBy('id')->first();

        if (!$corteDestino) {
            return array_merge($this->resumenVacio(), [
                'errores' => ['No existe un corte de inicio en la tabla ciclos.'],
            ]);
        }

        $origenes = TrayectoriaAcademica::query()
            ->with([
                'inscripcion' => fn ($query) => $query->withTrashed(),
                'nivel:id,nombre,slug',
                'grado:id,nivel_id,nombre,orden',
                'generacion:id,nivel_id,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'semestre:id,grado_id,numero',
            ])
            ->where('ciclo_escolar_id', $cicloOrigen->id)
            ->where('activo', true)
            ->whereNotIn('estatus', [
                'baja_temporal',
                'baja_definitiva',
                'traslado',
                'suspendido',
                'inactivo',
                'archivado',
                'egresado',
            ])
            ->get()
            ->groupBy('inscripcion_id')
            ->map(fn (Collection $grupo) => $grupo
                ->sortByDesc(fn (TrayectoriaAcademica $t) => sprintf(
                    '%010d|%010d|%010d|%010d',
                    (int) $t->ciclo_id,
                    $t->vigente_en_corte ? 1 : 0,
                    (int) $t->numero_estancia,
                    (int) $t->id,
                ))
                ->first())
            ->filter(fn (?TrayectoriaAcademica $t) => $t && $t->inscripcion)
            ->values();

        $resumen = $this->resumenVacio();

        foreach ($origenes as $origen) {
            $yaExiste = TrayectoriaAcademica::query()
                ->where('inscripcion_id', $origen->inscripcion_id)
                ->where('ciclo_escolar_id', $cicloDestino->id)
                ->where('vigente_en_corte', true)
                ->exists();

            if ($yaExiste) {
                $resumen['existentes']++;
                continue;
            }

            try {
                $resultado = ($origen->estatus === 'no_promovido' || $origen->promovido === false)
                    ? 'no_promovido'
                    : 'promovido';

                $destino = $this->resolverDestino($origen, $resultado);

                if ($destino['egresado']) {
                    $this->trayectorias->egresar(
                        $origen,
                        now(),
                        'Egreso automático al marcar el nuevo ciclo escolar como actual.',
                        $usuarioId
                    );
                    $resumen['egresados']++;
                    continue;
                }

                if (!$destino['grupo']) {
                    $resumen['omitidos']++;
                    $resumen['errores'][] = sprintf(
                        '%s: no existe grupo destino para %s, grado %s, generación %s.',
                        $origen->inscripcion?->matricula ?: 'Sin matrícula',
                        $origen->nivel?->nombre ?: 'nivel',
                        $destino['grado']?->nombre ?: '—',
                        $origen->generacion
                            ? $origen->generacion->anio_ingreso . '-' . $origen->generacion->anio_egreso
                            : '—'
                    );
                    continue;
                }

                $this->trayectorias->promover(
                    $origen,
                    [
                        'ciclo_escolar_id' => $cicloDestino->id,
                        'ciclo_id' => $corteDestino->id,
                        'nivel_id' => $origen->nivel_id,
                        'grado_id' => $destino['grado']->id,
                        'generacion_id' => $origen->generacion_id,
                        'grupo_id' => $destino['grupo']->id,
                        'semestre_id' => $destino['semestre']?->id,
                    ],
                    $resultado,
                    now(),
                    $usuarioId
                );

                if ($resultado === 'no_promovido') {
                    $resumen['no_promovidos']++;
                } else {
                    $resumen['promovidos']++;
                }
            } catch (Throwable $exception) {
                $resumen['omitidos']++;
                $resumen['errores'][] = sprintf(
                    '%s: %s',
                    $origen->inscripcion?->matricula ?: 'Sin matrícula',
                    $exception->getMessage()
                );
            }
        }

        $resumen['procesados'] = $resumen['promovidos']
            + $resumen['no_promovidos']
            + $resumen['egresados'];
        $resumen['errores'] = collect($resumen['errores'])
            ->filter()
            ->unique()
            ->take(8)
            ->values()
            ->all();

        return $resumen;
    }

    private function resolverDestino(TrayectoriaAcademica $origen, string $resultado): array
    {
        $esBachillerato = str_contains(
            mb_strtolower(($origen->nivel?->slug ?? '') . ' ' . ($origen->nivel?->nombre ?? '')),
            'bachillerato'
        );

        if ($resultado === 'no_promovido') {
            $grado = $origen->grado;
            $semestre = $esBachillerato ? $origen->semestre : null;

            return [
                'egresado' => false,
                'grado' => $grado,
                'semestre' => $semestre,
                'grupo' => $this->buscarGrupo($origen, $grado, $semestre),
            ];
        }

        if ($esBachillerato) {
            $numeroActual = (int) ($origen->semestre?->numero ?? 0);
            $semestre = Semestre::query()
                ->with('grado:id,nivel_id,nombre,orden')
                ->whereHas('grado', fn ($query) => $query->where('nivel_id', $origen->nivel_id))
                ->where('numero', '>', $numeroActual)
                ->orderBy('numero')
                ->first();

            if (!$semestre) {
                return [
                    'egresado' => true,
                    'grado' => $origen->grado,
                    'semestre' => $origen->semestre,
                    'grupo' => null,
                ];
            }

            return [
                'egresado' => false,
                'grado' => $semestre->grado,
                'semestre' => $semestre,
                'grupo' => $this->buscarGrupo($origen, $semestre->grado, $semestre),
            ];
        }

        $grado = Grado::query()
            ->where('nivel_id', $origen->nivel_id)
            ->where('orden', '>', (int) ($origen->grado?->orden ?? 0))
            ->orderBy('orden')
            ->first();

        if (!$grado) {
            return [
                'egresado' => true,
                'grado' => $origen->grado,
                'semestre' => null,
                'grupo' => null,
            ];
        }

        return [
            'egresado' => false,
            'grado' => $grado,
            'semestre' => null,
            'grupo' => $this->buscarGrupo($origen, $grado, null),
        ];
    }

    private function buscarGrupo(
        TrayectoriaAcademica $origen,
        ?Grado $grado,
        ?Semestre $semestre
    ): ?Grupo {
        if (!$grado) {
            return null;
        }

        $query = Grupo::query()
            ->where('nivel_id', $origen->nivel_id)
            ->where('grado_id', $grado->id)
            ->where('generacion_id', $origen->generacion_id)
            ->when(
                $semestre,
                fn ($q) => $q->where('semestre_id', $semestre->id),
                fn ($q) => $q->whereNull('semestre_id')
            );

        if ($origen->grupo?->asignacion_grupo_id) {
            $mismoGrupo = (clone $query)
                ->where('asignacion_grupo_id', $origen->grupo->asignacion_grupo_id)
                ->first();

            if ($mismoGrupo) {
                return $mismoGrupo;
            }
        }

        return $query->orderBy('asignacion_grupo_id')->orderBy('id')->first();
    }

    private function resumenVacio(): array
    {
        return [
            'procesados' => 0,
            'promovidos' => 0,
            'no_promovidos' => 0,
            'egresados' => 0,
            'existentes' => 0,
            'omitidos' => 0,
            'errores' => [],
        ];
    }
}
