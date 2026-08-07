<?php

namespace App\Services;

use App\Models\CalendarioEvento;
use App\Models\Periodos;
use App\Models\User;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class CalendarioOperativoService
{
    /** @return Collection<int, array<string, mixed>> */
    public function eventos(
        CarbonInterface $desde,
        CarbonInterface $hasta,
        array $filtros = [],
        ?User $usuario = null,
        bool $incluirSistema = true,
    ): Collection {
        $manuales = Schema::hasTable('calendario_eventos')
            ? $this->eventosManuales($desde, $hasta, $filtros, $usuario)
            : collect();
        $automaticos = $incluirSistema
            ? $this->eventosPeriodos($desde, $hasta, $filtros)
            : collect();

        return $manuales
            ->concat($automaticos)
            ->sortBy(fn (array $evento) => $evento['inicia_at']->format('Y-m-d H:i:s'))
            ->values();
    }

    /** @return Collection<int, array<string, mixed>> */
    private function eventosManuales(
        CarbonInterface $desde,
        CarbonInterface $hasta,
        array $filtros,
        ?User $usuario,
    ): Collection {
        $query = CalendarioEvento::query()
            ->visiblesPara($usuario)
            ->with([
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual',
                'nivel:id,nombre,slug,color',
                'grado:id,nombre',
                'grupo:id,clave,nivel_id,grado_id,generacion_id,semestre_id',
                'responsable:id,name',
                'creador:id,name',
            ])
            ->where(function (Builder $query) use ($desde, $hasta): void {
                $query
                    ->where(function (Builder $normal) use ($desde, $hasta): void {
                        $normal
                            ->where('recurrencia', 'ninguna')
                            ->where('inicia_at', '<=', $hasta->endOfDay())
                            ->where(function (Builder $fin) use ($desde): void {
                                $fin->whereNull('termina_at')->orWhere('termina_at', '>=', $desde->startOfDay());
                            });
                    })
                    ->orWhere(function (Builder $recurrente) use ($desde, $hasta): void {
                        $recurrente
                            ->where('recurrencia', '!=', 'ninguna')
                            ->where('inicia_at', '<=', $hasta->endOfDay())
                            ->where(function (Builder $limite) use ($desde): void {
                                $limite->whereNull('recurrencia_hasta')->orWhere('recurrencia_hasta', '>=', $desde->toDateString());
                            });
                    });
            });

        $this->aplicarFiltros($query, $filtros);

        return $query->get()
            ->flatMap(fn (CalendarioEvento $evento) => $this->expandirRecurrencia($evento, $desde, $hasta));
    }

    private function aplicarFiltros(Builder $query, array $filtros): void
    {
        if (($filtros['tipo'] ?? '') !== '' && ($filtros['tipo'] ?? '') !== 'todos') {
            $query->where('tipo', $filtros['tipo']);
        }

        if (($filtros['estado'] ?? '') !== '' && ($filtros['estado'] ?? '') !== 'todos') {
            $query->where('estado', $filtros['estado']);
        }

        if (! empty($filtros['nivel_id'])) {
            $query->where(function (Builder $alcance) use ($filtros): void {
                $alcance->where('nivel_id', (int) $filtros['nivel_id'])->orWhereNull('nivel_id');
            });
        }

        if (! empty($filtros['ciclo_escolar_id'])) {
            $query->where(function (Builder $alcance) use ($filtros): void {
                $alcance->where('ciclo_escolar_id', (int) $filtros['ciclo_escolar_id'])->orWhereNull('ciclo_escolar_id');
            });
        }

        $buscar = trim((string) ($filtros['buscar'] ?? ''));
        if ($buscar !== '') {
            $query->where(function (Builder $subquery) use ($buscar): void {
                $subquery
                    ->where('titulo', 'like', "%{$buscar}%")
                    ->orWhere('descripcion', 'like', "%{$buscar}%")
                    ->orWhere('ubicacion', 'like', "%{$buscar}%");
            });
        }
    }

    /** @return Collection<int, array<string, mixed>> */
    private function expandirRecurrencia(
        CalendarioEvento $evento,
        CarbonInterface $desde,
        CarbonInterface $hasta,
    ): Collection {
        $inicioBase = CarbonImmutable::instance($evento->inicia_at);
        $finBase = $evento->termina_at
            ? CarbonImmutable::instance($evento->termina_at)
            : $inicioBase;
        $duracionSegundos = max(0, $inicioBase->diffInSeconds($finBase, false));

        if ($evento->recurrencia === 'ninguna') {
            return collect([$this->mapearManual($evento, $inicioBase, $finBase)]);
        }

        $limite = $evento->recurrencia_hasta
            ? CarbonImmutable::instance($evento->recurrencia_hasta)->endOfDay()
            : $hasta->endOfDay();
        $limite = $limite->lessThan($hasta->endOfDay()) ? $limite : CarbonImmutable::instance($hasta)->endOfDay();

        $cursor = $inicioBase;
        $iteraciones = 0;
        $ocurrencias = collect();

        while ($cursor->lessThanOrEqualTo($limite) && $iteraciones < 2200) {
            $fin = $cursor->addSeconds($duracionSegundos);

            if ($cursor->lessThanOrEqualTo($hasta->endOfDay()) && $fin->greaterThanOrEqualTo($desde->startOfDay())) {
                $ocurrencias->push($this->mapearManual($evento, $cursor, $fin));
            }

            $cursor = match ($evento->recurrencia) {
                'diaria' => $cursor->addDay(),
                'semanal' => $cursor->addWeek(),
                'mensual' => $cursor->addMonthNoOverflow(),
                'anual' => $cursor->addYearNoOverflow(),
                default => $limite->addDay(),
            };
            $iteraciones++;
        }

        return $ocurrencias;
    }

    /** @return array<string, mixed> */
    private function mapearManual(
        CalendarioEvento $evento,
        CarbonImmutable $inicio,
        CarbonImmutable $fin,
    ): array {
        return [
            'key' => 'manual:'.$evento->id.':'.$inicio->format('YmdHis'),
            'id' => $evento->id,
            'origen' => 'manual',
            'editable' => true,
            'titulo' => $evento->titulo,
            'descripcion' => $evento->descripcion,
            'tipo' => $evento->tipo,
            'tipo_etiqueta' => $evento->etiqueta_tipo,
            'estado' => $evento->estado,
            'estado_etiqueta' => $evento->etiqueta_estado,
            'prioridad' => $evento->prioridad,
            'audiencia' => $evento->audiencia,
            'inicia_at' => $inicio,
            'termina_at' => $fin,
            'todo_el_dia' => (bool) $evento->todo_el_dia,
            'ubicacion' => $evento->ubicacion,
            'enlace' => $evento->enlace,
            'recurrencia' => $evento->recurrencia,
            'recordatorio_dias' => (int) $evento->recordatorio_dias,
            'ciclo' => $evento->cicloEscolar?->nombre,
            'nivel' => $evento->nivel?->nombre,
            'grado' => $evento->grado?->nombre,
            'grupo' => $evento->grupo?->clave,
            'responsable' => $evento->responsable?->name,
            'creado_por' => $evento->creador?->name,
        ];
    }

    /** @return Collection<int, array<string, mixed>> */
    private function eventosPeriodos(
        CarbonInterface $desde,
        CarbonInterface $hasta,
        array $filtros,
    ): Collection {
        if (in_array(($filtros['tipo'] ?? 'todos'), ['inscripcion', 'reinscripcion', 'boletas', 'cierre', 'horario', 'documentacion', 'reunion', 'administrativo', 'respaldo', 'otro'], true)) {
            return collect();
        }

        $query = Periodos::query()
            ->with([
                'nivel:id,nombre',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'semestre:id,numero',
                'cicloEscolar:id,inicio_anio,fin_anio',
                'periodoBasica:id,periodo,descripcion',
                'parcialBachillerato:id,parcial,descripcion',
                'mesesBasica:id,meses,meses_corto',
                'mesesBachillerato:id,meses,meses_corto',
            ])
            ->where(function (Builder $query) use ($desde, $hasta): void {
                foreach ([
                    ['fecha_inicio', 'fecha_fin'],
                    ['fecha_evaluacion_inicio', 'fecha_evaluacion_fin'],
                    ['fecha_captura_inicio', 'fecha_captura_fin'],
                ] as [$inicio, $fin]) {
                    $query->orWhere(function (Builder $rango) use ($desde, $hasta, $inicio, $fin): void {
                        $rango
                            ->whereNotNull($inicio)
                            ->where($inicio, '<=', $hasta->toDateString())
                            ->where(function (Builder $cierre) use ($desde, $fin): void {
                                $cierre->whereNull($fin)->orWhere($fin, '>=', $desde->toDateString());
                            });
                    });
                }
            });

        if (! empty($filtros['nivel_id'])) {
            $query->where('nivel_id', (int) $filtros['nivel_id']);
        }

        if (! empty($filtros['ciclo_escolar_id'])) {
            $query->where('ciclo_escolar_id', (int) $filtros['ciclo_escolar_id']);
        }

        $buscar = mb_strtolower(trim((string) ($filtros['buscar'] ?? '')));
        $eventos = collect();

        foreach ($query->get() as $periodo) {
            $nombre = $this->nombrePeriodo($periodo);
            $contexto = collect([
                $periodo->nivel?->nombre,
                $periodo->generacion?->etiqueta,
                $periodo->semestre?->numero ? $periodo->semestre->numero.'° semestre' : null,
            ])->filter()->implode(' · ');

            foreach ([
                ['academico', 'Periodo académico', $periodo->fecha_inicio, $periodo->fecha_fin, 'normal'],
                ['evaluacion', 'Aplicación de evaluación', $periodo->fecha_evaluacion_inicio, $periodo->fecha_evaluacion_fin, 'alta'],
                ['evaluacion', 'Captura de calificaciones', $periodo->fecha_captura_inicio, $periodo->fecha_captura_fin, 'critica'],
            ] as [$tipo, $titulo, $inicio, $fin, $prioridad]) {
                if (! $inicio) {
                    continue;
                }

                if (($filtros['tipo'] ?? 'todos') !== 'todos' && ($filtros['tipo'] ?? '') !== $tipo) {
                    continue;
                }

                $tituloCompleto = $titulo.': '.$nombre;
                if ($buscar !== '' && ! str_contains(mb_strtolower($tituloCompleto.' '.$contexto), $buscar)) {
                    continue;
                }

                $inicioCarbon = CarbonImmutable::instance($inicio)->startOfDay();
                $finCarbon = $fin ? CarbonImmutable::instance($fin)->endOfDay() : $inicioCarbon->endOfDay();
                $estado = $finCarbon->isPast() ? 'completado' : ($inicioCarbon->isPast() ? 'en_curso' : 'programado');

                if (($filtros['estado'] ?? 'todos') !== 'todos' && ($filtros['estado'] ?? '') !== $estado) {
                    continue;
                }

                $eventos->push([
                    'key' => 'sistema:periodo:'.$periodo->id.':'.$tipo.':'.$inicioCarbon->format('Ymd'),
                    'id' => $periodo->id,
                    'origen' => 'sistema',
                    'editable' => false,
                    'titulo' => $tituloCompleto,
                    'descripcion' => $contexto ?: 'Fecha generada desde la configuración de periodos.',
                    'tipo' => $tipo,
                    'tipo_etiqueta' => $tipo === 'evaluacion' ? 'Evaluación' : 'Académico',
                    'estado' => $estado,
                    'estado_etiqueta' => match ($estado) {
                        'completado' => 'Completado',
                        'en_curso' => 'En curso',
                        default => 'Programado',
                    },
                    'prioridad' => $prioridad,
                    'audiencia' => 'todos',
                    'inicia_at' => $inicioCarbon,
                    'termina_at' => $finCarbon,
                    'todo_el_dia' => true,
                    'ubicacion' => null,
                    'enlace' => null,
                    'recurrencia' => 'ninguna',
                    'recordatorio_dias' => 0,
                    'ciclo' => $periodo->cicloEscolar?->nombre,
                    'nivel' => $periodo->nivel?->nombre,
                    'grado' => null,
                    'grupo' => null,
                    'responsable' => null,
                    'creado_por' => 'Configuración académica',
                ]);
            }
        }

        return $eventos;
    }

    private function nombrePeriodo(Periodos $periodo): string
    {
        if ($periodo->periodoBasica) {
            return trim((string) ($periodo->periodoBasica->descripcion ?: $periodo->periodoBasica->periodo));
        }

        if ($periodo->parcialBachillerato) {
            return trim((string) ($periodo->parcialBachillerato->descripcion ?: $periodo->parcialBachillerato->parcial));
        }

        if ($periodo->mesesBasica) {
            return (string) $periodo->mesesBasica->meses;
        }

        if ($periodo->mesesBachillerato) {
            return (string) $periodo->mesesBachillerato->meses;
        }

        return 'Periodo configurado';
    }
}
