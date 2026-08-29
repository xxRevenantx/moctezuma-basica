<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\InscripcionCiclo;
use App\Models\Nivel;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class EstadisticaAlumnosGruposService
{
    public function __construct(private readonly EdadEscolarService $edadEscolar)
    {
    }

    /**
     * Genera el desglose compatible con la sección "Alumnado y grupos"
     * de los formatos 911.1, 911.3 y 911.5.
     *
     * @return array<string, mixed>
     */
    public function generar(int $cicloEscolarId, int $nivelId): array
    {
        $ciclo = CicloEscolar::query()->findOrFail($cicloEscolarId);
        $nivel = Nivel::query()->findOrFail($nivelId);
        $slug = mb_strtolower((string) $nivel->slug);

        if (! in_array($slug, ['preescolar', 'primaria', 'secundaria'], true)) {
            throw ValidationException::withMessages([
                'nivel_id' => 'El desglose SEP 911 de esta sección está disponible para Preescolar, Primaria y Secundaria.',
            ]);
        }

        $corte = $this->edadEscolar->fechaCorte911($ciclo);
        $corteEdad = $this->edadEscolar->fechaEdad911($ciclo);
        $columnas = $this->columnasEdad($slug);
        $grados = Grado::query()
            ->where('nivel_id', $nivel->id)
            ->orderBy('orden')
            ->get(['id', 'nivel_id', 'nombre', 'slug', 'orden']);

        $historiales = InscripcionCiclo::query()
            ->with([
                'inscripcion:id,fecha_nacimiento,genero,nombre,apellido_paterno,apellido_materno,curp,matricula',
                'grado:id,nivel_id,nombre,slug,orden',
            ])
            ->where('ciclo_escolar_id', $ciclo->id)
            ->where('nivel_id', $nivel->id)
            ->whereDate('fecha_ingreso', '<=', $corte->toDateString())
            ->where(function ($query) use ($corte): void {
                $query->whereNull('fecha_salida')
                    ->orWhereDate('fecha_salida', '>', $corte->toDateString());
            })
            ->orderBy('grado_id')
            ->orderBy('inscripcion_id')
            ->get();

        $matriz = [];
        foreach ($grados as $grado) {
            $matriz[(int) $grado->id] = $this->estructuraGrado($slug, $columnas, $grado);
        }

        $incidencias = [];
        $contabilizados = 0;

        foreach ($historiales as $historial) {
            $alumno = $historial->inscripcion;
            $grado = $historial->grado;

            if (! $alumno || ! $grado || ! isset($matriz[(int) $grado->id])) {
                $incidencias[] = $this->incidencia($historial, 'No fue posible resolver el alumno o grado del historial.');
                continue;
            }

            $sexo = match (mb_strtoupper(trim((string) $alumno->genero))) {
                'H' => 'hombres',
                'M' => 'mujeres',
                default => null,
            };

            if (! $sexo) {
                $incidencias[] = $this->incidencia($historial, 'Sexo no reconocido para el Formato 911.');
                continue;
            }

            if (! $alumno->fecha_nacimiento) {
                $incidencias[] = $this->incidencia($historial, 'No tiene fecha de nacimiento; no puede asignarse a una columna de edad.');
                continue;
            }

            $nacimiento = CarbonImmutable::parse($alumno->fecha_nacimiento)->startOfDay();
            $edad = $this->edadEscolar->edadEnFecha($nacimiento, $corteEdad);
            $columnaEdad = $this->claveEdad($slug, $edad);
            if ($slug !== 'preescolar' && $historial->estatus_ingreso === 'reingreso') {
                $incidencias[] = $this->incidencia(
                    $historial,
                    'El historial está marcado como reingreso. El glosario 911 distingue reingreso de nuevo ingreso y repetidor; revise el antecedente académico antes de asignarlo a una de las dos filas del formato.'
                );
                continue;
            }

            $condicion = $slug === 'preescolar'
                ? 'simple'
                : ($historial->estatus_ingreso === 'no_promovido' ? 'repetidores' : 'nuevo_ingreso');

            if (! $columnaEdad || ! array_key_exists($columnaEdad, $columnas)) {
                $incidencias[] = $this->incidencia(
                    $historial,
                    "La edad de {$edad} años queda fuera de las columnas disponibles en el formato {$this->codigoFormato($slug)}."
                );
                continue;
            }

            $numeroGrado = $this->edadEscolar->numeroGrado($grado);
            if (! $numeroGrado || ! $this->celdaPermitida($slug, $numeroGrado, $columnaEdad, $condicion)) {
                $incidencias[] = $this->incidencia(
                    $historial,
                    "La combinación de {$grado->nombre}° grado, {$edad} años y ".($condicion === 'repetidores' ? 'repetidor' : ($condicion === 'nuevo_ingreso' ? 'nuevo ingreso' : 'edad'))." corresponde a un área sombreada del formato {$this->codigoFormato($slug)} y requiere revisión."
                );
                continue;
            }

            if ($slug === 'preescolar') {
                $matriz[(int) $grado->id][$sexo]['edades'][$columnaEdad]++;
                $matriz[(int) $grado->id][$sexo]['total']++;
            } else {
                $matriz[(int) $grado->id][$sexo][$condicion]['edades'][$columnaEdad]++;
                $matriz[(int) $grado->id][$sexo][$condicion]['total']++;
            }

            $contabilizados++;
        }

        foreach ($matriz as $gradoId => &$datosGrado) {
            $datosGrado = $this->calcularSubtotalesGrado($slug, $datosGrado, $columnas);
        }
        unset($datosGrado);

        $totales = $this->totalesGenerales($slug, $matriz, $columnas);
        $grupos = $this->conteoGrupos($ciclo, $nivel, $grados, $corte);

        return [
            'contexto' => [
                'ciclo_escolar_id' => (int) $ciclo->id,
                'ciclo' => $ciclo->nombre,
                'nivel_id' => (int) $nivel->id,
                'nivel' => $nivel->nombre,
                'nivel_slug' => $slug,
                'cct' => $nivel->cct,
                'fecha_corte' => $corte->toDateString(),
                'fecha_corte_texto' => $corte->format('d/m/Y'),
                'fecha_edad' => $corteEdad->toDateString(),
                'fecha_edad_texto' => $corteEdad->format('d/m/Y'),
                'codigo_formato' => $this->codigoFormato($slug),
                'criterio_repetidor' => $slug === 'preescolar'
                    ? null
                    : 'Se clasifica como repetidor cuando el historial de ingreso al ciclo está marcado como no_promovido. Los historiales marcados como reingreso se envían a incidencias para revisión; los demás se presentan como nuevo ingreso.',
            ],
            'columnas' => $columnas,
            'grados' => array_values($matriz),
            'totales' => $totales,
            'grupos' => $grupos,
            'resumen' => [
                'historiales_al_corte' => $historiales->count(),
                'contabilizados' => $contabilizados,
                'incidencias' => count($incidencias),
                'cuadra' => $historiales->count() === $contabilizados + count($incidencias),
            ],
            'incidencias' => $incidencias,
        ];
    }

    /** @return array<string, string> */
    private function columnasEdad(string $slug): array
    {
        return match ($slug) {
            'preescolar' => [
                'menos_3' => 'Menos de 3 años',
                '3' => '3 años',
                '4' => '4 años',
                '5' => '5 años',
                '6' => '6 años',
            ],
            'primaria' => [
                'menos_6' => 'Menos de 6 años',
                '6' => '6 años',
                '7' => '7 años',
                '8' => '8 años',
                '9' => '9 años',
                '10' => '10 años',
                '11' => '11 años',
                '12' => '12 años',
                '13' => '13 años',
                '14' => '14 años',
                '15_mas' => '15 años y más',
            ],
            'secundaria' => [
                'menos_12' => 'Menos de 12 años',
                '12' => '12 años',
                '13' => '13 años',
                '14' => '14 años',
                '15' => '15 años',
                '16' => '16 años',
                '17' => '17 años',
                '18_mas' => '18 años y más',
            ],
        };
    }

    private function claveEdad(string $slug, int $edad): ?string
    {
        return match ($slug) {
            'preescolar' => match (true) {
                $edad < 3 => 'menos_3',
                $edad >= 3 && $edad <= 6 => (string) $edad,
                default => null,
            },
            'primaria' => match (true) {
                $edad < 6 => 'menos_6',
                $edad >= 6 && $edad <= 14 => (string) $edad,
                $edad >= 15 => '15_mas',
                default => null,
            },
            'secundaria' => match (true) {
                $edad < 12 => 'menos_12',
                $edad >= 12 && $edad <= 17 => (string) $edad,
                $edad >= 18 => '18_mas',
                default => null,
            },
            default => null,
        };
    }

    /** @param array<string,string> $columnas */
    private function estructuraGrado(string $slug, array $columnas, Grado $grado): array
    {
        $base = [
            'grado_id' => (int) $grado->id,
            'grado' => (string) $grado->nombre,
            'orden' => (int) $grado->orden,
        ];

        $numeroGrado = $this->edadEscolar->numeroGrado($grado) ?? 0;

        if ($slug === 'preescolar') {
            return $base + [
                'hombres' => $this->filaSimple($columnas),
                'mujeres' => $this->filaSimple($columnas),
                'subtotal' => $this->filaSimple($columnas),
                'sombreadas' => [
                    'simple' => $this->columnasSombreadas($slug, $numeroGrado, 'simple', $columnas),
                    'subtotal' => $this->columnasSombreadas($slug, $numeroGrado, 'subtotal', $columnas),
                ],
            ];
        }

        return $base + [
            'hombres' => [
                'nuevo_ingreso' => $this->filaSimple($columnas),
                'repetidores' => $this->filaSimple($columnas),
                'subtotal' => $this->filaSimple($columnas),
            ],
            'mujeres' => [
                'nuevo_ingreso' => $this->filaSimple($columnas),
                'repetidores' => $this->filaSimple($columnas),
                'subtotal' => $this->filaSimple($columnas),
            ],
            'subtotal' => $this->filaSimple($columnas),
            'sombreadas' => [
                'nuevo_ingreso' => $this->columnasSombreadas($slug, $numeroGrado, 'nuevo_ingreso', $columnas),
                'repetidores' => $this->columnasSombreadas($slug, $numeroGrado, 'repetidores', $columnas),
                'subtotal' => $this->columnasSombreadas($slug, $numeroGrado, 'subtotal', $columnas),
            ],
        ];
    }

    /** @param array<string,string> $columnas */
    private function filaSimple(array $columnas): array
    {
        return [
            'edades' => array_fill_keys(array_keys($columnas), 0),
            'total' => 0,
        ];
    }

    /** @param array<string,string> $columnas @return array<int,string> */
    private function columnasSombreadas(string $slug, int $grado, string $condicion, array $columnas): array
    {
        return collect(array_keys($columnas))
            ->reject(fn (string $clave): bool => $this->celdaPermitida($slug, $grado, $clave, $condicion))
            ->values()
            ->all();
    }

    private function celdaPermitida(string $slug, int $grado, string $columnaEdad, string $condicion): bool
    {
        if ($slug === 'preescolar') {
            if ($grado === 1) {
                return true;
            }

            if ($grado === 2) {
                return $columnaEdad !== 'menos_3';
            }

            if ($grado === 3) {
                return ! in_array($columnaEdad, ['menos_3', '3'], true);
            }

            return false;
        }

        if ($slug === 'primaria') {
            if ($grado === 1) {
                return ! ($condicion === 'repetidores' && $columnaEdad === 'menos_6');
            }

            $minimo = $grado + 4; // 2° admite desde 6; 3° desde 7; ... 6° desde 10.
            if ($columnaEdad === 'menos_6') {
                return false;
            }

            if (ctype_digit($columnaEdad)) {
                return (int) $columnaEdad >= $minimo;
            }

            return $columnaEdad === '15_mas';
        }

        if ($slug === 'secundaria') {
            if ($grado === 1) {
                return ! ($condicion === 'repetidores' && $columnaEdad === 'menos_12');
            }

            $minimo = $grado + 10; // 2° admite desde 12; 3° desde 13.
            if ($columnaEdad === 'menos_12') {
                return false;
            }

            if (ctype_digit($columnaEdad)) {
                return (int) $columnaEdad >= $minimo;
            }

            return $columnaEdad === '18_mas';
        }

        return false;
    }

    /** @param array<string,string> $columnas */
    private function calcularSubtotalesGrado(string $slug, array $datos, array $columnas): array
    {
        if ($slug === 'preescolar') {
            foreach (array_keys($columnas) as $clave) {
                $datos['subtotal']['edades'][$clave] =
                    $datos['hombres']['edades'][$clave] + $datos['mujeres']['edades'][$clave];
            }
            $datos['subtotal']['total'] = $datos['hombres']['total'] + $datos['mujeres']['total'];

            return $datos;
        }

        foreach (['hombres', 'mujeres'] as $sexo) {
            foreach (array_keys($columnas) as $clave) {
                $datos[$sexo]['subtotal']['edades'][$clave] =
                    $datos[$sexo]['nuevo_ingreso']['edades'][$clave]
                    + $datos[$sexo]['repetidores']['edades'][$clave];
            }
            $datos[$sexo]['subtotal']['total'] =
                $datos[$sexo]['nuevo_ingreso']['total'] + $datos[$sexo]['repetidores']['total'];
        }

        foreach (array_keys($columnas) as $clave) {
            $datos['subtotal']['edades'][$clave] =
                $datos['hombres']['subtotal']['edades'][$clave]
                + $datos['mujeres']['subtotal']['edades'][$clave];
        }
        $datos['subtotal']['total'] = $datos['hombres']['subtotal']['total'] + $datos['mujeres']['subtotal']['total'];

        return $datos;
    }

    /** @param array<int,array<string,mixed>> $matriz @param array<string,string> $columnas */
    private function totalesGenerales(string $slug, array $matriz, array $columnas): array
    {
        if ($slug === 'preescolar') {
            $totales = [
                'hombres' => $this->filaSimple($columnas),
                'mujeres' => $this->filaSimple($columnas),
                'total' => $this->filaSimple($columnas),
            ];

            foreach ($matriz as $grado) {
                foreach (['hombres', 'mujeres'] as $sexo) {
                    foreach (array_keys($columnas) as $clave) {
                        $totales[$sexo]['edades'][$clave] += $grado[$sexo]['edades'][$clave];
                    }
                    $totales[$sexo]['total'] += $grado[$sexo]['total'];
                }
            }

            foreach (array_keys($columnas) as $clave) {
                $totales['total']['edades'][$clave] = $totales['hombres']['edades'][$clave] + $totales['mujeres']['edades'][$clave];
            }
            $totales['total']['total'] = $totales['hombres']['total'] + $totales['mujeres']['total'];

            return $totales;
        }

        $totales = [
            'hombres' => [
                'nuevo_ingreso' => $this->filaSimple($columnas),
                'repetidores' => $this->filaSimple($columnas),
                'subtotal' => $this->filaSimple($columnas),
            ],
            'mujeres' => [
                'nuevo_ingreso' => $this->filaSimple($columnas),
                'repetidores' => $this->filaSimple($columnas),
                'subtotal' => $this->filaSimple($columnas),
            ],
            'total' => $this->filaSimple($columnas),
        ];

        foreach ($matriz as $grado) {
            foreach (['hombres', 'mujeres'] as $sexo) {
                foreach (['nuevo_ingreso', 'repetidores'] as $condicion) {
                    foreach (array_keys($columnas) as $clave) {
                        $totales[$sexo][$condicion]['edades'][$clave] += $grado[$sexo][$condicion]['edades'][$clave];
                    }
                    $totales[$sexo][$condicion]['total'] += $grado[$sexo][$condicion]['total'];
                }
            }
        }

        foreach (['hombres', 'mujeres'] as $sexo) {
            foreach (array_keys($columnas) as $clave) {
                $totales[$sexo]['subtotal']['edades'][$clave] =
                    $totales[$sexo]['nuevo_ingreso']['edades'][$clave]
                    + $totales[$sexo]['repetidores']['edades'][$clave];
            }
            $totales[$sexo]['subtotal']['total'] =
                $totales[$sexo]['nuevo_ingreso']['total'] + $totales[$sexo]['repetidores']['total'];
        }

        foreach (array_keys($columnas) as $clave) {
            $totales['total']['edades'][$clave] =
                $totales['hombres']['subtotal']['edades'][$clave]
                + $totales['mujeres']['subtotal']['edades'][$clave];
        }
        $totales['total']['total'] = $totales['hombres']['subtotal']['total'] + $totales['mujeres']['subtotal']['total'];

        return $totales;
    }

    /** @param Collection<int,Grado> $grados */
    private function conteoGrupos(CicloEscolar $ciclo, Nivel $nivel, Collection $grados, CarbonImmutable $corte): array
    {
        $conteos = Grupo::withTrashed()
            ->selectRaw('grado_id, COUNT(*) as total')
            ->where('ciclo_escolar_id', $ciclo->id)
            ->where('nivel_id', $nivel->id)
            ->where(function ($query) use ($corte): void {
                $query->whereNull('created_at')
                    ->orWhereDate('created_at', '<=', $corte->toDateString());
            })
            ->where(function ($query) use ($corte): void {
                $query->whereNull('archivado_at')
                    ->orWhereDate('archivado_at', '>', $corte->toDateString());
            })
            ->where(function ($query) use ($corte): void {
                $query->whereNull('deleted_at')
                    ->orWhereDate('deleted_at', '>', $corte->toDateString());
            })
            ->groupBy('grado_id')
            ->pluck('total', 'grado_id');

        $resultado = [];
        foreach ($grados as $grado) {
            $resultado[] = [
                'grado_id' => (int) $grado->id,
                'grado' => (string) $grado->nombre,
                'total' => (int) ($conteos[$grado->id] ?? 0),
            ];
        }

        return [
            'por_grado' => $resultado,
            'total' => array_sum(array_column($resultado, 'total')),
        ];
    }

    private function codigoFormato(string $slug): string
    {
        return match ($slug) {
            'preescolar' => '911.1',
            'primaria' => '911.3',
            'secundaria' => '911.5',
            default => '911',
        };
    }

    /** @return array<string,mixed> */
    private function incidencia(InscripcionCiclo $historial, string $motivo): array
    {
        $alumno = $historial->inscripcion;

        return [
            'inscripcion_id' => (int) $historial->inscripcion_id,
            'matricula' => $alumno?->matricula ?: $historial->matricula,
            'alumno' => trim(implode(' ', array_filter([
                $alumno?->apellido_paterno,
                $alumno?->apellido_materno,
                $alumno?->nombre,
            ]))) ?: 'Alumno no disponible',
            'grado' => $historial->grado?->nombre,
            'motivo' => $motivo,
        ];
    }
}
