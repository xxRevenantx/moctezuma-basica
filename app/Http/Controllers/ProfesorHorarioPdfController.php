<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\AsignacionMateria;
use App\Models\TallerSesion;
use App\Models\Escuela;
use App\Models\Horario;
use App\Models\Persona;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ProfesorHorarioPdfController extends Controller
{
    public function __invoke(Request $request)
    {
        $profesorId = $request->integer('profesor_id');

        abort_if(!$profesorId, 404);

        $profesor = Persona::query()->findOrFail($profesorId);

        $cicloEscolar = CicloEscolar::query()
            ->when($request->filled('ciclo_escolar_id'), fn($query) => $query->where('id', $request->integer('ciclo_escolar_id')))
            ->when(!$request->filled('ciclo_escolar_id'), fn($query) => $query->orderByDesc('es_actual'))
            ->orderBy('id', 'desc')
            ->first();

        $escuela = Escuela::query()->first();


        $horarios = Horario::query()
            ->with([
                'nivel:id,nombre,color,cct',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso,status',
                'semestre:id,numero,grado_id',
                'grupo:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'grupo.asignacionGrupo:id,nombre',
                'dia:id,nivel_id,dia,orden',
                'hora:id,nivel_id,hora_inicio,hora_fin,orden',
                'asignacionMateria:id,materia_id,grupo_id,profesor_id,orden',
                'asignacionMateria.materia:id,materia,nivel_id,grado_id,semestre_id,extra,receso,orden',
                'tallerSesion.taller:id,nivel_id,nombre,clave',
                'tallerSesion.profesor:id,titulo,nombre,apellido_paterno,apellido_materno',
                'tallerSesion.grupos:id,asignacion_grupo_id,nivel_id,grado_id,generacion_id,semestre_id',
                'tallerSesion.grupos.grado:id,nombre,orden',
                'tallerSesion.grupos.asignacionGrupo:id,nombre',
            ])
            ->where(function ($query) use ($profesorId) {
                $query->whereHas('asignacionMateria', function ($subQuery) use ($profesorId) {
                    $subQuery->where('profesor_id', $profesorId)
                        ->where('estado', '!=', AsignacionMateria::ESTADO_ARCHIVADA);
                })->orWhereHas('tallerSesion', function ($subQuery) use ($profesorId) {
                    $subQuery->where('profesor_id', $profesorId)
                        ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA);
                });
            })
            ->when($cicloEscolar, fn($query) => $query->where('ciclo_escolar_id', $cicloEscolar->id))
            ->when($request->filled('nivel_id'), function ($query) use ($request) {
                $query->where('nivel_id', $request->integer('nivel_id'));
            })
            ->when($request->filled('materia_id'), function ($query) use ($request) {
                $query->whereHas('asignacionMateria', function ($subQuery) use ($request) {
                    $subQuery->where('materia_id', $request->integer('materia_id'));
                });
            })
            ->when($request->filled('grado_id'), function ($query) use ($request) {
                $query->where('grado_id', $request->integer('grado_id'));
            })
            ->when($request->filled('grupo_id'), function ($query) use ($request) {
                $query->where('grupo_id', $request->integer('grupo_id'));
            })
            ->when($request->filled('busqueda'), function ($query) use ($request) {
                $buscar = trim((string) $request->input('busqueda'));

                $query->where(function ($subQuery) use ($buscar) {
                    $subQuery
                        ->whereHas('asignacionMateria.materia', function ($materiaQuery) use ($buscar) {
                            $materiaQuery->where('materia', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('tallerSesion.taller', function ($tallerQuery) use ($buscar) {
                            $tallerQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('nivel', function ($nivelQuery) use ($buscar) {
                            $nivelQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('grado', function ($gradoQuery) use ($buscar) {
                            $gradoQuery->where('nombre', 'like', "%{$buscar}%");
                        })
                        ->orWhereHas('grupo.asignacionGrupo', function ($grupoQuery) use ($buscar) {
                            $grupoQuery->where('nombre', 'like', "%{$buscar}%");
                        });
                });
            })
            ->get()
            ->unique(fn($horario) => $horario->taller_sesion_id
                ? 'taller-' . $horario->taller_sesion_id
                : 'horario-' . $horario->id)
            ->when($request->filled('dia_key'), function (Collection $items) use ($request) {
                $diaKey = (string) $request->input('dia_key');

                return $items->filter(function ($horario) use ($diaKey) {
                    return Str::slug($horario->dia?->dia ?? '') === $diaKey;
                })->values();
            })
            ->sortBy([
                fn($a, $b) => $this->horaInicio($a) <=> $this->horaInicio($b),
                fn($a, $b) => $this->ordenDia($a->dia?->dia) <=> $this->ordenDia($b->dia?->dia),
                fn($a, $b) => ($a->nivel->id ?? 0) <=> ($b->nivel->id ?? 0),
                fn($a, $b) => ($a->grado->orden ?? 0) <=> ($b->grado->orden ?? 0),
            ])
            ->values();

        $profesorNombre = trim(
            ($profesor->titulo ? $profesor->titulo . ' ' : '') .
            $profesor->nombre . ' ' .
            $profesor->apellido_paterno . ' ' .
            ($profesor->apellido_materno ?? '')
        );

        $horarioGeneral = $this->crearHorarioGeneral($horarios);
        $materiasAsignadas = $this->crearMateriasAsignadas($horarios);
        $horasPorDia = $this->crearHorasPorDia($horarioGeneral);
        $totalHorasSemanales = array_sum($horasPorDia);

        $logoIzquierdo = public_path('imagenes/logo-letra.png');
        $logoDerecho = public_path('penacho.jpg');

        $pdf = Pdf::loadView('pdf.profesor-horario', [
            'profesor' => $profesor,
            'profesorNombre' => $profesorNombre,
            'horarios' => $horarios,
            'horarioGeneral' => $horarioGeneral,
            'materiasAsignadas' => $materiasAsignadas,
            'horasPorDia' => $horasPorDia,
            'totalHorasSemanales' => $totalHorasSemanales,
            'logoIzquierdo' => file_exists($logoIzquierdo) ? $logoIzquierdo : null,
            'logoDerecho' => file_exists($logoDerecho) ? $logoDerecho : null,
            'cicloEscolar' => $cicloEscolar,
            'escuela' => $escuela,
        ])->setPaper('letter', 'portrait');

        return $pdf->stream('horario-profesor-' . Str::slug($profesorNombre) . '.pdf');
    }

    private function crearHorarioGeneral(Collection $horarios): array
    {
        $diasBase = collect([
            ['key' => 'lunes', 'nombre' => 'LUNES', 'orden' => 1],
            ['key' => 'martes', 'nombre' => 'MARTES', 'orden' => 2],
            ['key' => 'miercoles', 'nombre' => 'MIÉRCOLES', 'orden' => 3],
            ['key' => 'jueves', 'nombre' => 'JUEVES', 'orden' => 4],
            ['key' => 'viernes', 'nombre' => 'VIERNES', 'orden' => 5],
        ]);

        $diasExtras = $horarios
            ->pluck('dia')
            ->filter()
            ->map(function ($dia) {
                return [
                    'key' => Str::slug($dia->dia),
                    'nombre' => mb_strtoupper($dia->dia),
                    'orden' => $this->ordenDia($dia->dia),
                ];
            })
            ->filter(fn($dia) => $dia['orden'] > 5 && $dia['orden'] < 99)
            ->unique('key')
            ->values();

        $dias = $diasBase
            ->merge($diasExtras)
            ->unique('key')
            ->sortBy('orden')
            ->values();

        $horas = $horarios
            ->pluck('hora')
            ->filter()
            ->map(function ($hora) {
                $inicio24 = Carbon::parse($hora->hora_inicio)->format('H:i');
                $fin24 = Carbon::parse($hora->hora_fin)->format('H:i');

                return [
                    'key' => $inicio24 . '-' . $fin24,
                    'inicio' => $this->formatoHora($hora->hora_inicio),
                    'fin' => $this->formatoHora($hora->hora_fin),
                    'orden' => $this->minutos($inicio24),
                ];
            })
            ->unique('key')
            ->sortBy('orden')
            ->values();

        $celdas = [];

        foreach ($horarios as $horario) {
            if (!$horario->dia || !$horario->hora) {
                continue;
            }

            $diaKey = Str::slug($horario->dia->dia);

            $inicio = Carbon::parse($horario->hora->hora_inicio)->format('H:i');
            $fin = Carbon::parse($horario->hora->hora_fin)->format('H:i');
            $horaKey = $inicio . '-' . $fin;

            $celdas[$horaKey][$diaKey][] = $horario;
        }

        return [
            'dias' => $dias,
            'horas' => $horas,
            'celdas' => $celdas,
        ];
    }

    private function crearMateriasAsignadas(Collection $horarios): Collection
    {
        return $horarios
            ->groupBy(function ($horario) {
                if ($horario->esTallerConjunto()) {
                    return 'taller|' . $horario->taller_sesion_id;
                }

                return implode('|', [
                    'materia',
                    $horario->asignacionMateria?->materia_id,
                    $horario->nivel_id,
                    $horario->grado_id,
                    $horario->grupo_id,
                ]);
            })
            ->map(function (Collection $items) {
                $primero = $items->first();

                if ($primero->esTallerConjunto()) {
                    $grupos = $primero->tallerSesion?->grupos
                            ?->map(fn($grupo) => trim(
                            $this->gradoCorto($grupo->grado?->nombre) . '° ' .
                            ($grupo->asignacionGrupo?->nombre ?? '-')
                        ))
                        ->filter()
                        ->implode(', ');

                    return [
                        'materia' => 'Taller conjunto: ' . $primero->nombreActividad(),
                        'nivel' => $primero->nivel?->nombre ?? 'Nivel',
                        'grado' => 'Varios',
                        'grupo' => $grupos ?: '-',
                        'bloques' => 1,
                        'taller_conjunto' => true,
                    ];
                }

                return [
                    'materia' => $primero->nombreActividad(),
                    'nivel' => $primero->nivel?->nombre ?? 'Nivel',
                    'grado' => $this->gradoCorto($primero->grado?->nombre),
                    'grupo' => $primero->grupo?->asignacionGrupo?->nombre ?? '-',
                    'bloques' => $items->count(),
                    'taller_conjunto' => false,
                ];
            })
            ->sortBy([
                fn($a, $b) => $a['nivel'] <=> $b['nivel'],
                fn($a, $b) => $a['grado'] <=> $b['grado'],
                fn($a, $b) => $a['materia'] <=> $b['materia'],
            ])
            ->values();
    }

    private function crearHorasPorDia(array $horarioGeneral): array
    {
        $totales = [];

        foreach ($horarioGeneral['dias'] as $dia) {
            $total = 0;

            foreach ($horarioGeneral['horas'] as $hora) {
                $celdas = $horarioGeneral['celdas'][$hora['key']][$dia['key']] ?? [];
                $total += count($celdas);
            }

            $totales[$dia['key']] = $total;
        }

        return $totales;
    }

    private function gradoCorto(?string $grado): string
    {
        if (!$grado) {
            return '-';
        }

        if (preg_match('/\d+/', $grado, $match)) {
            return $match[0];
        }

        return $grado;
    }

    private function ordenDia(?string $dia): int
    {
        $key = Str::slug($dia ?? '');

        return match ($key) {
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'domingo' => 7,
            default => 99,
        };
    }

    private function horaInicio($horario): int
    {
        if (!$horario->hora?->hora_inicio) {
            return 9999;
        }

        return $this->minutos(Carbon::parse($horario->hora->hora_inicio)->format('H:i'));
    }

    private function minutos(string $hora): int
    {
        [$h, $m] = explode(':', $hora);

        return ((int) $h * 60) + (int) $m;
    }

    private function formatoHora($hora): string
    {
        return strtolower(Carbon::parse($hora)->format('g:ia'));
    }
}
