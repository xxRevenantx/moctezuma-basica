<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Support\CurpRenapo;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class SeceListaService
{
    public function __construct(private readonly EdadEscolarService $edadEscolar)
    {
    }

    /**
     * Prepara la información exclusiva del formato SECE sin alterar los
     * modelos de inscripción ni la lógica de matrícula vigente.
     *
     * @param Collection<int, Inscripcion> $alumnos
     * @return array<string, mixed>
     */
    public function construir(
        Collection $alumnos,
        Nivel $nivel,
        Grado $grado,
        CicloEscolar $cicloEscolar,
        ?CarbonInterface $ahora = null,
    ): array {
        $ahora = $ahora ? CarbonImmutable::instance($ahora) : CarbonImmutable::now();
        $esBachillerato = mb_strtolower(trim((string) $nivel->slug)) === 'bachillerato';

        $filas = $alumnos
            ->values()
            ->map(function (Inscripcion $alumno) use ($nivel, $grado, $cicloEscolar, $ahora): array {
                $analisisEdad = $this->edadEscolar->analizar(
                    $alumno->fecha_nacimiento,
                    $nivel,
                    $grado,
                    $cicloEscolar,
                    $ahora,
                );

                $entidad = CurpRenapo::entidadNacimiento($alumno->curp);
                $sexo = mb_strtoupper(trim((string) $alumno->genero), 'UTF-8');

                return [
                    'id' => (int) $alumno->id,
                    'matricula' => trim((string) $alumno->matricula),
                    'nombre_completo' => mb_strtoupper(trim(implode(' ', array_filter([
                        $alumno->apellido_paterno,
                        $alumno->apellido_materno,
                        $alumno->nombre,
                    ], fn ($valor): bool => filled($valor)))), 'UTF-8'),
                    'curp' => mb_strtoupper(trim((string) $alumno->curp), 'UTF-8'),
                    'sexo' => in_array($sexo, ['H', 'M'], true) ? $sexo : '—',
                    'sexo_texto' => match ($sexo) {
                        'H' => 'HOMBRE',
                        'M' => 'MUJER',
                        default => 'SIN DATO',
                    },
                    'fecha_nacimiento' => $alumno->fecha_nacimiento,
                    'edad_actual' => is_numeric($analisisEdad['edad_actual'] ?? null)
                        ? (int) $analisisEdad['edad_actual']
                        : null,
                    'entidad_codigo' => $entidad['codigo'],
                    'entidad_nombre' => $entidad['nombre'],
                    'entidad_reconocida' => $entidad['reconocida'],
                    'entidad_etiqueta' => CurpRenapo::etiquetaEntidadNacimiento($alumno->curp),
                    'situacion' => (string) ($analisisEdad['situacion'] ?? EdadEscolarService::SITUACION_SIN_DATOS),
                    'situacion_etiqueta' => (string) ($analisisEdad['etiqueta'] ?? 'Sin datos'),
                    'edad_corte' => $analisisEdad['edad_corte'] ?? null,
                    'edad_esperada' => $analisisEdad['edad_esperada'] ?? null,
                ];
            });

        $total = $filas->count();
        $hombres = $filas->where('sexo', 'H')->count();
        $mujeres = $filas->where('sexo', 'M')->count();
        $sinSexo = max(0, $total - $hombres - $mujeres);
        $edades = $filas->pluck('edad_actual')->filter(fn ($edad): bool => is_int($edad))->values();

        $porEdad = $filas
            ->filter(fn (array $fila): bool => is_int($fila['edad_actual']))
            ->groupBy('edad_actual')
            ->sortKeys()
            ->map(function (Collection $grupoEdad, int|string $edad) use ($total): array {
                $subtotal = $grupoEdad->count();

                return [
                    'edad' => (int) $edad,
                    'hombres' => $grupoEdad->where('sexo', 'H')->count(),
                    'mujeres' => $grupoEdad->where('sexo', 'M')->count(),
                    'total' => $subtotal,
                    'porcentaje' => $total > 0 ? round(($subtotal / $total) * 100, 1) : 0.0,
                ];
            })
            ->values();

        $situaciones = $this->estadisticaSituacion($filas, $esBachillerato, $total);

        return [
            'filas' => $filas,
            'resumen' => [
                'total' => $total,
                'hombres' => $hombres,
                'mujeres' => $mujeres,
                'sin_sexo' => $sinSexo,
                'edad_promedio' => $edades->isNotEmpty() ? round((float) $edades->avg(), 1) : null,
                'edad_minima' => $edades->isNotEmpty() ? (int) $edades->min() : null,
                'edad_maxima' => $edades->isNotEmpty() ? (int) $edades->max() : null,
            ],
            'por_edad' => $porEdad,
            'situaciones' => $situaciones,
            'es_bachillerato' => $esBachillerato,
            'fecha_calculo' => $ahora,
        ];
    }

    /**
     * @param Collection<int, array<string, mixed>> $filas
     * @return Collection<int, array<string, mixed>>
     */
    private function estadisticaSituacion(Collection $filas, bool $esBachillerato, int $total): Collection
    {
        $definiciones = $esBachillerato
            ? [
                EdadEscolarService::SITUACION_ADECUADA => 'Edad típica',
                EdadEscolarService::SITUACION_MAYOR => 'Sobre edad típica',
                EdadEscolarService::SITUACION_MENOR => 'Bajo edad típica',
                EdadEscolarService::SITUACION_SIN_DATOS => 'Sin datos',
            ]
            : [
                EdadEscolarService::SITUACION_ADECUADA => 'Edad adecuada',
                EdadEscolarService::SITUACION_MAYOR => '+1 año',
                EdadEscolarService::SITUACION_EXTRAEDAD => 'Extraedad',
                EdadEscolarService::SITUACION_MENOR => 'Menor',
                EdadEscolarService::SITUACION_SIN_DATOS => 'Sin datos',
            ];

        return collect($definiciones)
            ->map(function (string $etiqueta, string $situacion) use ($filas, $total): array {
                $cantidad = $filas->where('situacion', $situacion)->count();

                return [
                    'clave' => $situacion,
                    'etiqueta' => $etiqueta,
                    'cantidad' => $cantidad,
                    'porcentaje' => $total > 0 ? round(($cantidad / $total) * 100, 1) : 0.0,
                ];
            })
            ->values();
    }
}
