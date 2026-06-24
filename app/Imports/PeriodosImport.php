<?php

namespace App\Imports;

use App\Exceptions\PeriodoImportException;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\MesesBasica;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\Periodos;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Carbon\Carbon;
use DateTimeInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

class PeriodosImport implements ToCollection, WithHeadingRow, SkipsEmptyRows
{
    private int $creados = 0;
    private int $actualizados = 0;
    private int $sinCambios = 0;

    /** @var array<int, string> */
    private array $errores = [];

    public function collection(Collection $rows): void
    {
        $niveles = Nivel::query()->get()->keyBy('id');
        $ciclos = CicloEscolar::query()->get()->keyBy('id');
        $generaciones = Generacion::query()->get()->keyBy('id');
        $semestres = Semestre::query()->get()->keyBy('id');
        $mesesBasica = MesesBasica::query()->orderBy('id')->get();
        $periodosBasica = PeriodosBasica::query()->orderBy('periodo')->get();
        $mesesBachillerato = MesesBachillerato::query()->get()->keyBy('id');
        $parciales = Parcial::query()->get()->keyBy('id');

        $mesesBasicaPorId = $mesesBasica->keyBy('id');
        $periodosBasicaPorId = $periodosBasica->keyBy('id');
        $ordenMesesBasica = $mesesBasica->pluck('id')->values()->all();
        $ordenPeriodosBasica = $periodosBasica->pluck('id')->values()->all();

        $filasValidas = [];
        $llavesArchivo = [];

        foreach ($rows as $indice => $row) {
            $numeroFila = $indice + 2;
            $filaOriginal = $row->toArray();

            if ($this->filaVacia($filaOriginal)) {
                continue;
            }

            $fila = [
                'tipo' => $this->normalizarTipo($filaOriginal['tipo'] ?? null),
                'nivel_id' => $this->extraerId($filaOriginal['nivel_id'] ?? null),
                'ciclo_escolar_id' => $this->extraerId($filaOriginal['ciclo_escolar_id'] ?? null),
                'generacion_id' => $this->extraerId($filaOriginal['generacion_id'] ?? null),
                'semestre_id' => $this->extraerId($filaOriginal['semestre_id'] ?? null),
                'mes_basica_id' => $this->extraerId($filaOriginal['mes_basica_id'] ?? null),
                'periodo_basica_id' => $this->extraerId($filaOriginal['periodo_basica_id'] ?? null),
                'mes_bachillerato_id' => $this->extraerId($filaOriginal['mes_bachillerato_id'] ?? null),
                'parcial_bachillerato_id' => $this->extraerId($filaOriginal['parcial_bachillerato_id'] ?? null),
                'fecha_inicio' => $this->normalizarFecha($filaOriginal['fecha_inicio'] ?? null),
                'fecha_fin' => $this->normalizarFecha($filaOriginal['fecha_fin'] ?? null),
            ];

            $validator = Validator::make($fila, [
                'tipo' => ['required', 'in:BASICA,BACHILLERATO'],
                'nivel_id' => ['required', 'integer'],
                'ciclo_escolar_id' => ['required', 'integer'],
                'generacion_id' => ['nullable', 'integer'],
                'semestre_id' => ['nullable', 'integer'],
                'mes_basica_id' => ['nullable', 'integer'],
                'periodo_basica_id' => ['nullable', 'integer'],
                'mes_bachillerato_id' => ['nullable', 'integer'],
                'parcial_bachillerato_id' => ['nullable', 'integer'],
                'fecha_inicio' => ['nullable', 'required_with:fecha_fin', 'date_format:Y-m-d'],
                'fecha_fin' => ['nullable', 'required_with:fecha_inicio', 'date_format:Y-m-d', 'after_or_equal:fecha_inicio'],
            ], [
                'tipo.required' => 'el tipo es obligatorio',
                'tipo.in' => 'el tipo debe ser BASICA o BACHILLERATO',
                'nivel_id.required' => 'el nivel es obligatorio',
                'nivel_id.integer' => 'el nivel no tiene un ID válido',
                'ciclo_escolar_id.required' => 'el ciclo escolar es obligatorio',
                'ciclo_escolar_id.integer' => 'el ciclo escolar no tiene un ID válido',
                'generacion_id.integer' => 'la generación no contiene un ID válido',
                'semestre_id.integer' => 'el semestre no contiene un ID válido',
                'mes_basica_id.integer' => 'el mes de básica no contiene un ID válido',
                'periodo_basica_id.integer' => 'el periodo de básica no contiene un ID válido',
                'mes_bachillerato_id.integer' => 'el mes de bachillerato no contiene un ID válido',
                'parcial_bachillerato_id.integer' => 'el parcial no contiene un ID válido',
                'fecha_inicio.required_with' => 'debes capturar también la fecha de inicio',
                'fecha_fin.required_with' => 'debes capturar también la fecha de fin',
                'fecha_inicio.date_format' => 'la fecha de inicio debe usar el formato AAAA-MM-DD',
                'fecha_fin.date_format' => 'la fecha de fin debe usar el formato AAAA-MM-DD',
                'fecha_fin.after_or_equal' => 'la fecha de fin debe ser igual o posterior a la fecha de inicio',
            ]);

            if ($validator->fails()) {
                $this->agregarErrores($numeroFila, $validator->errors()->all());
                continue;
            }

            $nivel = $niveles->get($fila['nivel_id']);
            $ciclo = $ciclos->get($fila['ciclo_escolar_id']);

            if (!$nivel) {
                $this->errores[] = "Fila {$numeroFila}: el nivel seleccionado no existe.";
                continue;
            }

            if (!$ciclo) {
                $this->errores[] = "Fila {$numeroFila}: el ciclo escolar seleccionado no existe.";
                continue;
            }

            $esBachillerato = $nivel->slug === 'bachillerato';
            $tipoEsperado = $esBachillerato ? 'BACHILLERATO' : 'BASICA';

            if ($fila['tipo'] !== $tipoEsperado) {
                $this->errores[] = "Fila {$numeroFila}: el tipo {$fila['tipo']} no corresponde al nivel {$nivel->nombre}.";
                continue;
            }

            if (!$this->fechasDentroDelCiclo($fila, $ciclo)) {
                $this->errores[] = "Fila {$numeroFila}: las fechas deben estar entre los años {$ciclo->inicio_anio} y {$ciclo->fin_anio} del ciclo escolar.";
                continue;
            }

            if ($esBachillerato) {
                $erroresFila = $this->validarBachillerato(
                    $fila,
                    $nivel->id,
                    $generaciones,
                    $semestres,
                    $mesesBachillerato,
                    $parciales
                );
            } else {
                $erroresFila = $this->validarBasica(
                    $fila,
                    $mesesBasicaPorId,
                    $periodosBasicaPorId,
                    $ordenMesesBasica,
                    $ordenPeriodosBasica
                );
            }

            if ($erroresFila !== []) {
                $this->agregarErrores($numeroFila, $erroresFila);
                continue;
            }

            $fila['es_bachillerato'] = $esBachillerato;
            $llave = $this->llaveNatural($fila);

            if (isset($llavesArchivo[$llave])) {
                $filaAnterior = $llavesArchivo[$llave];
                $this->errores[] = "Fila {$numeroFila}: el mismo periodo ya fue capturado en la fila {$filaAnterior}.";
                continue;
            }

            $llavesArchivo[$llave] = $numeroFila;
            $filasValidas[] = $fila;
        }

        if ($this->errores !== []) {
            throw new PeriodoImportException($this->errores);
        }

        if ($filasValidas === []) {
            throw new PeriodoImportException([
                'La hoja “Periodos” no contiene filas para importar.',
            ]);
        }

        DB::transaction(function () use ($filasValidas): void {
            foreach ($filasValidas as $fila) {
                $this->guardarFila($fila);
            }
        });
    }

    /**
     * @return array<int, string>
     */
    private function validarBasica(
        array $fila,
        Collection $mesesBasica,
        Collection $periodosBasica,
        array $ordenMeses,
        array $ordenPeriodos
    ): array {
        $errores = [];

        if (!$fila['mes_basica_id'] || !$mesesBasica->has($fila['mes_basica_id'])) {
            $errores[] = 'el mes de básica es obligatorio y debe existir';
        }

        if (!$fila['periodo_basica_id'] || !$periodosBasica->has($fila['periodo_basica_id'])) {
            $errores[] = 'el periodo de básica es obligatorio y debe existir';
        }

        if ($fila['generacion_id'] || $fila['semestre_id'] || $fila['mes_bachillerato_id'] || $fila['parcial_bachillerato_id']) {
            $errores[] = 'los campos de bachillerato deben quedar vacíos para un periodo de básica';
        }

        if ($errores === []) {
            $posicionMes = array_search($fila['mes_basica_id'], $ordenMeses, true);
            $posicionPeriodo = array_search($fila['periodo_basica_id'], $ordenPeriodos, true);

            if ($posicionMes !== $posicionPeriodo) {
                $errores[] = 'el mes de básica no corresponde al periodo seleccionado';
            }
        }

        return $errores;
    }

    /**
     * @return array<int, string>
     */
    private function validarBachillerato(
        array $fila,
        int $nivelId,
        Collection $generaciones,
        Collection $semestres,
        Collection $mesesBachillerato,
        Collection $parciales
    ): array {
        $errores = [];
        $generacion = $generaciones->get($fila['generacion_id']);
        $semestre = $semestres->get($fila['semestre_id']);

        if (!$generacion) {
            $errores[] = 'la generación es obligatoria y debe existir';
        } elseif ((int) $generacion->nivel_id !== $nivelId) {
            $errores[] = 'la generación no pertenece al nivel bachillerato';
        }

        if (!$semestre) {
            $errores[] = 'el semestre es obligatorio y debe existir';
        }

        if (!$fila['mes_bachillerato_id'] || !$mesesBachillerato->has($fila['mes_bachillerato_id'])) {
            $errores[] = 'el mes de bachillerato es obligatorio y debe existir';
        }

        if (!$fila['parcial_bachillerato_id'] || !$parciales->has($fila['parcial_bachillerato_id'])) {
            $errores[] = 'el parcial es obligatorio y debe existir';
        }

        if ($fila['mes_basica_id'] || $fila['periodo_basica_id']) {
            $errores[] = 'los campos de básica deben quedar vacíos para bachillerato';
        }

        return $errores;
    }

    private function guardarFila(array $fila): void
    {
        if ($fila['es_bachillerato']) {
            $periodo = Periodos::query()
                ->where('nivel_id', $fila['nivel_id'])
                ->where('ciclo_escolar_id', $fila['ciclo_escolar_id'])
                ->where('generacion_id', $fila['generacion_id'])
                ->where('semestre_id', $fila['semestre_id'])
                ->where('mes_bachillerato_id', $fila['mes_bachillerato_id'])
                ->where('parcial_bachillerato_id', $fila['parcial_bachillerato_id'])
                ->first();
        } else {
            $periodo = Periodos::query()
                ->where('nivel_id', $fila['nivel_id'])
                ->where('ciclo_escolar_id', $fila['ciclo_escolar_id'])
                ->where('mes_basica_id', $fila['mes_basica_id'])
                ->where('periodo_basica_id', $fila['periodo_basica_id'])
                ->first();
        }

        if (!$periodo) {
            Periodos::create($this->payload($fila));
            $this->creados++;

            return;
        }

        // Las celdas vacías no borran fechas existentes accidentalmente.
        if ($fila['fecha_inicio'] !== null) {
            $periodo->fecha_inicio = $fila['fecha_inicio'];
        }

        if ($fila['fecha_fin'] !== null) {
            $periodo->fecha_fin = $fila['fecha_fin'];
        }

        if ($periodo->isDirty(['fecha_inicio', 'fecha_fin'])) {
            $periodo->save();
            $this->actualizados++;
        } else {
            $this->sinCambios++;
        }
    }

    private function payload(array $fila): array
    {
        return [
            'nivel_id' => $fila['nivel_id'],
            'ciclo_escolar_id' => $fila['ciclo_escolar_id'],
            'generacion_id' => $fila['es_bachillerato'] ? $fila['generacion_id'] : null,
            'semestre_id' => $fila['es_bachillerato'] ? $fila['semestre_id'] : null,
            'mes_bachillerato_id' => $fila['es_bachillerato'] ? $fila['mes_bachillerato_id'] : null,
            'parcial_bachillerato_id' => $fila['es_bachillerato'] ? $fila['parcial_bachillerato_id'] : null,
            'mes_basica_id' => $fila['es_bachillerato'] ? null : $fila['mes_basica_id'],
            'periodo_basica_id' => $fila['es_bachillerato'] ? null : $fila['periodo_basica_id'],
            'fecha_inicio' => $fila['fecha_inicio'],
            'fecha_fin' => $fila['fecha_fin'],
        ];
    }

    private function llaveNatural(array $fila): string
    {
        if ($fila['es_bachillerato']) {
            return implode('|', [
                'B',
                $fila['nivel_id'],
                $fila['ciclo_escolar_id'],
                $fila['generacion_id'],
                $fila['semestre_id'],
                $fila['mes_bachillerato_id'],
                $fila['parcial_bachillerato_id'],
            ]);
        }

        return implode('|', [
            'A',
            $fila['nivel_id'],
            $fila['ciclo_escolar_id'],
            $fila['mes_basica_id'],
            $fila['periodo_basica_id'],
        ]);
    }

    private function fechasDentroDelCiclo(array $fila, CicloEscolar $ciclo): bool
    {
        foreach (['fecha_inicio', 'fecha_fin'] as $campo) {
            if (!$fila[$campo]) {
                continue;
            }

            $anio = (int) Carbon::createFromFormat('Y-m-d', $fila[$campo])->format('Y');

            if ($anio < (int) $ciclo->inicio_anio || $anio > (int) $ciclo->fin_anio) {
                return false;
            }
        }

        return true;
    }

    private function extraerId(mixed $valor): mixed
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        if (is_int($valor)) {
            return $valor > 0 ? $valor : 'ID_INVALIDO';
        }

        if (is_float($valor)) {
            return floor($valor) === $valor && $valor > 0
                ? (int) $valor
                : 'ID_INVALIDO';
        }

        $texto = trim((string) $valor);

        // Acepta "12" o "12 | Descripción", pero rechaza decimales y texto arbitrario.
        if (preg_match('/^(\d+)(?:\s*\|.*)?$/u', $texto, $coincidencias) !== 1) {
            return 'ID_INVALIDO';
        }

        $id = (int) $coincidencias[1];

        return $id > 0 ? $id : 'ID_INVALIDO';
    }

    private function normalizarTipo(mixed $valor): ?string
    {
        if ($valor === null || trim((string) $valor) === '') {
            return null;
        }

        return strtoupper(Str::ascii(trim((string) $valor)));
    }

    private function normalizarFecha(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if ($valor instanceof DateTimeInterface) {
            return $valor->format('Y-m-d');
        }

        if (is_numeric($valor)) {
            try {
                return ExcelDate::excelToDateTimeObject((float) $valor)->format('Y-m-d');
            } catch (Throwable) {
                return 'FECHA_INVALIDA';
            }
        }

        $texto = trim((string) $valor);

        foreach (['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y'] as $formato) {
            try {
                $fecha = Carbon::createFromFormat($formato, $texto);

                if ($fecha && $fecha->format($formato) === $texto) {
                    return $fecha->format('Y-m-d');
                }
            } catch (Throwable) {
                // Se intenta el siguiente formato.
            }
        }

        return $texto;
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $valor) {
            if ($valor instanceof DateTimeInterface) {
                return false;
            }

            if ($valor !== null && (!is_string($valor) || trim($valor) !== '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, string>  $mensajes
     */
    private function agregarErrores(int $fila, array $mensajes): void
    {
        foreach ($mensajes as $mensaje) {
            $this->errores[] = "Fila {$fila}: {$mensaje}.";
        }
    }

    public function creados(): int
    {
        return $this->creados;
    }

    public function actualizados(): int
    {
        return $this->actualizados;
    }

    public function sinCambios(): int
    {
        return $this->sinCambios;
    }

    /**
     * @return array<int, string>
     */
    public function errores(): array
    {
        return $this->errores;
    }
}
