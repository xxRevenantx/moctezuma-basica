<?php

namespace App\Services;

use App\Models\HorarioAsignacionRegla;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\HorarioDocenteDisponibilidad;
use App\Models\Horario;
use App\Models\AsignacionMateria;
use App\Models\HorarioVersion;
use App\Models\HorarioVersionDetalle;
use App\Models\HorarioRegla;
use Illuminate\Support\Collection;

class HorarioConflictoService
{
    /**
     * @return array{criticos:array<int,array<string,mixed>>,advertencias:array<int,array<string,mixed>>,informativos:array<int,array<string,mixed>>,metricas:array<string,mixed>,puntaje:float}
     */
    public function analizar(HorarioVersion $version): array
    {
        $version->loadMissing([
            'detalles.dia', 'detalles.hora', 'detalles.nivel', 'detalles.grado',
            'detalles.semestre', 'detalles.grupo.asignacionGrupo',
            'detalles.asignacionMateria.materia', 'detalles.profesor',
            'detalles.tallerSesion.taller',
        ]);

        $detalles = $version->detalles;
        $criticos = collect();
        $advertencias = collect();
        $informativos = collect();

        $configuraciones = HorarioDocenteConfiguracion::query()
            ->with(['primeraHora', 'ultimaHora'])
            ->where('ciclo_escolar_id', $version->ciclo_escolar_id)
            ->where(function ($query) use ($version): void {
                $query->whereNull('nivel_id')->orWhere('nivel_id', $version->nivel_id);
            })
            ->get()
            ->sortByDesc(fn ($item) => (int) $item->nivel_id === (int) $version->nivel_id)
            ->unique('persona_id')
            ->keyBy('persona_id');

        $disponibilidades = HorarioDocenteDisponibilidad::query()
            ->whereIn('configuracion_id', $configuraciones->pluck('id'))
            ->get()
            ->keyBy(fn ($item) => $item->configuracion_id.'-'.$item->dia_id.'-'.$item->hora_id);

        $reglasAsignacion = HorarioAsignacionRegla::query()
            ->whereHas('asignacionMateria', fn ($query) => $query
                ->where('ciclo_escolar_id', $version->ciclo_escolar_id)
                ->where('nivel_id', $version->nivel_id)
                ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA))
            ->get()
            ->keyBy('asignacion_materia_id');

        $reglasActivas = HorarioRegla::query()->where('activa', true)->pluck('codigo')->flip();
        $activa = static fn (string $codigo): bool => $reglasActivas->has($codigo);

        if ($activa('grupo_sin_duplicar')) {
            $this->detectarChoquesDeGrupo($detalles, $criticos, $informativos);
        }
        if ($activa('limite_grupos_simultaneos')) {
            $this->detectarSimultaneidadDocente($detalles, $configuraciones, $criticos, $advertencias, $informativos);
            $this->detectarCrucesEntreNiveles($version, $detalles, $criticos, $advertencias, $informativos);
        }
        if ($activa('disponibilidad_docente')) {
            $this->detectarDisponibilidad($detalles, $configuraciones, $disponibilidades, $criticos, $advertencias);
        }
        if ($activa('horas_semanales')) {
            $this->detectarCargaMaterias($detalles, $reglasAsignacion, $criticos, $advertencias);
        }
        if ($activa('reducir_huecos_docente') || $activa('limitar_consecutivas') || $activa('preferencias_horarias')) {
            $this->detectarCargaDocente($detalles, $configuraciones, $criticos, $advertencias);
        }
        $this->detectarRecesosYDatosFaltantes(
            $detalles,
            $criticos,
            $advertencias,
            $activa('recesos_bloqueados')
        );

        $criticos = $criticos->values();
        $advertencias = $advertencias->values();
        $informativos = $informativos->values();

        $puntaje = max(0, round(
            100
            - ($criticos->count() * 18)
            - ($advertencias->count() * 3.5)
            - ($informativos->count() * 0.25),
            2
        ));

        $metricas = [
            'bloques' => $detalles->count(),
            'grupos' => $detalles->pluck('grupo_id')->unique()->count(),
            'docentes' => $detalles->pluck('profesor_id')->filter()->unique()->count(),
            'materias' => $detalles->pluck('asignacion_materia_id')->filter()->unique()->count(),
            'sesiones_compartidas' => $detalles->where('sesion_compartida', true)->count(),
            'traslapes_excepcionales' => $detalles->where('traslape_excepcional', true)->count(),
            'coensenanzas' => $detalles->where('coensenanza', true)->count(),
            'conflictos_criticos' => $criticos->count(),
            'advertencias' => $advertencias->count(),
            'informativos' => $informativos->count(),
        ];

        return [
            'criticos' => $criticos->all(),
            'advertencias' => $advertencias->all(),
            'informativos' => $informativos->all(),
            'metricas' => $metricas,
            'puntaje' => $puntaje,
        ];
    }

    private function detectarChoquesDeGrupo(Collection $detalles, Collection $criticos, Collection $informativos): void
    {
        $detalles
            ->groupBy(fn (HorarioVersionDetalle $detalle) => $detalle->grupo_id.'-'.$detalle->dia_id.'-'.$detalle->hora_id)
            ->filter(fn (Collection $bloque) => $bloque->count() > 1)
            ->each(function (Collection $bloque) use ($criticos, $informativos): void {
                $claves = $bloque->pluck('clave_sesion_compartida')->filter()->unique();
                $coensenanzaValida = $claves->count() === 1
                    && $bloque->every(fn (HorarioVersionDetalle $detalle) => $detalle->coensenanza || $detalle->sesion_compartida);

                $primero = $bloque->first();
                if ($coensenanzaValida) {
                    $informativos->push($this->hallazgo(
                        'coensenanza',
                        'Sesión colaborativa válida',
                        'El grupo recibe apoyo de más de un docente dentro de una misma sesión clasificada.',
                        $primero,
                        ['detalles' => $bloque->pluck('id')->all()]
                    ));
                    return;
                }

                $criticos->push($this->hallazgo(
                    'grupo_duplicado',
                    'Grupo con dos actividades independientes',
                    'El mismo grupo tiene más de una actividad en el mismo bloque. Debe clasificarse como coenseñanza o mover una clase.',
                    $primero,
                    ['detalles' => $bloque->pluck('id')->all()]
                ));
            });
    }

    private function detectarSimultaneidadDocente(
        Collection $detalles,
        Collection $configuraciones,
        Collection $criticos,
        Collection $advertencias,
        Collection $informativos
    ): void {
        $detalles->whereNotNull('profesor_id')
            ->groupBy(fn (HorarioVersionDetalle $detalle) => $detalle->profesor_id.'-'.$detalle->dia_id.'-'.$detalle->hora_id)
            ->filter(fn (Collection $bloque) => $bloque->pluck('grupo_id')->unique()->count() > 1)
            ->each(function (Collection $bloque) use ($configuraciones, $criticos, $advertencias, $informativos): void {
                $primero = $bloque->first();
                $config = $configuraciones->get($primero->profesor_id);
                $maximo = max(1, (int) ($config?->max_grupos_simultaneos ?? 2));
                $grupos = $bloque->pluck('grupo_id')->unique()->count();

                if ($grupos > $maximo) {
                    $criticos->push($this->hallazgo(
                        'limite_simultaneo',
                        'Docente excede sus grupos simultáneos',
                        "El docente aparece en {$grupos} grupos al mismo tiempo y su máximo configurado es {$maximo}.",
                        $primero,
                        ['detalles' => $bloque->pluck('id')->all(), 'maximo' => $maximo]
                    ));
                    return;
                }

                if ($config && ! $config->permitir_multigrado) {
                    $criticos->push($this->hallazgo(
                        'multigrado_no_permitido',
                        'El docente no tiene habilitada la atención multigrado',
                        'La configuración individual del docente impide atender más de un grupo o grado en el mismo bloque.',
                        $primero,
                        ['detalles' => $bloque->pluck('id')->all()]
                    ));
                    return;
                }

                $clavesCompartidas = $bloque->pluck('clave_sesion_compartida')->filter()->unique();
                $esCompartida = $clavesCompartidas->count() === 1
                    && $bloque->every(fn (HorarioVersionDetalle $detalle) => $detalle->sesion_compartida);

                if ($esCompartida) {
                    $materias = $bloque->map(fn (HorarioVersionDetalle $detalle) => mb_strtolower((string) $detalle->nombre_actividad))->unique();
                    if ($materias->count() > 1 && ! ($config?->permitir_materias_distintas ?? false)) {
                        $advertencias->push($this->hallazgo(
                            'multigrado_materias_distintas',
                            'Sesión multigrado con contenidos diferentes',
                            'La sesión compartida es válida, pero reúne materias distintas. Debe mantenerse una justificación administrativa.',
                            $primero,
                            ['detalles' => $bloque->pluck('id')->all()]
                        ));
                    } else {
                        $informativos->push($this->hallazgo(
                            'sesion_compartida',
                            'Docente atendiendo varios grupos en una sesión compartida',
                            "La simultaneidad está clasificada y se mantiene dentro del máximo de {$maximo} grupos.",
                            $primero,
                            ['detalles' => $bloque->pluck('id')->all()]
                        ));
                    }
                    return;
                }

                $esExcepcional = $bloque->every(fn (HorarioVersionDetalle $detalle) => $detalle->traslape_excepcional)
                    && $bloque->every(fn (HorarioVersionDetalle $detalle) => filled($detalle->motivo_traslape_excepcional));

                if ($esExcepcional) {
                    $advertencias->push($this->hallazgo(
                        'traslape_excepcional',
                        'Traslape excepcional autorizado',
                        'El docente tiene actividades diferentes simultáneas. El sistema lo permite porque todas están justificadas y no superan el máximo configurado.',
                        $primero,
                        ['detalles' => $bloque->pluck('id')->all()]
                    ));
                    return;
                }

                $criticos->push($this->hallazgo(
                    'simultaneidad_sin_clasificar',
                    'Simultaneidad docente sin clasificar',
                    'El docente puede atender dos grupos o grados, pero el bloque debe marcarse como sesión compartida o como traslape excepcional con motivo.',
                    $primero,
                    ['detalles' => $bloque->pluck('id')->all()]
                ));
            });
    }

    private function detectarDisponibilidad(
        Collection $detalles,
        Collection $configuraciones,
        Collection $disponibilidades,
        Collection $criticos,
        Collection $advertencias
    ): void {
        $docentesSinConfig = collect();
        foreach ($detalles->whereNotNull('profesor_id') as $detalle) {
            $config = $configuraciones->get($detalle->profesor_id);
            if (! $config) {
                if (! $docentesSinConfig->contains($detalle->profesor_id)) {
                    $advertencias->push($this->hallazgo(
                        'disponibilidad_sin_capturar',
                        'Disponibilidad docente no capturada',
                        'El docente se considera disponible por defecto, pero conviene registrar su matriz semanal.',
                        $detalle
                    ));
                    $docentesSinConfig->push($detalle->profesor_id);
                }
                continue;
            }

            $disponibilidad = $disponibilidades->get($config->id.'-'.$detalle->dia_id.'-'.$detalle->hora_id);
            $estado = $disponibilidad?->estado ?? 'disponible';

            if ($estado === 'no_disponible') {
                $criticos->push($this->hallazgo(
                    'docente_no_disponible',
                    'Clase fuera de la disponibilidad docente',
                    'El bloque está marcado como no disponible para este docente.',
                    $detalle,
                    ['motivo' => $disponibilidad?->motivo]
                ));
            }

            if ($estado === 'autorizacion' && blank($detalle->motivo_autorizacion_disponibilidad)) {
                $criticos->push($this->hallazgo(
                    'disponibilidad_requiere_autorizacion',
                    'Bloque disponible solo con autorización',
                    'La clase puede conservarse, pero requiere registrar el motivo de autorización antes de publicar.',
                    $detalle
                ));
            } elseif ($estado === 'autorizacion') {
                $advertencias->push($this->hallazgo(
                    'disponibilidad_autorizada',
                    'Disponibilidad excepcional autorizada',
                    'El bloque requiere autorización y ya cuenta con una justificación.',
                    $detalle
                ));
            }
        }
    }

    private function detectarCrucesEntreNiveles(
        HorarioVersion $version,
        Collection $detalles,
        Collection $criticos,
        Collection $advertencias,
        Collection $informativos
    ): void {
        $profesores = $detalles->pluck('profesor_id')->filter()->unique();
        if ($profesores->isEmpty()) {
            return;
        }

        $externos = Horario::query()
            ->where('ciclo_escolar_id', $version->ciclo_escolar_id)
            ->where('nivel_id', '!=', $version->nivel_id)
            ->whereIn('profesor_id', $profesores)
            ->with(['dia', 'hora', 'nivel', 'grado', 'grupo.asignacionGrupo', 'profesorAsignado', 'asignacionMateria.materia'])
            ->get();

        foreach ($detalles->whereNotNull('profesor_id') as $detalle) {
            $cruces = $externos->filter(function (Horario $externo) use ($detalle): bool {
                if ((int) $externo->profesor_id !== (int) $detalle->profesor_id) {
                    return false;
                }
                if (mb_strtolower((string) $externo->dia?->dia) !== mb_strtolower((string) $detalle->dia?->dia)) {
                    return false;
                }
                return (string) $externo->hora?->hora_inicio < (string) $detalle->hora?->hora_fin
                    && (string) $externo->hora?->hora_fin > (string) $detalle->hora?->hora_inicio;
            });

            if ($cruces->isEmpty()) {
                continue;
            }

            if ($detalle->traslape_excepcional && filled($detalle->motivo_traslape_excepcional)) {
                $advertencias->push($this->hallazgo(
                    'cruce_nivel_autorizado',
                    'Docente simultáneo en otro nivel',
                    'La coincidencia con otro nivel está marcada como excepcional y requiere aceptación al publicar.',
                    $detalle,
                    ['horarios_externos' => $cruces->pluck('id')->all()]
                ));
                continue;
            }

            $criticos->push($this->hallazgo(
                'cruce_nivel_sin_autorizar',
                'Cruce docente entre niveles sin autorización',
                'El docente ya tiene una clase en otro nivel durante este bloque. Los cruces entre niveles solo se permiten como excepción justificada.',
                $detalle,
                ['horarios_externos' => $cruces->pluck('id')->all()]
            ));
        }
    }

    private function detectarCargaMaterias(
        Collection $detalles,
        Collection $reglasAsignacion,
        Collection $criticos,
        Collection $advertencias
    ): void {
        $porAsignacion = $detalles->whereNotNull('asignacion_materia_id')->groupBy('asignacion_materia_id');

        foreach ($reglasAsignacion as $asignacionId => $regla) {
            // Una coenseñanza produce una fila por docente, pero académicamente
            // sigue siendo una sola sesión de la materia para el grupo.
            $sesiones = $porAsignacion->get($asignacionId, collect())
                ->unique(fn (HorarioVersionDetalle $detalle) => implode('-', [
                    $detalle->asignacion_materia_id,
                    $detalle->grupo_id,
                    $detalle->dia_id,
                    $detalle->hora_id,
                ]))
                ->values();
            $cantidad = $sesiones->count();
            $requeridas = max(1, (int) $regla->sesiones_semanales);
            $referencia = $sesiones->first();

            if ($cantidad !== $requeridas) {
                $texto = $cantidad < $requeridas
                    ? "Faltan ".($requeridas - $cantidad)." sesiones para completar {$requeridas} semanales."
                    : "Existen ".($cantidad - $requeridas)." sesiones adicionales sobre las {$requeridas} configuradas.";

                $criticos->push($this->hallazgoGenerico(
                    'sesiones_semanales',
                    'Carga semanal de materia incompleta',
                    $texto,
                    $referencia,
                    ['asignacion_materia_id' => (int) $asignacionId, 'actuales' => $cantidad, 'requeridas' => $requeridas]
                ));
            }

            foreach ($sesiones->groupBy('dia_id') as $diaId => $delDia) {
                if ($delDia->count() > max(1, (int) $regla->max_sesiones_dia)) {
                    $advertencias->push($this->hallazgo(
                        'max_sesiones_dia',
                        'Materia concentrada en un solo día',
                        'La materia supera el máximo de sesiones diarias configurado.',
                        $delDia->first(),
                        ['cantidad' => $delDia->count(), 'maximo' => (int) $regla->max_sesiones_dia]
                    ));
                }
            }

            $diasDistintos = $sesiones->pluck('dia_id')->unique()->count();
            if ($cantidad > 0 && $diasDistintos < min($requeridas, max(1, (int) $regla->dias_minimos))) {
                $advertencias->push($this->hallazgo(
                    'dias_minimos',
                    'Materia con poca distribución semanal',
                    'Las sesiones están concentradas en menos días de los configurados.',
                    $referencia,
                    ['dias_actuales' => $diasDistintos, 'dias_minimos' => (int) $regla->dias_minimos]
                ));
            }

            if (! $regla->permitir_bloques_consecutivos) {
                foreach ($sesiones->groupBy('dia_id') as $delDia) {
                    $ordenes = $delDia->pluck('hora.orden')->filter()->map(fn ($v) => (int) $v)->sort()->values();
                    for ($i = 1; $i < $ordenes->count(); $i++) {
                        if ($ordenes[$i] === $ordenes[$i - 1] + 1) {
                            $advertencias->push($this->hallazgo(
                                'bloques_consecutivos',
                                'Materia en bloques consecutivos',
                                'La configuración de esta materia solicita distribuir sus sesiones sin continuidad inmediata.',
                                $delDia->first()
                            ));
                            break;
                        }
                    }
                }
            }
        }
    }

    private function detectarCargaDocente(Collection $detalles, Collection $configuraciones, Collection $criticos, Collection $advertencias): void
    {
        foreach ($detalles->whereNotNull('profesor_id')->groupBy('profesor_id') as $profesorId => $delProfesor) {
            $config = $configuraciones->get($profesorId);
            if (! $config) {
                continue;
            }

            foreach ($delProfesor->groupBy('dia_id') as $delDia) {
                $primeraOrden = $config->primeraHora?->orden !== null ? (int) $config->primeraHora->orden : null;
                $ultimaOrden = $config->ultimaHora?->orden !== null ? (int) $config->ultimaHora->orden : null;

                foreach ($delDia as $detalle) {
                    $orden = $detalle->hora?->orden !== null ? (int) $detalle->hora->orden : null;
                    if ($orden === null) {
                        continue;
                    }
                    if ($primeraOrden !== null && $orden < $primeraOrden) {
                        $criticos->push($this->hallazgo(
                            'antes_primera_hora',
                            'Clase antes de la primera hora permitida',
                            'El bloque está fuera de la ventana autorizada del docente.',
                            $detalle,
                            ['primera_hora_id' => $config->primera_hora_id]
                        ));
                    }
                    if ($ultimaOrden !== null && $orden > $ultimaOrden) {
                        $criticos->push($this->hallazgo(
                            'despues_ultima_hora',
                            'Clase después de la última hora permitida',
                            'El bloque está fuera de la ventana autorizada del docente.',
                            $detalle,
                            ['ultima_hora_id' => $config->ultima_hora_id]
                        ));
                    }
                }

                $modulos = $delDia
                    ->unique(fn (HorarioVersionDetalle $detalle) => $detalle->dia_id.'-'.$detalle->hora_id)
                    ->count();
                if ($modulos > (int) $config->max_horas_diarias) {
                    $advertencias->push($this->hallazgo(
                        'max_horas_diarias',
                        'Docente con sobrecarga diaria',
                        "El docente tiene {$modulos} módulos y su máximo configurado es {$config->max_horas_diarias}.",
                        $delDia->first()
                    ));
                }

                $ordenes = $delDia->pluck('hora.orden')->filter()->map(fn ($v) => (int) $v)->unique()->sort()->values();
                $descansoMinimo = max(0, (int) $config->min_descanso_bloques);
                if ($descansoMinimo > 0) {
                    for ($i = 1; $i < $ordenes->count(); $i++) {
                        $libresEntreClases = max(0, $ordenes[$i] - $ordenes[$i - 1] - 1);
                        if ($libresEntreClases < $descansoMinimo) {
                            $advertencias->push($this->hallazgo(
                                'descanso_insuficiente',
                                'Descanso docente menor al configurado',
                                "Entre dos módulos hay {$libresEntreClases} bloques libres y se configuraron {$descansoMinimo}.",
                                $delDia->first(),
                                ['descanso_actual' => $libresEntreClases, 'descanso_minimo' => $descansoMinimo]
                            ));
                            break;
                        }
                    }
                }

                $racha = 1;
                $maxRacha = $ordenes->isEmpty() ? 0 : 1;
                for ($i = 1; $i < $ordenes->count(); $i++) {
                    $racha = $ordenes[$i] === $ordenes[$i - 1] + 1 ? $racha + 1 : 1;
                    $maxRacha = max($maxRacha, $racha);
                }
                if ($maxRacha > (int) $config->max_horas_consecutivas) {
                    $advertencias->push($this->hallazgo(
                        'max_horas_consecutivas',
                        'Docente con demasiadas horas consecutivas',
                        "Se detectó una racha de {$maxRacha} módulos y el máximo configurado es {$config->max_horas_consecutivas}.",
                        $delDia->first()
                    ));
                }

                if ($ordenes->count() > 1) {
                    $huecos = max(0, ($ordenes->last() - $ordenes->first() + 1) - $ordenes->count());
                    if ($huecos > (int) $config->max_huecos_diarios) {
                        $advertencias->push($this->hallazgo(
                            'max_huecos',
                            'Docente con demasiados huecos',
                            "Se detectaron {$huecos} huecos y el máximo preferido es {$config->max_huecos_diarios}.",
                            $delDia->first()
                        ));
                    }
                }
            }
        }
    }

    private function detectarRecesosYDatosFaltantes(Collection $detalles, Collection $criticos, Collection $advertencias, bool $validarRecesos = true): void
    {
        foreach ($detalles as $detalle) {
            if ($validarRecesos && $detalle->asignacionMateria?->materia?->receso) {
                $criticos->push($this->hallazgo(
                    'receso_como_clase',
                    'Receso utilizado como clase',
                    'Una materia marcada como receso no puede publicarse como actividad académica.',
                    $detalle
                ));
            }

            if (! $detalle->asignacion_materia_id && ! $detalle->taller_sesion_id) {
                $criticos->push($this->hallazgo(
                    'actividad_faltante',
                    'Bloque sin actividad',
                    'El bloque no está relacionado con una materia ni con un taller conjunto.',
                    $detalle
                ));
            }

            if (! $detalle->profesor_id) {
                $advertencias->push($this->hallazgo(
                    'profesor_faltante',
                    'Actividad sin docente',
                    'El bloque puede mantenerse, pero se recomienda asignar un docente antes de publicar.',
                    $detalle
                ));
            }
        }
    }

    private function hallazgo(
        string $codigo,
        string $titulo,
        string $mensaje,
        HorarioVersionDetalle $detalle,
        array $extra = []
    ): array {
        return array_merge([
            'codigo' => $codigo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'detalle_id' => $detalle->id,
            'grupo_id' => $detalle->grupo_id,
            'profesor_id' => $detalle->profesor_id,
            'dia_id' => $detalle->dia_id,
            'hora_id' => $detalle->hora_id,
            'grupo' => trim(($detalle->grado?->nombre ?? '').' '.($detalle->grupo?->asignacionGrupo?->nombre ?? '')) ?: 'Grupo no disponible',
            'docente' => $this->nombrePersona($detalle->profesor),
            'dia' => $detalle->dia?->dia ?? 'Día no disponible',
            'hora' => trim(($detalle->hora?->hora_inicio ?? '').' - '.($detalle->hora?->hora_fin ?? '')),
            'actividad' => $detalle->nombre_actividad,
        ], $extra);
    }

    private function hallazgoGenerico(
        string $codigo,
        string $titulo,
        string $mensaje,
        ?HorarioVersionDetalle $detalle,
        array $extra = []
    ): array {
        if ($detalle) {
            return $this->hallazgo($codigo, $titulo, $mensaje, $detalle, $extra);
        }

        return array_merge([
            'codigo' => $codigo,
            'titulo' => $titulo,
            'mensaje' => $mensaje,
            'detalle_id' => null,
            'grupo_id' => null,
            'profesor_id' => null,
            'dia_id' => null,
            'hora_id' => null,
            'grupo' => 'Sin bloques asignados',
            'docente' => 'Sin docente',
            'dia' => '—',
            'hora' => '—',
            'actividad' => 'Asignación sin horario',
        ], $extra);
    }

    private function nombrePersona($persona): string
    {
        if (! $persona) {
            return 'Sin docente';
        }

        return trim(collect([$persona->nombre, $persona->apellido_paterno, $persona->apellido_materno])->filter()->implode(' ')) ?: 'Sin docente';
    }
}
