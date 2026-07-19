<?php

namespace App\Services;

use App\Models\Dia;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\PersonaNivel;
use App\Models\CicloEscolar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HorarioPdfBuilder
{
    /**
     * Construye todos los datos necesarios para una página de horario.
     * Devuelve null cuando el grupo no tiene registros válidos en el ciclo.
     */
    public function construirBloque(Nivel $nivel, Grupo $grupo, CicloEscolar $cicloEscolar): ?array
    {
        $grupo->loadMissing([
            'asignacionGrupo:id,nombre',
            'generacion:id,anio_ingreso,anio_egreso',
            'grado:id,nombre,orden',
            'semestre:id,grado_id,numero,orden_global',
        ]);

        $esBachillerato = (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';
        $esSecundaria = (int) $nivel->id === 3 || $nivel->slug === 'secundaria';
        $esPreescolar = (int) $nivel->id === 1 || $nivel->slug === 'preescolar';
        $esPrimaria = (int) $nivel->id === 2 || $nivel->slug === 'primaria';

        $horariosQuery = Horario::query()
            ->with([
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria.materia',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion:id,taller_id,profesor_id,ciclo_escolar_id,dia_id,hora_id,ubicacion,conflicto_forzado,motivo_conflicto',
                'tallerSesion.taller:id,nivel_id,nombre,clave',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.grado:id,nombre,orden',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
            ])
            ->where('grupo_id', $grupo->id)
            ->where('nivel_id', $nivel->id);

        if (Schema::hasColumn('horarios', 'ciclo_escolar_id')) {
            $horariosQuery->where('ciclo_escolar_id', $cicloEscolar->id);
        }

        if (Schema::hasColumn('horarios', 'grado_id')) {
            $horariosQuery->where('grado_id', $grupo->grado_id);
        }

        if (Schema::hasColumn('horarios', 'generacion_id')) {
            $horariosQuery->where('generacion_id', $grupo->generacion_id);
        }

        if (Schema::hasColumn('horarios', 'semestre_id')) {
            $grupo->semestre_id
                ? $horariosQuery->where('semestre_id', $grupo->semestre_id)
                : $horariosQuery->whereNull('semestre_id');
        }

        $horariosQuery->where(function ($actividadQuery) use ($grupo) {
            $actividadQuery
                ->where(function ($materiaQuery) {
                    $materiaQuery
                        ->whereNull('taller_sesion_id')
                        ->whereNotNull('asignacion_materia_id')
                        ->whereHas('asignacionMateria');
                })
                ->orWhere(function ($tallerQuery) use ($grupo) {
                    $tallerQuery
                        ->whereNotNull('taller_sesion_id')
                        ->whereHas('tallerSesion.grupos', function ($gruposQuery) use ($grupo) {
                            $gruposQuery->where('grupos.id', $grupo->id);
                        });
                });
        });

        $horarios = $horariosQuery
            ->orderBy('hora_id')
            ->orderBy('dia_id')
            ->orderBy('id')
            ->get();

        if ($horarios->isEmpty()) {
            return null;
        }

        $normalizarDia = static function ($dia): string {
            return Str::lower(Str::ascii(trim((string) ($dia->dia ?? $dia->nombre ?? ''))));
        };

        $dias = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('id')
            ->get()
            ->concat($horarios->pluck('dia')->filter())
            ->unique('id')
            ->unique($normalizarDia)
            ->sortBy(function ($dia) use ($normalizarDia) {
                $nombre = $normalizarDia($dia);

                $ordenNombre = match (true) {
                    str_contains($nombre, 'lunes') => 1,
                    str_contains($nombre, 'martes') => 2,
                    str_contains($nombre, 'miercoles') => 3,
                    str_contains($nombre, 'jueves') => 4,
                    str_contains($nombre, 'viernes') => 5,
                    default => 99,
                };

                return sprintf(
                    '%02d-%06d-%06d',
                    $ordenNombre,
                    (int) ($dia->orden ?? 999999),
                    (int) ($dia->id ?? 999999)
                );
            })
            ->values();

        $horas = Hora::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->orderBy('hora_inicio')
            ->orderBy('id')
            ->get()
            ->concat($horarios->pluck('hora')->filter())
            ->unique('id')
            ->unique(fn($hora) => trim((string) $hora->hora_inicio) . '|' . trim((string) $hora->hora_fin))
            ->sortBy(function ($hora) {
                return sprintf(
                    '%s-%s-%06d',
                    (string) ($hora->hora_inicio ?? '99:99:99'),
                    (string) ($hora->hora_fin ?? '99:99:99'),
                    (int) ($hora->id ?? 999999)
                );
            })
            ->values();

        if ($dias->isEmpty() || $horas->isEmpty()) {
            return null;
        }

        $horarioPorCelda = $horarios
            ->whereNull('taller_sesion_id')
            ->groupBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id)
            ->map(fn($registros) => $registros->first());

        $talleresPorCelda = $horarios
            ->whereNotNull('taller_sesion_id')
            ->groupBy(fn($horario) => $horario->hora_id . '-' . $horario->dia_id)
            ->map(function ($registros) {
                return $registros
                    ->unique('taller_sesion_id')
                    ->sortBy(fn($horario) => Str::lower(Str::ascii(
                        trim((string) ($horario->tallerSesion?->taller?->nombre ?? ''))
                    )))
                    ->values();
            });

        // El titular no se imprime en el horario general. Solo se obtiene su ID
        // para conservar la regla de la tabla de docentes de preescolar.
        $profesorTitularId = null;

        if ($esPreescolar || $esPrimaria) {
            $personalAsignado = PersonaNivel::query()
                ->with('persona:id,titulo,nombre,apellido_paterno,apellido_materno,genero')
                ->where('nivel_id', $nivel->id)
                ->whereHas('detalles', function ($query) use ($grupo) {
                    $query
                        ->where('grado_id', $grupo->grado_id)
                        ->where('grupo_id', $grupo->id);
                })
                ->first();

            $profesorTitularId = $personalAsignado?->persona?->id
                ? (int) $personalAsignado->persona->id
                : null;
        }

        [$docentesPreescolar, $docentesHorario] = $this->construirDocentes(
            $horarios,
            $esPreescolar,
            $esPrimaria,
            $esSecundaria,
            $esBachillerato,
            $profesorTitularId
        );

        return [
            'generacion' => $grupo->generacion,
            'grado' => $grupo->grado,
            'grupo' => $grupo,
            'semestre' => $grupo->semestre,
            'dias' => $dias,
            'horas' => $horas,
            'horarios' => $horarios,
            'horarioPorCelda' => $horarioPorCelda,
            'talleresPorCelda' => $talleresPorCelda,
            'docentes_preescolar' => $docentesPreescolar,
            'docentes_horario' => $docentesHorario,
            'esBachillerato' => $esBachillerato,
            'esSecundaria' => $esSecundaria,
            'esPreescolar' => $esPreescolar,
            'esPrimaria' => $esPrimaria,
        ];
    }

    public function imagenBase64Publica(?string $ruta): ?string
    {
        if (!$ruta) {
            return null;
        }

        $path = public_path($ruta);

        if (!is_file($path)) {
            return null;
        }

        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'image/png',
        };

        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($path));
    }

    private function construirDocentes(
        Collection $horarios,
        bool $esPreescolar,
        bool $esPrimaria,
        bool $esSecundaria,
        bool $esBachillerato,
        ?int $profesorTitularId
    ): array {
        $docentesPreescolar = collect();
        $docentesHorario = collect();

        if ($esPreescolar) {
            $docentesPreescolar = $horarios
                ->whereNull('taller_sesion_id')
                ->map(function ($horario) use ($profesorTitularId) {
                    $asignacion = $horario->asignacionMateria;
                    $materia = $asignacion?->materia;

                    if (!$asignacion || !$materia || (int) ($materia->receso ?? 0) === 1) {
                        return null;
                    }

                    $profesor = $asignacion->profesor;
                    $profesorId = $profesor?->id ? (int) $profesor->id : null;

                    if ($profesorTitularId !== null && $profesorId === $profesorTitularId) {
                        return null;
                    }

                    return [
                        'profesor_id' => $profesorId,
                        'docente' => $profesor ? ($this->nombrePersona($profesor) ?: 'Sin docente') : 'Sin docente',
                        'materia' => trim((string) ($materia->materia ?? '')) ?: 'Sin materia',
                        'orden' => (int) ($materia->orden ?? 999999),
                        'sin_docente' => $profesorId === null,
                    ];
                })
                ->filter();
        } else {
            $slugsExcluidosPrimaria = ['calculo-mental', 'caligrafia', 'lectura'];

            foreach ($horarios->whereNull('taller_sesion_id') as $horario) {
                $asignacion = $horario->asignacionMateria;
                $materia = $asignacion?->materia;

                if (!$asignacion || !$materia) {
                    continue;
                }

                $calificable = (int) ($materia->calificable ?? 0);
                $extra = (int) ($materia->extra ?? 0);
                $receso = (int) ($materia->receso ?? 0);
                $slugMateria = Str::lower(trim((string) ($materia->slug ?? '')));

                if ($receso === 1) {
                    continue;
                }

                if ($esSecundaria || $esBachillerato) {
                    if ($calificable !== 1) {
                        continue;
                    }
                } else {
                    if (!$esPrimaria || $extra !== 1 || $calificable !== 1) {
                        continue;
                    }

                    if (in_array($slugMateria, $slugsExcluidosPrimaria, true)) {
                        continue;
                    }
                }

                $profesor = $asignacion->profesor;
                $profesorId = $profesor?->id ? (int) $profesor->id : null;

                $docentesHorario->push([
                    'profesor_id' => $profesorId,
                    'docente' => $profesor ? ($this->nombrePersona($profesor) ?: 'Sin docente') : 'Sin docente',
                    'materia' => trim((string) ($materia->materia ?? '')) ?: 'Sin materia',
                    'orden' => (int) ($materia->orden ?? 999999),
                    'sin_docente' => $profesorId === null,
                ]);
            }

            if ($esSecundaria) {
                foreach ($horarios->whereNotNull('taller_sesion_id')->unique('taller_sesion_id') as $horarioTaller) {
                    $sesion = $horarioTaller->tallerSesion;

                    if (!$sesion) {
                        continue;
                    }

                    $profesor = $sesion->profesor;
                    $profesorId = $profesor?->id ? (int) $profesor->id : null;

                    $docentesHorario->push([
                        'profesor_id' => $profesorId,
                        'docente' => $profesor ? ($this->nombrePersona($profesor) ?: 'Sin docente') : 'Sin docente',
                        'materia' => trim((string) ($sesion->taller?->nombre ?? '')) ?: 'Taller',
                        'orden' => 999998,
                        'sin_docente' => $profesorId === null,
                    ]);
                }
            }
        }

        return [
            $this->agruparDocentes($docentesPreescolar),
            $this->agruparDocentes($docentesHorario),
        ];
    }

    private function agruparDocentes(Collection $items): Collection
    {
        return $items
            ->groupBy(fn(array $item) => $item['profesor_id'] !== null
                ? 'profesor-' . $item['profesor_id']
                : 'sin-docente')
            ->map(function ($items) {
                $primero = $items->first();
                $materias = $items
                    ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                    ->pluck('materia')
                    ->filter()
                    ->unique(fn($materia) => mb_strtoupper(trim((string) $materia), 'UTF-8'))
                    ->values();

                return [
                    'profesor_id' => $primero['profesor_id'],
                    'docente' => $primero['docente'],
                    'materias' => $materias->all(),
                    'materias_texto' => $materias->implode(', '),
                    'sin_docente' => (bool) $primero['sin_docente'],
                ];
            })
            ->sortBy(fn(array $item) => sprintf(
                '%d-%s',
                $item['sin_docente'] ? 1 : 0,
                mb_strtoupper(trim((string) $item['docente']), 'UTF-8')
            ))
            ->values();
    }

    private function nombrePersona($persona): string
    {
        if (!$persona) {
            return '';
        }

        return trim(implode(' ', array_filter([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])));
    }
}
