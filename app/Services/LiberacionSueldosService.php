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
            ->whereHas('persona', fn (Builder $query) => $query
                ->where('status', true)
                ->where(function (Builder $estado) {
                    $estado->whereNull('estado_laboral')->orWhere('estado_laboral', 'activo');
                }))
            ->whereHas('detalles', fn (Builder $query) => $query->where('estado', 'activo'));
    }

    public function directoresPlantilla(int $nivelId)
    {
        return Persona::query()
            ->whereHas('personaNiveles', function (Builder $cabecera) use ($nivelId) {
                $cabecera->where('nivel_id', $nivelId)
                    ->where('estado', PersonaNivel::ESTADO_ACTIVO)
                    ->whereHas('detalles', function (Builder $detalle) {
                        $detalle->where('estado', 'activo')
                            ->whereHas('personaRole.rolePersona', function (Builder $rol) {
                                $rol->where('slug', 'like', 'director%')
                                    ->orWhere('nombre', 'like', '%Director%');
                            });
                    });
            })
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    public function nombrePersona(?Persona $persona, bool $conTitulo = true): string
    {
        if (! $persona) {
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
        if (! $director) {
            return '';
        }

        return trim(collect([
            $conTitulo ? $director->titulo : null,
            $director->nombre,
            $director->apellido_paterno,
            $director->apellido_materno,
        ])->filter()->implode(' '));
    }

    public function cargoDireccion(?Persona $persona, string $nivelNombre): string
    {
        $genero = Str::upper((string) $persona?->genero);
        $esMujer = in_array($genero, ['M', 'F', 'MUJER', 'FEMENINO'], true);

        return $esMujer ? 'DIRECTORA' : 'DIRECTOR';
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

    public function logoConfigurado(): ?string
    {
        return LiberacionSueldoConfiguracion::query()->value('logo_encabezado_path');
    }

    /**
     * @param array<string, mixed> $formulario
     * @param array<string, mixed> $firmante
     * @return array<string, mixed>
     */
    public function construirDatos(PersonaNivel $personaNivel, array $formulario, array $firmante = []): array
    {
        $personaNivel->loadMissing(['persona', 'nivel.director', 'nivel.supervisor']);
        $nivel = $personaNivel->nivel;
        $persona = $personaNivel->persona;
        $escuela = Escuela::query()->first();
        $ciclo = cicloEscolar::query()->where('es_actual', true)->first() ?: cicloEscolar::query()->latest('id')->first();

        $directorPersona = null;
        if ($directorId = Arr::get($firmante, 'director_persona_id')) {
            $directorPersona = Persona::query()->find($directorId);
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
                : Str::upper((string) ($nivel?->director?->cargo ?: 'DIRECTOR'));
        }

        $supervisorNombre = trim((string) Arr::get($firmante, 'supervisor_nombre'));
        if ($supervisorNombre === '') {
            $supervisorNombre = $this->nombreDirector($nivel?->supervisor);
        }

        $supervisorCargo = trim((string) Arr::get($firmante, 'supervisor_cargo')) ?: 'SUPERVISOR ESCOLAR';

        return [
            'persona_nivel_id' => $personaNivel->id,
            'persona_id' => $persona?->id,
            'nivel_id' => $nivel?->id,
            'trabajador_nombre' => $this->nombrePersona($persona, false),
            'nivel_nombre' => Str::upper((string) $nivel?->nombre),
            'encabezado_subsecretaria' => $nivel ? $this->encabezadoSubsecretaria($nivel) : 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA',
            'encabezado_direccion' => $nivel ? $this->encabezadoDireccion($nivel) : 'DIRECCIÓN GENERAL DE EDUCACIÓN',
            'director_nombre' => $directorNombre,
            'director_cargo' => Str::upper($directorCargo ?: 'DIRECTOR'),
            'escuela_nombre' => (string) ($escuela?->nombre ?: 'CENTRO DE TRABAJO'),
            'cct' => (string) ($nivel?->cct ?: ''),
            'localidad' => (string) ($escuela?->ciudad ?: ''),
            'municipio' => (string) ($escuela?->municipio ?: ''),
            'supervisor_nombre' => $supervisorNombre,
            'supervisor_cargo' => Str::upper($supervisorCargo),
            'fecha_documento' => Arr::get($formulario, 'fecha_documento'),
            'quincena_inicio' => (int) Arr::get($formulario, 'quincena_inicio', 13),
            'quincena_fin' => (int) Arr::get($formulario, 'quincena_fin', 14),
            'anio' => (int) Arr::get($formulario, 'anio', now()->year),
            'ciclo_escolar' => (string) Arr::get($formulario, 'ciclo_escolar', $ciclo?->nombre),
            'fecha_reanudacion' => Arr::get($formulario, 'fecha_reanudacion'),
            'clave_presupuestal' => 'S/N',
            'logo_encabezado_path' => $this->logoConfigurado(),
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
