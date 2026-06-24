<?php

namespace App\Services;

use App\Models\Dia;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario;
use App\Models\Nivel;
use App\Models\cicloEscolar;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class HorarioGeneralBuilder
{
    /**
     * Construye un horario concentrado para varios grupos del mismo nivel.
     *
     * Cada celda reúne las actividades de los grupos visibles y muestra
     * grado, grupo y materia. Los bloques marcados como receso en la base
     * de datos se convierten en filas completas; su texto se reconstruye
     * siguiendo el orden de los días (RE + C + E + S + O = RECESO).
     */
    public function construir(
        Nivel $nivel,
        cicloEscolar $cicloEscolar,
        Collection $grupos,
        array $filtros = []
    ): ?array {
        $grupos = $grupos
            ->filter(fn ($grupo) => $grupo instanceof Grupo)
            ->values();

        if ($grupos->isEmpty()) {
            return null;
        }

        $grupos->loadMissing([
            'asignacionGrupo:id,nombre',
            'grado:id,nivel_id,nombre,orden',
            'generacion:id,nivel_id,anio_ingreso,anio_egreso',
            'semestre:id,grado_id,numero,orden_global',
        ]);

        $gradoFiltro = $this->enteroNullable($filtros['grado_id'] ?? null);
        $grupoFiltro = $this->enteroNullable($filtros['grupo_id'] ?? null);
        $diaFiltro = $this->enteroNullable($filtros['dia_id'] ?? null);
        $materiaFiltro = trim((string) ($filtros['materia'] ?? ''));

        if ($gradoFiltro) {
            $grupos = $grupos
                ->where('grado_id', $gradoFiltro)
                ->values();
        }

        if ($grupoFiltro) {
            $grupos = $grupos
                ->where('id', $grupoFiltro)
                ->values();
        }

        if ($grupos->isEmpty()) {
            return null;
        }

        $idsGrupo = $grupos->pluck('id')->map(fn ($id) => (int) $id)->values();

        $consultaHorarios = Horario::query()
            ->with([
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'grupo.grado:id,nivel_id,nombre,orden',
                'grupo.generacion:id,nivel_id,anio_ingreso,anio_egreso',
                'grupo.semestre:id,grado_id,numero,orden_global',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,nivel_id,grado_id,semestre_id,materia,clave,slug,calificable,extra,receso,orden',
                'asignacionMateria.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion:id,taller_id,profesor_id,ciclo_escolar_id,dia_id,hora_id,ubicacion',
                'tallerSesion.taller:id,nivel_id,nombre,clave,slug',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
            ])
            ->where('nivel_id', $nivel->id)
            ->whereIn('grupo_id', $idsGrupo)
            ->where(function ($query) {
                $query
                    ->whereNotNull('asignacion_materia_id')
                    ->orWhereNotNull('taller_sesion_id');
            });

        if (Schema::hasColumn('horarios', 'ciclo_escolar_id')) {
            $consultaHorarios->where('ciclo_escolar_id', $cicloEscolar->id);
        }

        $horarios = $consultaHorarios->get();

        if ($horarios->isEmpty()) {
            return null;
        }

        $diasOpciones = Dia::query()
            ->where('nivel_id', $nivel->id)
            ->get(['id', 'nivel_id', 'dia', 'orden'])
            ->concat($horarios->pluck('dia')->filter())
            ->unique('id')
            ->sortBy(fn ($dia) => $this->claveOrdenDia($dia))
            ->values();

        $horas = Hora::query()
            ->where('nivel_id', $nivel->id)
            ->get(['id', 'nivel_id', 'hora_inicio', 'hora_fin', 'orden'])
            ->concat($horarios->pluck('hora')->filter())
            ->unique('id')
            ->unique(fn ($hora) => trim((string) $hora->hora_inicio) . '|' . trim((string) $hora->hora_fin))
            ->sortBy(fn ($hora) => sprintf(
                '%s-%s-%06d',
                (string) ($hora->hora_inicio ?? '99:99:99'),
                (string) ($hora->hora_fin ?? '99:99:99'),
                (int) ($hora->id ?? 999999)
            ))
            ->values();

        if ($diasOpciones->isEmpty() || $horas->isEmpty()) {
            return null;
        }

        $dias = $diaFiltro
            ? $diasOpciones->where('id', $diaFiltro)->values()
            : $diasOpciones;

        if ($dias->isEmpty()) {
            return null;
        }

        $materiasOpciones = $this->construirOpcionesMaterias($horarios);
        $filas = collect();
        $actividadesDocentes = collect();
        $totalActividades = 0;
        $totalRecesos = 0;

        foreach ($horas as $hora) {
            $registrosHora = $horarios
                ->where('hora_id', $hora->id)
                ->values();

            if ($registrosHora->isEmpty()) {
                continue;
            }

            $esFilaReceso = $this->esFilaCompletaDeReceso($registrosHora);

            if ($esFilaReceso) {
                $filas->push([
                    'hora' => $hora,
                    'es_receso' => true,
                    'receso_label' => $this->construirEtiquetaReceso(
                        $registrosHora,
                        $diasOpciones
                    ),
                    'celdas' => collect(),
                    'total_actividades' => 0,
                ]);

                $totalRecesos++;
                continue;
            }

            $celdas = collect();
            $totalFila = 0;

            foreach ($dias as $dia) {
                $actividades = $registrosHora
                    ->where('dia_id', $dia->id)
                    ->map(fn (Horario $horario) => $this->convertirActividad($horario))
                    ->filter()
                    ->when(
                        $materiaFiltro !== '',
                        fn (Collection $items) => $items->where('filtro_id', $materiaFiltro)
                    )
                    ->unique(fn (array $actividad) => implode('|', [
                        $actividad['tipo'],
                        $actividad['actividad_id'],
                        $actividad['grupo_id'],
                        $actividad['dia_id'],
                        $actividad['hora_id'],
                    ]))
                    ->sortBy(fn (array $actividad) => $actividad['orden'])
                    ->values();

                $celdas->put((int) $dia->id, $actividades);
                $totalFila += $actividades->count();
                $actividadesDocentes->push(...$actividades->all());
            }

            // Al filtrar por materia se eliminan las filas completamente vacías.
            if ($materiaFiltro !== '' && $totalFila === 0) {
                continue;
            }

            $filas->push([
                'hora' => $hora,
                'es_receso' => false,
                'receso_label' => null,
                'celdas' => $celdas,
                'total_actividades' => $totalFila,
            ]);

            $totalActividades += $totalFila;
        }

        $gruposVisibles = $grupos
            ->sortBy(fn (Grupo $grupo) => $this->claveOrdenGrupo($grupo))
            ->values();

        return [
            'nivel' => $nivel,
            'ciclo_escolar' => $cicloEscolar,
            'grupos' => $gruposVisibles,
            'dias' => $dias,
            'dias_opciones' => $diasOpciones,
            'horas' => $horas,
            'filas' => $filas,
            'materias_opciones' => $materiasOpciones,
            'docentes' => $this->construirTablaDocentes($actividadesDocentes),
            'total_grupos' => $gruposVisibles->count(),
            'total_actividades' => $totalActividades,
            'total_recesos' => $totalRecesos,
            'filtros' => [
                'grado_id' => $gradoFiltro,
                'grupo_id' => $grupoFiltro,
                'dia_id' => $diaFiltro,
                'materia' => $materiaFiltro,
            ],
        ];
    }

    public function etiquetaGrupo(Grupo $grupo): string
    {
        $grado = trim((string) ($grupo->grado?->nombre ?? ''));
        $grado = preg_match('/^\d+$/', $grado) ? $grado . '°' : $grado;
        $nombreGrupo = trim((string) ($grupo->asignacionGrupo?->nombre ?? ''));

        $etiqueta = trim($grado . ' ' . $nombreGrupo);

        if ($grupo->semestre) {
            $etiqueta .= ' · S' . $grupo->semestre->numero;
        }

        return $etiqueta !== '' ? $etiqueta : 'Sin grupo';
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

    private function convertirActividad(Horario $horario): ?array
    {
        $grupo = $horario->grupo;

        if (!$grupo) {
            return null;
        }

        $etiquetaGrupo = $this->etiquetaGrupo($grupo);
        $gradoOrden = (int) ($grupo->grado?->orden ?? 999999);
        $semestreOrden = (int) (
            $grupo->semestre?->orden_global
            ?? $grupo->semestre?->numero
            ?? 0
        );
        $grupoOrden = Str::lower(Str::ascii(
            trim((string) ($grupo->asignacionGrupo?->nombre ?? ''))
        ));

        if ($horario->taller_sesion_id) {
            $sesion = $horario->tallerSesion;
            $taller = $sesion?->taller;

            if (!$sesion || !$taller) {
                return null;
            }

            $nombre = trim((string) ($taller->nombre ?? 'Taller')) ?: 'Taller';
            $profesor = $sesion->profesor;

            return [
                'tipo' => 'taller',
                'actividad_id' => (int) $taller->id,
                'filtro_id' => 'taller:' . $taller->id,
                'nombre' => $nombre,
                'clave' => trim((string) ($taller->clave ?? '')),
                'grado_grupo' => $etiquetaGrupo,
                'grupo_id' => (int) $grupo->id,
                'grado_id' => (int) $grupo->grado_id,
                'dia_id' => (int) $horario->dia_id,
                'hora_id' => (int) $horario->hora_id,
                'profesor_id' => $profesor?->id ? (int) $profesor->id : null,
                'profesor' => $this->nombrePersona($profesor) ?: 'Sin docente',
                'ubicacion' => trim((string) ($sesion->ubicacion ?? '')),
                'orden' => sprintf(
                    '%06d-%06d-%s-%s-%06d',
                    $gradoOrden,
                    $semestreOrden,
                    $grupoOrden,
                    Str::lower(Str::ascii($nombre)),
                    (int) $horario->id
                ),
            ];
        }

        $asignacion = $horario->asignacionMateria;
        $materia = $asignacion?->materia;

        if (!$asignacion || !$materia || (int) ($materia->receso ?? 0) === 1) {
            return null;
        }

        $nombre = trim((string) ($materia->materia ?? 'Materia')) ?: 'Materia';
        $profesor = $asignacion->profesor;

        return [
            'tipo' => 'materia',
            'actividad_id' => (int) $materia->id,
            'filtro_id' => 'materia:' . $materia->id,
            'nombre' => $nombre,
            'clave' => trim((string) ($materia->clave ?? '')),
            'grado_grupo' => $etiquetaGrupo,
            'grupo_id' => (int) $grupo->id,
            'grado_id' => (int) $grupo->grado_id,
            'dia_id' => (int) $horario->dia_id,
            'hora_id' => (int) $horario->hora_id,
            'profesor_id' => $profesor?->id ? (int) $profesor->id : null,
            'profesor' => $this->nombrePersona($profesor) ?: 'Sin docente',
            'ubicacion' => '',
            'orden' => sprintf(
                '%06d-%06d-%s-%s-%06d',
                $gradoOrden,
                $semestreOrden,
                $grupoOrden,
                Str::lower(Str::ascii($nombre)),
                (int) $horario->id
            ),
        ];
    }

    private function construirOpcionesMaterias(Collection $horarios): Collection
    {
        return $horarios
            ->map(function (Horario $horario) {
                if ($horario->taller_sesion_id) {
                    $taller = $horario->tallerSesion?->taller;

                    if (!$taller) {
                        return null;
                    }

                    return [
                        'id' => 'taller:' . $taller->id,
                        'nombre' => trim((string) ($taller->nombre ?? 'Taller')) ?: 'Taller',
                        'tipo' => 'Taller',
                    ];
                }

                $materia = $horario->asignacionMateria?->materia;

                if (!$materia || (int) ($materia->receso ?? 0) === 1) {
                    return null;
                }

                return [
                    'id' => 'materia:' . $materia->id,
                    'nombre' => trim((string) ($materia->materia ?? 'Materia')) ?: 'Materia',
                    'tipo' => 'Materia',
                ];
            })
            ->filter()
            ->unique('id')
            ->sortBy(fn (array $item) => sprintf(
                '%s-%s',
                $item['tipo'] === 'Materia' ? '0' : '1',
                Str::lower(Str::ascii($item['nombre']))
            ))
            ->values();
    }

    private function construirTablaDocentes(Collection $actividades): Collection
    {
        return $actividades
            ->filter(fn (array $actividad) => in_array($actividad['tipo'], ['materia', 'taller'], true))
            ->groupBy(fn (array $actividad) => $actividad['profesor_id']
                ? 'profesor-' . $actividad['profesor_id']
                : 'sin-docente')
            ->map(function (Collection $items) {
                $primero = $items->first();

                return [
                    'profesor_id' => $primero['profesor_id'],
                    'docente' => $primero['profesor'],
                    'materias' => $items
                        ->pluck('nombre')
                        ->filter()
                        ->unique(fn ($nombre) => Str::lower(Str::ascii(trim((string) $nombre))))
                        ->sort()
                        ->values()
                        ->all(),
                    'grupos' => $items
                        ->pluck('grado_grupo')
                        ->filter()
                        ->unique()
                        ->sort()
                        ->values()
                        ->all(),
                    'sin_docente' => $primero['profesor_id'] === null,
                ];
            })
            ->sortBy(fn (array $item) => sprintf(
                '%d-%s',
                $item['sin_docente'] ? 1 : 0,
                Str::lower(Str::ascii($item['docente']))
            ))
            ->values();
    }

    private function esFilaCompletaDeReceso(Collection $registrosHora): bool
    {
        $recesos = $registrosHora->filter(fn (Horario $horario) => $this->esReceso($horario));

        if ($recesos->isEmpty()) {
            return false;
        }

        return $registrosHora->every(fn (Horario $horario) => $this->esReceso($horario));
    }

    private function esReceso(Horario $horario): bool
    {
        return !$horario->taller_sesion_id
            && (int) ($horario->asignacionMateria?->materia?->receso ?? 0) === 1;
    }

    private function construirEtiquetaReceso(Collection $registrosHora, Collection $dias): string
    {
        $partes = $dias
            ->map(function ($dia) use ($registrosHora) {
                return $registrosHora
                    ->where('dia_id', $dia->id)
                    ->filter(fn (Horario $horario) => $this->esReceso($horario))
                    ->map(fn (Horario $horario) => trim((string) (
                        $horario->asignacionMateria?->materia?->materia ?? ''
                    )))
                    ->filter()
                    ->unique(fn ($texto) => Str::lower(Str::ascii($texto)))
                    ->first();
            })
            ->filter()
            ->values();

        $etiqueta = trim($partes->implode(''));

        if ($etiqueta === '') {
            return 'RECESO';
        }

        return mb_strtoupper($etiqueta, 'UTF-8');
    }

    private function claveOrdenGrupo(Grupo $grupo): string
    {
        return sprintf(
            '%06d-%06d-%s-%06d-%06d',
            (int) ($grupo->grado?->orden ?? 999999),
            (int) ($grupo->semestre?->orden_global ?? $grupo->semestre?->numero ?? 0),
            Str::lower(Str::ascii(trim((string) ($grupo->asignacionGrupo?->nombre ?? '')))),
            (int) ($grupo->generacion?->anio_ingreso ?? 999999),
            (int) $grupo->id
        );
    }

    private function claveOrdenDia($dia): string
    {
        $nombre = Str::lower(Str::ascii(trim((string) ($dia->dia ?? ''))));
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

    private function enteroNullable(mixed $valor): ?int
    {
        return filled($valor) ? (int) $valor : null;
    }
}
