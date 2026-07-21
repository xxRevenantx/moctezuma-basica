<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\CicloEscolarNivel;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\PersonaNivelCiclo;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaRole;
use App\Models\PlantillaPersonalNivel;
use App\Models\RolePersona;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PlantillaPersonalCicloService
{
    public function cicloActual(): ?CicloEscolar
    {
        return CicloEscolar::query()->where('es_actual', true)->first()
            ?: CicloEscolar::query()->latest('id')->first();
    }

    public function plantilla(int $cicloEscolarId, int $nivelId, bool $crear = true): ?PlantillaPersonalNivel
    {
        $query = PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId);

        $plantilla = $query->first();
        if ($plantilla || !$crear) {
            return $plantilla;
        }

        $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);
        $cerrada = !is_null($ciclo->cerrado_at);

        return PlantillaPersonalNivel::query()->create([
            'ciclo_escolar_id' => $cicloEscolarId,
            'nivel_id' => $nivelId,
            'estado' => $cerrada
                ? PlantillaPersonalNivel::ESTADO_CERRADA
                : PlantillaPersonalNivel::ESTADO_BORRADOR,
            'cerrada_at' => $cerrada ? $ciclo->cerrado_at : null,
            'observaciones' => $cerrada
                ? 'Plantilla histórica creada en modo consulta. Requiere reapertura administrativa para capturar información.'
                : null,
        ]);
    }

    /** @return Collection<int, PlantillaPersonalNivel> */
    public function plantillasDelCiclo(int $cicloEscolarId): Collection
    {
        $niveles = Nivel::query()->orderBy('id')->get();

        return $niveles->map(fn (Nivel $nivel) => $this->plantilla($cicloEscolarId, $nivel->id))
            ->filter();
    }


    public function recalcularCiclo(int $cicloEscolarId): Collection
    {
        return $this->plantillasDelCiclo($cicloEscolarId)
            ->each(fn (PlantillaPersonalNivel $plantilla) => $this->actualizarDiagnostico($plantilla));
    }

    public function prepararCiclo(int $cicloEscolarId): array
    {
        $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);

        if ($ciclo->cerrado_at) {
            throw ValidationException::withMessages([
                'cicloEscolarId' => 'El ciclo está cerrado. Reabre primero la plantilla del nivel con un motivo administrativo.',
            ]);
        }

        $cicloAnterior = CicloEscolar::query()
            ->where('inicio_anio', '<', $ciclo->inicio_anio)
            ->orderByDesc('inicio_anio')
            ->first();

        $creadas = 0;
        $copiadas = 0;
        $pendientes = 0;

        DB::transaction(function () use ($ciclo, $cicloAnterior, &$creadas, &$copiadas, &$pendientes) {
            foreach (Nivel::query()->orderBy('id')->get() as $nivel) {
                $plantilla = $this->plantilla($ciclo->id, $nivel->id);
                $creadas++;

                if ($plantilla->membresias()->exists() || !$cicloAnterior) {
                    $this->actualizarDiagnostico($plantilla);
                    continue;
                }

                $origen = $this->plantilla($cicloAnterior->id, $nivel->id, false);
                if (!$origen) {
                    $this->actualizarDiagnostico($plantilla);
                    continue;
                }

                $plantilla->update(['copiada_de_id' => $origen->id]);
                $origen->load(['membresias.personaNivel', 'membresias.detalles.personaRole.rolePersona']);

                foreach ($origen->membresias->where('estado', PersonaNivelCiclo::ESTADO_ACTIVO) as $membresiaOrigen) {
                    $membresia = PersonaNivelCiclo::query()->create([
                        'plantilla_personal_nivel_id' => $plantilla->id,
                        'persona_nivel_id' => $membresiaOrigen->persona_nivel_id,
                        'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
                        'orden' => $membresiaOrigen->orden,
                        'fecha_inicio' => $ciclo->inicio_anio . '-07-01',
                        'copiado_desde_id' => $membresiaOrigen->id,
                    ]);

                    foreach ($membresiaOrigen->detalles->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO) as $detalleOrigen) {
                        $rol = $detalleOrigen->personaRole?->rolePersona;
                        $requiereRevision = (bool) $detalleOrigen->grupo_id || (bool) $rol?->requiere_grupo;

                        PersonaNivelDetalle::query()->create([
                            'persona_nivel_id' => $membresiaOrigen->persona_nivel_id,
                            'persona_nivel_ciclo_id' => $membresia->id,
                            'persona_role_id' => $detalleOrigen->persona_role_id,
                            'grado_id' => $requiereRevision ? $detalleOrigen->grado_id : null,
                            'grupo_id' => null,
                            'fecha_inicio' => $ciclo->inicio_anio . '-07-01',
                            'estado' => PersonaNivelDetalle::ESTADO_ACTIVO,
                            'confirmado' => !$requiereRevision,
                            'pendiente_motivo' => $requiereRevision ? 'Confirmar grupo para el nuevo ciclo.' : null,
                            'materia_manual' => $detalleOrigen->materia_manual,
                            'horas_administrativas' => $detalleOrigen->horas_administrativas,
                            'actividad_administrativa_id' => $detalleOrigen->actividad_administrativa_id,
                            'actividad_administrativa_manual' => $detalleOrigen->actividad_administrativa_manual,
                            'limite_horas_semanales' => $detalleOrigen->limite_horas_semanales,
                            'orden' => $detalleOrigen->orden,
                            'observaciones' => $requiereRevision
                                ? 'Asignación propuesta desde ' . $cicloAnterior->nombre . '; requiere confirmación.'
                                : $detalleOrigen->observaciones,
                        ]);

                        $copiadas++;
                        $pendientes += $requiereRevision ? 1 : 0;
                    }
                }

                $this->actualizarDiagnostico($plantilla);
            }
        });

        return compact('creadas', 'copiadas', 'pendientes');
    }

    public function asegurarEditable(PlantillaPersonalNivel $plantilla): void
    {
        if (!$plantilla->esEditable()) {
            throw ValidationException::withMessages([
                'plantilla' => 'La plantilla está cerrada. Reábrela con un motivo antes de modificarla.',
            ]);
        }
    }

    public function cambiarEstado(PlantillaPersonalNivel $plantilla, string $estado, ?string $motivo = null): PlantillaPersonalNivel
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $permitidos = [
            PlantillaPersonalNivel::ESTADO_BORRADOR,
            PlantillaPersonalNivel::ESTADO_REVISION,
            PlantillaPersonalNivel::ESTADO_PUBLICADA,
            PlantillaPersonalNivel::ESTADO_CERRADA,
        ];

        if (!in_array($estado, $permitidos, true)) {
            throw ValidationException::withMessages(['estadoPlantilla' => 'Estado de plantilla no válido.']);
        }

        $diagnostico = $this->diagnostico($plantilla);
        if ($estado === PlantillaPersonalNivel::ESTADO_PUBLICADA && ($diagnostico['criticos'] ?? 0) > 0) {
            throw ValidationException::withMessages([
                'estadoPlantilla' => 'No se puede publicar: corrige primero los errores críticos de la plantilla.',
            ]);
        }

        if ($plantilla->estado === PlantillaPersonalNivel::ESTADO_CERRADA && $estado !== PlantillaPersonalNivel::ESTADO_CERRADA) {
            if (blank($motivo) || mb_strlen(trim($motivo)) < 10) {
                throw ValidationException::withMessages([
                    'motivoReapertura' => 'Escribe un motivo de al menos 10 caracteres para reabrir el ciclo cerrado.',
                ]);
            }

            $plantilla->fill([
                'reabierta_at' => now(),
                'reabierta_por' => auth()->id(),
                'motivo_reapertura' => trim($motivo),
            ]);
        }

        $plantilla->estado = $estado;
        if ($estado === PlantillaPersonalNivel::ESTADO_PUBLICADA) {
            $plantilla->publicada_at = now();
            $plantilla->publicada_por = auth()->id();
            $plantilla->cerrada_at = null;
            $plantilla->cerrada_por = null;
        }
        if ($estado === PlantillaPersonalNivel::ESTADO_CERRADA) {
            $plantilla->cerrada_at = now();
            $plantilla->cerrada_por = auth()->id();
        }
        $plantilla->diagnostico = $diagnostico;
        $plantilla->save();

        $this->sincronizarNivelCiclo($plantilla);

        return $plantilla->refresh();
    }

    public function membresia(PlantillaPersonalNivel $plantilla, PersonaNivel $personaNivel): PersonaNivelCiclo
    {
        $this->asegurarEditable($plantilla);
        $plantilla->loadMissing('cicloEscolar');

        return PersonaNivelCiclo::query()->firstOrCreate([
            'plantilla_personal_nivel_id' => $plantilla->id,
            'persona_nivel_id' => $personaNivel->id,
        ], [
            'estado' => PersonaNivelCiclo::ESTADO_ACTIVO,
            'orden' => ((int) $plantilla->membresias()->max('orden')) + 1,
            'fecha_inicio' => $plantilla->cicloEscolar?->inicio_anio . '-07-01',
        ]);
    }

    public function validarAsignacion(
        PlantillaPersonalNivel $plantilla,
        RolePersona $rol,
        ?int $gradoId,
        ?int $grupoId,
        ?int $detalleIgnorarId = null
    ): ?Grupo {
        $this->asegurarEditable($plantilla);
        $nivelId = (int) $plantilla->nivel_id;
        $esBachillerato = $plantilla->nivel?->slug === 'bachillerato';

        if ($esBachillerato && !$rol->aplica_bachillerato) {
            throw ValidationException::withMessages(['persona_role_id' => 'La función seleccionada no aplica a Bachillerato.']);
        }

        if (!$rol->permiteAsignacionGrupo() && ($gradoId || $grupoId)) {
            throw ValidationException::withMessages(['grupo_id' => 'Esta función es general y no permite grado o grupo.']);
        }

        if ($rol->requiere_grupo && (!$gradoId || !$grupoId)) {
            throw ValidationException::withMessages(['grupo_id' => 'Esta función requiere seleccionar grado/semestre y grupo.']);
        }

        if (($gradoId && !$grupoId) || (!$gradoId && $grupoId)) {
            throw ValidationException::withMessages(['grupo_id' => 'Grado/semestre y grupo deben seleccionarse juntos.']);
        }

        $grupo = null;
        if ($grupoId) {
            $grupo = Grupo::query()
                ->with(['generacion', 'grado', 'semestre'])
                ->whereKey($grupoId)
                ->where('ciclo_escolar_id', $plantilla->ciclo_escolar_id)
                ->where('nivel_id', $nivelId)
                ->where('estado', 'activo')
                ->where('grado_id', $gradoId)
                ->first();

            if (!$grupo) {
                throw ValidationException::withMessages([
                    'grupo_id' => 'El grupo no pertenece al ciclo, nivel o grado/semestre seleccionado.',
                ]);
            }
        }

        return $grupo;
    }

    public function bloquearDuplicado(
        PersonaNivelCiclo $membresia,
        int $personaRoleId,
        ?int $gradoId,
        ?int $grupoId,
        ?int $ignorarId = null
    ): void {
        $duplicado = PersonaNivelDetalle::query()
            ->where('persona_nivel_ciclo_id', $membresia->id)
            ->where('persona_role_id', $personaRoleId)
            ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
            ->whereNull('archivado_at')
            ->when($gradoId, fn (Builder $q) => $q->where('grado_id', $gradoId), fn (Builder $q) => $q->whereNull('grado_id'))
            ->when($grupoId, fn (Builder $q) => $q->where('grupo_id', $grupoId), fn (Builder $q) => $q->whereNull('grupo_id'))
            ->when($ignorarId, fn (Builder $q) => $q->whereKeyNot($ignorarId))
            ->exists();

        if ($duplicado) {
            throw ValidationException::withMessages([
                'persona_role_id' => 'Esta persona ya tiene la misma función, grado y grupo en el ciclo seleccionado.',
            ]);
        }

        $rol = PersonaRole::query()->with('rolePersona')->find($personaRoleId)?->rolePersona;
        if ($rol && !$rol->permite_varios_grupos) {
            $otraAsignacion = PersonaNivelDetalle::query()
                ->where('persona_nivel_ciclo_id', $membresia->id)
                ->where('persona_role_id', $personaRoleId)
                ->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)
                ->whereNull('archivado_at')
                ->when($ignorarId, fn (Builder $q) => $q->whereKeyNot($ignorarId))
                ->exists();

            if ($otraAsignacion) {
                throw ValidationException::withMessages([
                    'persona_role_id' => 'Esta función no permite varias asignaciones dentro del mismo nivel y ciclo.',
                ]);
            }
        }
    }

    public function cerrarYCrearCambio(
        PersonaNivelDetalle $detalle,
        array $nuevosDatos,
        string $motivo
    ): PersonaNivelDetalle {
        if (blank($motivo)) {
            throw ValidationException::withMessages(['motivoCambio' => 'El motivo del cambio es obligatorio.']);
        }

        return DB::transaction(function () use ($detalle, $nuevosDatos, $motivo) {
            $detalle->update([
                'estado' => PersonaNivelDetalle::ESTADO_BAJA,
                'fecha_fin' => now()->toDateString(),
                'fecha_baja' => now()->toDateString(),
                'motivo_baja' => $motivo,
            ]);

            $nuevo = $detalle->replicate([
                'fecha_fin', 'fecha_baja', 'motivo_baja', 'archivado_at', 'archivado_por', 'motivo_archivo',
            ]);
            $nuevo->fill($nuevosDatos);
            $nuevo->estado = PersonaNivelDetalle::ESTADO_ACTIVO;
            $nuevo->fecha_inicio = now()->toDateString();
            $nuevo->fecha_fin = null;
            $nuevo->fecha_baja = null;
            $nuevo->motivo_baja = null;
            $nuevo->confirmado = true;
            $nuevo->pendiente_motivo = null;
            $nuevo->save();

            return $nuevo;
        });
    }

    public function archivarDetalle(PersonaNivelDetalle $detalle, string $motivo): void
    {
        if (blank($motivo) || mb_strlen(trim($motivo)) < 5) {
            throw ValidationException::withMessages(['motivoArchivo' => 'Indica el motivo para archivar la asignación.']);
        }

        $detalle->update([
            'estado' => PersonaNivelDetalle::ESTADO_BAJA,
            'fecha_fin' => $detalle->fecha_fin ?: now()->toDateString(),
            'fecha_baja' => $detalle->fecha_baja ?: now()->toDateString(),
            'motivo_baja' => trim($motivo),
            'archivado_at' => now(),
            'archivado_por' => auth()->id(),
            'motivo_archivo' => trim($motivo),
        ]);
    }

    public function detallesQuery(int $cicloEscolarId, ?int $nivelId = null): Builder
    {
        return PersonaNivelDetalle::query()
            ->whereNull('archivado_at')
            ->whereHas('cicloAsignacion.plantilla', function (Builder $q) use ($cicloEscolarId, $nivelId) {
                $q->where('ciclo_escolar_id', $cicloEscolarId)
                    ->when($nivelId, fn (Builder $nivel) => $nivel->where('nivel_id', $nivelId));
            });
    }

    public function plantillaPublicada(int $cicloEscolarId, int $nivelId): ?PlantillaPersonalNivel
    {
        return PlantillaPersonalNivel::query()
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->where('nivel_id', $nivelId)
            ->whereIn('estado', [PlantillaPersonalNivel::ESTADO_PUBLICADA, PlantillaPersonalNivel::ESTADO_CERRADA])
            ->first();
    }

    public function diagnostico(PlantillaPersonalNivel $plantilla): array
    {
        $plantilla->loadMissing(['cicloEscolar', 'nivel']);
        $detalles = PersonaNivelDetalle::query()
            ->with(['cabecera.persona', 'personaRole.rolePersona', 'grupo.generacion'])
            ->whereHas('cicloAsignacion', fn (Builder $q) => $q->where('plantilla_personal_nivel_id', $plantilla->id))
            ->whereNull('archivado_at')
            ->get();

        $errores = collect();
        $advertencias = collect();

        foreach ($detalles as $detalle) {
            $persona = $detalle->cabecera?->persona;
            $nombre = trim(collect([$persona?->nombre, $persona?->apellido_paterno, $persona?->apellido_materno])->filter()->implode(' '));
            $rol = $detalle->personaRole?->rolePersona;

            if (!$rol) {
                $errores->push("{$nombre}: función inexistente.");
                continue;
            }

            if (!$detalle->confirmado) {
                $errores->push("{$nombre}: asignación pendiente de confirmar.");
            }

            if ($rol->requiere_grupo && !$detalle->grupo_id) {
                $errores->push("{$nombre}: {$rol->nombre} requiere grupo.");
            }

            if ($detalle->grupo_id) {
                if (!$detalle->grupo?->ciclo_escolar_id || (int) $detalle->grupo->ciclo_escolar_id !== (int) $plantilla->ciclo_escolar_id) {
                    $errores->push("{$nombre}: el grupo pertenece a otro ciclo o no tiene ciclo.");
                }
                if (!$detalle->grupo?->generacion_id) {
                    $errores->push("{$nombre}: el grupo no tiene generación.");
                }
            }

            $cabecera = $detalle->cabecera;
            if (!$cabecera?->ingreso_seg || !$cabecera?->ingreso_sep || !$cabecera?->ingreso_ct) {
                $advertencias->push("{$nombre}: faltan una o más fechas SEG, SEP o C.T. del nivel.");
            }
        }

        $duplicados = $detalles
            ->groupBy(fn (PersonaNivelDetalle $d) => implode('|', [
                $d->persona_nivel_ciclo_id,
                $d->persona_role_id,
                $d->grado_id ?: 0,
                $d->grupo_id ?: 0,
            ]))
            ->filter(fn (Collection $items) => $items->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)->count() > 1);

        foreach ($duplicados as $items) {
            $persona = $items->first()?->cabecera?->persona;
            $nombre = trim(collect([$persona?->nombre, $persona?->apellido_paterno, $persona?->apellido_materno])->filter()->implode(' '));
            $errores->push("{$nombre}: existen asignaciones duplicadas.");
        }

        $grupos = Grupo::query()
            ->where('ciclo_escolar_id', $plantilla->ciclo_escolar_id)
            ->where('nivel_id', $plantilla->nivel_id)
            ->where('estado', 'activo')
            ->count();

        return [
            'criticos' => $errores->unique()->count(),
            'advertencias' => $advertencias->unique()->count(),
            'errores' => $errores->unique()->values()->all(),
            'avisos' => $advertencias->unique()->values()->all(),
            'personas' => $plantilla->membresias()->where('estado', 'activo')->count(),
            'asignaciones' => $detalles->where('estado', PersonaNivelDetalle::ESTADO_ACTIVO)->count(),
            'pendientes' => $detalles->where('confirmado', false)->count(),
            'grupos_activos' => $grupos,
            'estado' => $plantilla->estado,
            'actualizado_at' => now()->toIso8601String(),
        ];
    }

    public function actualizarDiagnostico(PlantillaPersonalNivel $plantilla): array
    {
        $diagnostico = $this->diagnostico($plantilla);
        $plantilla->forceFill(['diagnostico' => $diagnostico])->saveQuietly();
        $this->sincronizarNivelCiclo($plantilla);

        return $diagnostico;
    }

    private function sincronizarNivelCiclo(PlantillaPersonalNivel $plantilla): void
    {
        $diagnostico = $plantilla->diagnostico ?: $this->diagnostico($plantilla);
        $estado = match ($plantilla->estado) {
            PlantillaPersonalNivel::ESTADO_CERRADA => 'cerrado',
            PlantillaPersonalNivel::ESTADO_PUBLICADA => (($diagnostico['criticos'] ?? 0) === 0 ? 'listo' : 'en_preparacion'),
            default => 'en_preparacion',
        };

        $registro = CicloEscolarNivel::query()->firstOrNew([
            'ciclo_escolar_id' => $plantilla->ciclo_escolar_id,
            'nivel_id' => $plantilla->nivel_id,
        ]);

        $diagnosticoGeneral = is_array($registro->diagnostico) ? $registro->diagnostico : [];
        $diagnosticoGeneral['plantilla_personal'] = $diagnostico;

        $registro->fill([
            'estado' => $estado,
            'diagnostico' => $diagnosticoGeneral,
            'preparado_at' => $estado === 'listo' ? now() : $registro->preparado_at,
            'preparado_por' => $estado === 'listo' ? auth()->id() : $registro->preparado_por,
        ])->save();
    }
}
