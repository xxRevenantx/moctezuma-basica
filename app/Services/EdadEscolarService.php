<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Nivel;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use DateTimeInterface;
use Throwable;

class EdadEscolarService
{
    public const SITUACION_ADECUADA = 'adecuada';
    public const SITUACION_MAYOR = 'mayor';
    public const SITUACION_EXTRAEDAD = 'extraedad';
    public const SITUACION_MENOR = 'menor';
    public const SITUACION_SIN_DATOS = 'sin_datos';

    /**
     * Analiza la edad del alumno para su grado y ciclo escolar.
     *
     * Educación básica: +2 años o más se clasifica como extraedad.
     * Bachillerato: se informa como edad típica, sin usar la etiqueta normativa
     * de extraedad de educación básica.
     *
     * @return array<string, mixed>
     */
    public function analizar(
        mixed $fechaNacimiento,
        ?Nivel $nivel,
        ?Grado $grado,
        ?CicloEscolar $cicloEscolar,
        ?CarbonInterface $ahora = null,
    ): array {
        $fecha = $this->fechaNacimiento($fechaNacimiento);
        $ahora = $ahora ? CarbonImmutable::instance($ahora) : CarbonImmutable::now();
        $slugNivel = mb_strtolower(trim((string) ($nivel?->slug ?? '')));
        $numeroGrado = $this->numeroGrado($grado);
        $edadEsperada = $this->edadEsperada($slugNivel, $numeroGrado);
        $esBasica = in_array($slugNivel, (array) config('edad_escolar.niveles_basica', []), true);
        $esBachillerato = $slugNivel === 'bachillerato';

        if (! $fecha || ! $cicloEscolar || ! $edadEsperada || (! $esBasica && ! $esBachillerato)) {
            return $this->sinDatos($fecha, $ahora, $nivel, $grado, $cicloEscolar);
        }

        $corte = $this->fechaCorteEscolar($cicloEscolar);
        $edadActual = $this->edadEnFecha($fecha, $ahora);
        $edadCorte = $this->edadEnFecha($fecha, $corte);
        $diferencia = $edadCorte - $edadEsperada;

        if ($esBachillerato) {
            $situacion = match (true) {
                $diferencia < 0 => self::SITUACION_MENOR,
                $diferencia === 0 => self::SITUACION_ADECUADA,
                default => self::SITUACION_MAYOR,
            };

            return [
                'disponible' => true,
                'nivel_slug' => $slugNivel,
                'es_basica' => false,
                'es_bachillerato' => true,
                'edad_actual' => $edadActual,
                'edad_corte' => $edadCorte,
                'edad_esperada' => $edadEsperada,
                'diferencia' => $diferencia,
                'situacion' => $situacion,
                'etiqueta' => $this->etiquetaBachillerato($situacion, $diferencia),
                'descripcion' => $this->descripcionBachillerato($situacion, $diferencia, $edadEsperada),
                'fecha_corte' => $corte,
                'fecha_corte_texto' => $corte->format('d/m/Y'),
                'referencia' => 'Edad típica de educación media superior',
            ];
        }

        $situacion = match (true) {
            $diferencia < 0 => self::SITUACION_MENOR,
            $diferencia === 0 => self::SITUACION_ADECUADA,
            $diferencia === 1 => self::SITUACION_MAYOR,
            default => self::SITUACION_EXTRAEDAD,
        };

        return [
            'disponible' => true,
            'nivel_slug' => $slugNivel,
            'es_basica' => true,
            'es_bachillerato' => false,
            'edad_actual' => $edadActual,
            'edad_corte' => $edadCorte,
            'edad_esperada' => $edadEsperada,
            'diferencia' => $diferencia,
            'situacion' => $situacion,
            'etiqueta' => $this->etiquetaBasica($situacion, $diferencia),
            'descripcion' => $this->descripcionBasica($situacion, $diferencia, $edadEsperada),
            'fecha_corte' => $corte,
            'fecha_corte_texto' => $corte->format('d/m/Y'),
            'referencia' => 'Edad escolar de referencia para educación básica',
        ];
    }

    public function edadEsperada(string $nivelSlug, ?int $numeroGrado): ?int
    {
        if (! $numeroGrado) {
            return null;
        }

        $valor = data_get(config('edad_escolar.edades_esperadas', []), $nivelSlug.'.'.$numeroGrado);

        return is_numeric($valor) ? (int) $valor : null;
    }

    public function fechaCorteEscolar(CicloEscolar $cicloEscolar): CarbonImmutable
    {
        [$mes, $dia] = $this->mesDia((string) config('edad_escolar.corte_escolar', '12-31'));

        return CarbonImmutable::create((int) $cicloEscolar->inicio_anio, $mes, $dia)->endOfDay();
    }

    public function fechaCorte911(CicloEscolar $cicloEscolar): CarbonImmutable
    {
        [$mes, $dia] = $this->mesDia((string) config('edad_escolar.corte_911', '09-30'));

        return CarbonImmutable::create((int) $cicloEscolar->inicio_anio, $mes, $dia)->endOfDay();
    }

    public function fechaEdad911(CicloEscolar $cicloEscolar): CarbonImmutable
    {
        [$mes, $dia] = $this->mesDia((string) config('edad_escolar.corte_edad_911', '09-01'));

        return CarbonImmutable::create((int) $cicloEscolar->inicio_anio, $mes, $dia)->endOfDay();
    }

    public function edadEnFecha(CarbonImmutable $fechaNacimiento, CarbonInterface $fechaReferencia): int
    {
        return (int) $fechaNacimiento->diffInYears(CarbonImmutable::instance($fechaReferencia));
    }

    public function claseBadge(string $situacion, bool $bachillerato = false): string
    {
        if ($bachillerato && $situacion === self::SITUACION_MAYOR) {
            return 'bg-violet-100 text-violet-700 dark:bg-violet-950/40 dark:text-violet-300';
        }

        return match ($situacion) {
            self::SITUACION_ADECUADA => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-300',
            self::SITUACION_MAYOR => 'bg-amber-100 text-amber-700 dark:bg-amber-950/40 dark:text-amber-300',
            self::SITUACION_EXTRAEDAD => 'bg-rose-100 text-rose-700 dark:bg-rose-950/40 dark:text-rose-300',
            self::SITUACION_MENOR => 'bg-sky-100 text-sky-700 dark:bg-sky-950/40 dark:text-sky-300',
            default => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        };
    }

    public function numeroGrado(?Grado $grado): ?int
    {
        if (! $grado) {
            return null;
        }

        if (is_numeric($grado->nombre)) {
            return (int) $grado->nombre;
        }

        if (preg_match('/\d+/', (string) $grado->slug, $coincidencia)) {
            return (int) $coincidencia[0];
        }

        return null;
    }

    private function fechaNacimiento(mixed $fecha): ?CarbonImmutable
    {
        if (! $fecha) {
            return null;
        }

        try {
            if ($fecha instanceof DateTimeInterface) {
                return CarbonImmutable::instance($fecha)->startOfDay();
            }

            return CarbonImmutable::parse((string) $fecha)->startOfDay();
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array{0:int,1:int} */
    private function mesDia(string $valor): array
    {
        [$mes, $dia] = array_pad(array_map('intval', explode('-', $valor, 2)), 2, 1);

        return [max(1, min(12, $mes)), max(1, min(31, $dia))];
    }

    /** @return array<string, mixed> */
    private function sinDatos(
        ?CarbonImmutable $fecha,
        CarbonImmutable $ahora,
        ?Nivel $nivel,
        ?Grado $grado,
        ?CicloEscolar $cicloEscolar,
    ): array {
        return [
            'disponible' => false,
            'nivel_slug' => $nivel?->slug,
            'es_basica' => false,
            'es_bachillerato' => false,
            'edad_actual' => $fecha ? $this->edadEnFecha($fecha, $ahora) : null,
            'edad_corte' => null,
            'edad_esperada' => $this->edadEsperada((string) $nivel?->slug, $this->numeroGrado($grado)),
            'diferencia' => null,
            'situacion' => self::SITUACION_SIN_DATOS,
            'etiqueta' => 'Sin datos suficientes',
            'descripcion' => 'Se requiere fecha de nacimiento, nivel, grado y ciclo escolar para calcular la edad escolar.',
            'fecha_corte' => $cicloEscolar ? $this->fechaCorteEscolar($cicloEscolar) : null,
            'fecha_corte_texto' => $cicloEscolar ? $this->fechaCorteEscolar($cicloEscolar)->format('d/m/Y') : null,
            'referencia' => null,
        ];
    }

    private function etiquetaBasica(string $situacion, int $diferencia): string
    {
        return match ($situacion) {
            self::SITUACION_ADECUADA => 'Edad adecuada',
            self::SITUACION_MAYOR => '+1 año',
            self::SITUACION_EXTRAEDAD => 'Extraedad +'.$diferencia.' años',
            self::SITUACION_MENOR => abs($diferencia).' año'.(abs($diferencia) === 1 ? '' : 's').' menor',
            default => 'Sin datos',
        };
    }

    private function descripcionBasica(string $situacion, int $diferencia, int $esperada): string
    {
        return match ($situacion) {
            self::SITUACION_ADECUADA => "Coincide con la edad escolar esperada de {$esperada} años para el grado.",
            self::SITUACION_MAYOR => "Tiene un año más que la edad escolar esperada de {$esperada} años.",
            self::SITUACION_EXTRAEDAD => "Presenta un desfase de {$diferencia} años respecto de la edad escolar esperada de {$esperada} años.",
            self::SITUACION_MENOR => 'Tiene '.abs($diferencia).' año'.(abs($diferencia) === 1 ? '' : 's')." menos que la edad escolar esperada de {$esperada} años.",
            default => '',
        };
    }

    private function etiquetaBachillerato(string $situacion, int $diferencia): string
    {
        return match ($situacion) {
            self::SITUACION_ADECUADA => 'Edad típica',
            self::SITUACION_MAYOR => '+'.$diferencia.' año'.($diferencia === 1 ? '' : 's').' sobre edad típica',
            self::SITUACION_MENOR => abs($diferencia).' año'.(abs($diferencia) === 1 ? '' : 's').' bajo edad típica',
            default => 'Sin datos',
        };
    }

    private function descripcionBachillerato(string $situacion, int $diferencia, int $esperada): string
    {
        return match ($situacion) {
            self::SITUACION_ADECUADA => "Coincide con la edad típica de {$esperada} años para el grado de bachillerato.",
            self::SITUACION_MAYOR => "Está {$diferencia} año".($diferencia === 1 ? '' : 's')." por encima de la edad típica de {$esperada} años. No se etiqueta como extraedad de educación básica.",
            self::SITUACION_MENOR => 'Está '.abs($diferencia).' año'.(abs($diferencia) === 1 ? '' : 's')." por debajo de la edad típica de {$esperada} años.",
            default => '',
        };
    }
}
