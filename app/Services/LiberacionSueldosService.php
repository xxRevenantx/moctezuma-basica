<?php

namespace App\Services;

use App\Models\cicloEscolar;
use App\Models\Director;
use App\Models\Escuela;
use App\Models\LiberacionSueldo;
use App\Models\LiberacionSueldoConfiguracion;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\PersonaNivel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class LiberacionSueldosService
{
    public function personalActivoQuery(): Builder
    {
        return PersonaNivel::query()
            ->with([
                'persona',
                'nivel.director',
                'nivel.supervisor',
                'detalles.personaRole.rolePersona',
            ])
            ->where('estado', PersonaNivel::ESTADO_ACTIVO)
            ->whereHas('persona', fn(Builder $query) => $query
                ->where('status', true)
                ->where(function (Builder $estado) {
                    $estado->whereNull('estado_laboral')->orWhere('estado_laboral', 'activo');
                }))
            ->whereHas('detalles', fn(Builder $query) => $query->where('estado', 'activo'));
    }

    /** @return Collection<int, Persona> */
    public function directoresPlantilla(int $nivelId): Collection
    {
        return Persona::query()
            ->whereHas('personaNiveles', function (Builder $cabecera) use ($nivelId) {
                $cabecera->where('nivel_id', $nivelId)
                    ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                    ->whereHas('detalles', function (Builder $detalle) {
                        $detalle->where('estado', 'activo')
                            ->whereHas('personaRole.rolePersona', function (Builder $rol) {
                                $rol->where('slug', 'like', '%director%')
                                    ->orWhere('nombre', 'like', '%Director%');
                            });
                    });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    /** @return Collection<int, Director> */
    public function supervisoresNivel(Nivel $nivel): Collection
    {
        $nivel->loadMissing('supervisor');
        $supervisorActual = $nivel->supervisor;

        $query = Director::query()
            ->where('status', true)
            ->where(function (Builder $q) {
                $q->where('identificador', 'like', '%supervisor%')
                    ->orWhere('cargo', 'like', '%supervisor%')
                    ->orWhere('cargo', 'like', '%supervisora%');
            });

        if ($supervisorActual?->zona_escolar) {
            $zona = $supervisorActual->zona_escolar;
            $query->where(function (Builder $q) use ($zona, $supervisorActual) {
                $q->where('zona_escolar', $zona)
                    ->orWhere('id', $supervisorActual->id);
            });
        }

        $candidatos = $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($supervisorActual && !$candidatos->contains('id', $supervisorActual->id)) {
            $candidatos->prepend($supervisorActual);
        }

        return $candidatos->unique('id')->values();
    }

    /** @return Collection<int, Director> */
    public function jefesSector(?Nivel $nivel = null): Collection
    {
        $sector = $nivel?->supervisor?->sector;

        return Director::query()
            ->where('status', true)
            ->where(function (Builder $q) {
                $q->where('identificador', 'like', '%jefe-sector%')
                    ->orWhere('identificador', 'like', '%jefa-sector%')
                    ->orWhere('cargo', 'like', '%jefe de sector%')
                    ->orWhere('cargo', 'like', '%jefa de sector%');
            })
            ->get()
            ->sortBy(function (Director $director) use ($sector) {
                $coincideSector = $sector && $director->sector && (string) $director->sector === (string) $sector;

                return [
                    $coincideSector ? 0 : 1,
                    Str::lower($director->apellido_paterno . ' ' . $director->apellido_materno . ' ' . $director->nombre),
                ];
            })
            ->values();
    }

    public function nombrePersona(?Persona $persona, bool $conTitulo = true): string
    {
        if (!$persona) {
            return '';
        }

        return trim(collect([
            $conTitulo ? $persona->titulo : null,
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
        ])->filter()->implode(' '));
    }

    public function nombreDirector(?Director $director, bool $conTitulo = true): string
    {
        if (!$director) {
            return '';
        }

        return trim(collect([
            $conTitulo ? $director->titulo : null,
            $director->nombre,
            $director->apellido_paterno,
            $director->apellido_materno,
        ])->filter()->implode(' '));
    }

    public function cargoDireccion(?Persona $persona, string $nivelNombre = ''): string
    {
        $genero = $persona?->genero;

        // dd($genero, $nivelNombre);
        $esMujer = in_array($genero, ['M', 'F', 'MUJER', 'FEMENINO'], true);

        return $esMujer ? 'DIRECTORA' : 'DIRECTOR';
    }

    public function esDestinatarioDirectivo(PersonaNivel $personaNivel): bool
    {
        $personaNivel->loadMissing('detalles.personaRole.rolePersona');

        return $personaNivel->detalles
            ->where('estado', 'activo')
            ->contains(function ($detalle) {
                $rol = $detalle->personaRole?->rolePersona;
                $texto = Str::slug(trim(($rol?->slug ?? '') . ' ' . ($rol?->nombre ?? '')));

                // Incluye director(a), director(a) encargado(a), subdirector(a) y demás variantes disponibles.
                return str_contains($texto, 'director');
            });
    }

    public function encabezadoSubsecretaria(Nivel $nivel): string
    {
        return in_array(Str::slug($nivel->nombre), ['bachillerato', 'media-superior'], true)
            ? 'SUBSECRETARÍA DE EDUCACIÓN MEDIA SUPERIOR Y SUPERIOR'
            : 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA';
    }

    public function encabezadoDireccion(Nivel $nivel): string
    {
        return match (Str::slug($nivel->nombre)) {
            'preescolar' => 'DIRECCIÓN GENERAL DE EDUCACIÓN PREESCOLAR',
            'primaria' => 'DIRECCIÓN GENERAL DE EDUCACIÓN PRIMARIA',
            'secundaria' => 'DIRECCIÓN GENERAL DE EDUCACIÓN SECUNDARIA',
            'bachillerato', 'media-superior' => 'DIRECCIÓN GENERAL DE EDUCACIÓN MEDIA SUPERIOR Y SUPERIOR',
            default => 'DIRECCIÓN GENERAL DE EDUCACIÓN ' . Str::upper($nivel->nombre),
        };
    }


    public function nombreEscuelaPorNivel(?Nivel $nivel, ?Escuela $escuela = null): string
    {
        return match (Str::slug((string) $nivel?->nombre)) {
            'preescolar' => 'Jardin de Niños Moctezuma',
            'primaria' => 'ESC. PRIM. PART. MOCTEZUMA',
            'secundaria' => 'ESC. SEC. PART. MOCTEZUMA',
            default => (string) ($escuela?->nombre ?: 'CENTRO DE TRABAJO'),
        };
    }

    public function configuracion(): LiberacionSueldoConfiguracion
    {
        return LiberacionSueldoConfiguracion::query()->firstOrNew([], [
            'franja_ancho_mm' => 200,
            'franja_alto_mm' => 5.5,
            'franja_inferior_mm' => 4,
        ]);
    }

    /**
     * @param array<string, mixed> $formulario
     * @param array<string, mixed> $firmante
     * @return array<string, mixed>
     */
    public function construirDatos(PersonaNivel $personaNivel, array $formulario, array $firmante = []): array
    {
        $personaNivel->loadMissing([
            'persona',
            'nivel.director',
            'nivel.supervisor',
            'detalles.personaRole.rolePersona',
        ]);

        $nivel = $personaNivel->nivel;
        $persona = $personaNivel->persona;
        $escuela = Escuela::query()->first();
        $ciclo = cicloEscolar::query()->where('es_actual', true)->first() ?: cicloEscolar::query()->latest('id')->first();
        $config = $this->configuracion();

        $directorPersona = null;
        if ($directorId = Arr::get($firmante, 'director_persona_id')) {
            $directorPersona = Persona::query()->find((int) $directorId);
        }

        $supervisorDirector = null;
        if ($supervisorId = Arr::get($firmante, 'supervisor_director_id')) {
            $supervisorDirector = Director::query()->find((int) $supervisorId);
        }

        $jefeSectorDirector = null;
        if ($jefeSectorId = Arr::get($firmante, 'jefe_sector_director_id')) {
            $jefeSectorDirector = Director::query()->find((int) $jefeSectorId);
        }

        $directorNombre = trim((string) Arr::get($firmante, 'director_nombre'));
        if ($directorNombre === '') {
            $directorNombre = $directorPersona
                ? $this->nombrePersona($directorPersona)
                : $this->nombreDirector($nivel?->director);
        }

        $directorCargo = trim((string) Arr::get($firmante, 'director_cargo'));
        if ($directorCargo === '') {
            $directorCargo = $directorPersona
                ? $this->cargoDireccion($directorPersona, (string) $nivel?->nombre)
                : ($nivel?->director?->cargo ?: 'directora');
        }

        $supervisorNombre = trim((string) Arr::get($firmante, 'supervisor_nombre'));
        if ($supervisorNombre === '') {
            $supervisorNombre = $supervisorDirector
                ? $this->nombreDirector($supervisorDirector)
                : $this->nombreDirector($nivel?->supervisor);
        }

        $supervisorCargo = trim((string) Arr::get($firmante, 'supervisor_cargo'));
        if ($supervisorCargo === '') {
            $supervisorCargo = (string) ($supervisorDirector?->cargo ?: $nivel?->supervisor?->cargo ?: 'SUPERVISOR ESCOLAR');
        }

        $jefeSectorNombre = trim((string) Arr::get($firmante, 'jefe_sector_nombre'));
        if ($jefeSectorNombre === '' && $jefeSectorDirector) {
            $jefeSectorNombre = $this->nombreDirector($jefeSectorDirector);
        }

        $jefeSectorCargo = trim((string) Arr::get($firmante, 'jefe_sector_cargo'));
        if ($jefeSectorCargo === '') {
            $jefeSectorCargo = (string) ($jefeSectorDirector?->cargo ?: 'JEFE DE SECTOR');
        }

        $esDirectivo = $this->esDestinatarioDirectivo($personaNivel);

        return [
            'persona_nivel_id' => $personaNivel->id,
            'persona_id' => $persona?->id,
            'nivel_id' => $nivel?->id,
            'trabajador_nombre' => $this->nombrePersona($persona, false),
            'nivel_nombre' => Str::upper((string) $nivel?->nombre),
            'encabezado_subsecretaria' => $nivel ? $this->encabezadoSubsecretaria($nivel) : 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA',
            'encabezado_direccion' => $nivel ? $this->encabezadoDireccion($nivel) : 'DIRECCIÓN GENERAL DE EDUCACIÓN',
            'director_persona_id' => $directorPersona?->id,
            'director_nombre' => $directorNombre,
            'director_cargo' => $directorCargo ?: 'directora',
            'escuela_nombre' => $this->nombreEscuelaPorNivel($nivel, $escuela),
            'cct' => (string) ($nivel?->cct ?: ''),
            'localidad' => (string) ($escuela?->ciudad ?: ''),
            'municipio' => (string) ($escuela?->municipio ?: ''),
            'supervisor_director_id' => $supervisorDirector?->id ?: $nivel?->supervisor?->id,
            'supervisor_nombre' => $supervisorNombre,
            'supervisor_cargo' => Str::upper($supervisorCargo ?: 'SUPERVISOR ESCOLAR'),
            'jefe_sector_director_id' => $jefeSectorDirector?->id,
            'jefe_sector_nombre' => $jefeSectorNombre,
            'jefe_sector_cargo' => Str::upper($jefeSectorCargo ?: 'JEFE DE SECTOR'),
            'destinatario_es_directivo' => $esDirectivo,
            'tipo_firmantes' => $esDirectivo ? 'supervision_sector' : 'direccion_supervision',
            'fecha_documento' => Arr::get($formulario, 'fecha_documento'),
            'quincena_inicio' => (int) Arr::get($formulario, 'quincena_inicio', 13),
            'quincena_fin' => (int) Arr::get($formulario, 'quincena_fin', 14),
            'anio' => (int) Arr::get($formulario, 'anio', now()->year),
            'ciclo_escolar' => (string) Arr::get($formulario, 'ciclo_escolar', $ciclo?->nombre),
            'fecha_reanudacion' => Arr::get($formulario, 'fecha_reanudacion'),
            'clave_presupuestal' => 'S/C',
            'logo_encabezado_path' => $config->logo_encabezado_path,
            'franja_inferior_path' => $config->franja_inferior_path,
            'franja_ancho_mm' => (float) ($config->franja_ancho_mm ?: 200),
            'franja_alto_mm' => (float) ($config->franja_alto_mm ?: 5.5),
            'franja_inferior_mm' => (float) ($config->franja_inferior_mm ?? 4),
        ];
    }

    /** @param array<string, mixed> $datos */
    public function guardar(array $datos, ?LiberacionSueldo $liberacion = null): LiberacionSueldo
    {
        $usuarioId = auth()->id();
        $liberacion ??= new LiberacionSueldo();
        $liberacion->fill($datos);
        $liberacion->creado_por ??= $usuarioId;
        $liberacion->actualizado_por = $usuarioId;
        $liberacion->save();

        return $liberacion;
    }
}
