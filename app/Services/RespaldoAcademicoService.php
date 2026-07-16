<?php

namespace App\Services;

use App\Exceptions\RespaldoAcademicoImportException;
use App\Support\RespaldoAcademico;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Throwable;

class RespaldoAcademicoService
{

    /**
     * Analiza un respaldo completo sin escribir ningún dato.
     *
     * @return array{
     *     tipo:string,
     *     total_creados:int,
     *     total_actualizados:int,
     *     total_sin_cambios:int,
     *     tablas:array<string,array<string,mixed>>
     * }
     */
    public function previsualizar(string $tipo, string $rutaArchivo): array
    {
        $configuracion = RespaldoAcademico::configuracion($tipo);
        $this->validarTablasDisponibles($configuracion['tablas']);
        $libro = $this->abrirLibro($rutaArchivo);

        try {
            $this->validarMetadata($libro, $tipo);

            $resultado = [
                'tipo' => $tipo,
                'total_creados' => 0,
                'total_actualizados' => 0,
                'total_sin_cambios' => 0,
                'tablas' => [],
            ];

            foreach ($configuracion['tablas'] as $tabla => $datosTabla) {
                $hoja = $libro->getSheetByName($datosTabla['hoja']);

                if (! $hoja instanceof Worksheet) {
                    throw new RespaldoAcademicoImportException(
                        "Falta la hoja obligatoria «{$datosTabla['hoja']}». Descarga nuevamente el respaldo correcto."
                    );
                }

                $filas = $this->leerHoja(
                    hoja: $hoja,
                    tabla: $tabla,
                    columnasDiferidas: $datosTabla['diferidas'],
                );

                $resumenTabla = $this->compararTabla($tabla, $filas);
                $resultado['tablas'][$tabla] = [
                    'hoja' => $datosTabla['hoja'],
                    ...$resumenTabla,
                ];
                $resultado['total_creados'] += $resumenTabla['creados'];
                $resultado['total_actualizados'] += $resumenTabla['actualizados'];
                $resultado['total_sin_cambios'] += $resumenTabla['sin_cambios'];
            }

            return $resultado;
        } finally {
            $libro->disconnectWorksheets();
            unset($libro);
        }
    }

    /**
     * Importa un respaldo sin cambiar jamás el ID de un registro existente.
     *
     * - Si el ID existe, actualiza únicamente las demás columnas.
     * - Si el ID no existe, crea el registro usando exactamente ese ID.
     * - No elimina registros ausentes del archivo.
     * - Cualquier error revierte toda la importación.
     *
     * @return array{
     *     tipo:string,
     *     total_creados:int,
     *     total_actualizados:int,
     *     total_sin_cambios:int,
     *     tablas:array<string,array{hoja:string,creados:int,actualizados:int,sin_cambios:int,total:int}>
     * }
     */
    public function importar(string $tipo, string $rutaArchivo, ?int $usuarioId = null): array
    {
        $configuracion = RespaldoAcademico::configuracion($tipo);
        $this->validarTablasDisponibles($configuracion['tablas']);

        $libro = $this->abrirLibro($rutaArchivo);

        try {
            $this->validarMetadata($libro, $tipo);

            $paquetes = [];

            foreach ($configuracion['tablas'] as $tabla => $datosTabla) {
                $hoja = $libro->getSheetByName($datosTabla['hoja']);

                if (!$hoja instanceof Worksheet) {
                    throw new RespaldoAcademicoImportException(
                        "Falta la hoja obligatoria «{$datosTabla['hoja']}». Descarga nuevamente el respaldo correcto."
                    );
                }

                $paquetes[$tabla] = $this->leerHoja(
                    hoja: $hoja,
                    tabla: $tabla,
                    columnasDiferidas: $datosTabla['diferidas'],
                );
            }

            $resumen = DB::transaction(function () use ($tipo, $configuracion, $paquetes, $usuarioId): array {
                $resultado = [
                    'tipo' => $tipo,
                    'total_creados' => 0,
                    'total_actualizados' => 0,
                    'total_sin_cambios' => 0,
                    'tablas' => [],
                ];

                foreach ($configuracion['tablas'] as $tabla => $datosTabla) {
                    $resumenTabla = $this->guardarTabla(
                        tabla: $tabla,
                        filas: $paquetes[$tabla],
                        columnasDiferidas: $datosTabla['diferidas'],
                    );

                    $resultado['tablas'][$tabla] = [
                        'hoja' => $datosTabla['hoja'],
                        ...$resumenTabla,
                    ];

                    $resultado['total_creados'] += $resumenTabla['creados'];
                    $resultado['total_actualizados'] += $resumenTabla['actualizados'];
                    $resultado['total_sin_cambios'] += $resumenTabla['sin_cambios'];
                }

                Log::info('Respaldo académico importado', [
                    'tipo' => $tipo,
                    'usuario_id' => $usuarioId,
                    'resumen' => $resultado,
                ]);

                return $resultado;
            }, 1);

            // MySQL normalmente ajusta el AUTO_INCREMENT al insertar IDs explícitos.
            // Esta sincronización adicional se ejecuta después de confirmar la transacción.
            foreach (array_keys($configuracion['tablas']) as $tabla) {
                $this->sincronizarAutoincremento($tabla);
            }

            return $resumen;
        } finally {
            $libro->disconnectWorksheets();
            unset($libro);
        }
    }

    /**
     * @param array<string,array{hoja:string,descripcion:string,diferidas:array<int,string>}> $tablas
     */
    private function validarTablasDisponibles(array $tablas): void
    {
        foreach (array_keys($tablas) as $tabla) {
            if (!Schema::hasTable($tabla)) {
                throw new RespaldoAcademicoImportException(
                    "No existe la tabla {$tabla}. Ejecuta primero todas las migraciones del sistema."
                );
            }
        }
    }

    private function abrirLibro(string $rutaArchivo): Spreadsheet
    {
        try {
            $lector = IOFactory::createReaderForFile($rutaArchivo);
            $lector->setReadDataOnly(false);

            return $lector->load($rutaArchivo);
        } catch (Throwable $e) {
            throw new RespaldoAcademicoImportException(
                'No se pudo abrir el archivo Excel. Verifica que no esté dañado y que sea un respaldo generado por el sistema.',
                previous: $e,
            );
        }
    }

    private function validarMetadata(Spreadsheet $libro, string $tipoEsperado): void
    {
        $hoja = $libro->getSheetByName('__metadata');

        if (!$hoja instanceof Worksheet) {
            throw new RespaldoAcademicoImportException(
                'El archivo no contiene la metadata de seguridad. Usa un respaldo exportado desde esta sección.'
            );
        }

        $metadata = [];

        foreach ($hoja->rangeToArray('A1:B20', null, false, false, false) as $fila) {
            $clave = trim((string) ($fila[0] ?? ''));

            if ($clave !== '') {
                $metadata[$clave] = trim((string) ($fila[1] ?? ''));
            }
        }

        if (($metadata['formato'] ?? null) !== 'moctezuma_respaldo_academico') {
            throw new RespaldoAcademicoImportException(
                'El archivo seleccionado no corresponde a un respaldo académico válido.'
            );
        }

        if (($metadata['version'] ?? null) !== RespaldoAcademico::VERSION_FORMATO) {
            throw new RespaldoAcademicoImportException(
                'La versión del respaldo no es compatible con esta instalación.'
            );
        }

        if (($metadata['tipo'] ?? null) !== $tipoEsperado) {
            $esperado = $tipoEsperado === RespaldoAcademico::TIPO_ALUMNOS
                ? 'alumnos'
                : 'calificaciones';

            throw new RespaldoAcademicoImportException(
                "Seleccionaste un archivo de otro tipo. Para esta acción se requiere un respaldo de {$esperado}."
            );
        }
    }

    /**
     * @param array<int,string> $columnasDiferidas
     * @return array<int,array{fila_excel:int,id:int,datos:array<string,mixed>,diferidos:array<string,mixed>}>
     */
    private function leerHoja(Worksheet $hoja, string $tabla, array $columnasDiferidas): array
    {
        $columnasTabla = Schema::getColumnListing($tabla);
        $ultimaColumna = $hoja->getHighestDataColumn();
        $ultimaFila = max(1, $hoja->getHighestDataRow());
        $matriz = $hoja->rangeToArray("A1:{$ultimaColumna}{$ultimaFila}", null, false, false, false);
        $encabezados = array_map(
            fn (mixed $valor): string => trim((string) $valor),
            $matriz[0] ?? []
        );

        $this->validarEncabezados($hoja->getTitle(), $encabezados, $columnasTabla);

        $indices = [];

        foreach ($encabezados as $indice => $encabezado) {
            $indices[$encabezado] = $indice;
        }

        $filas = [];
        $idsVistos = [];

        foreach (array_slice($matriz, 1, null, true) as $indiceMatriz => $fila) {
            $filaExcel = $indiceMatriz + 1;

            if ($this->filaVacia($fila)) {
                continue;
            }

            $this->rechazarFormulas($hoja, $filaExcel, count($encabezados));

            $idCrudo = $fila[$indices['id']] ?? null;
            $idOriginalCrudo = $fila[$indices['__id_original']] ?? null;
            $id = $this->idValido($idCrudo, $hoja->getTitle(), $filaExcel, 'id');
            $idOriginal = $this->idValido(
                $idOriginalCrudo,
                $hoja->getTitle(),
                $filaExcel,
                '__id_original'
            );

            if ($id !== $idOriginal) {
                throw new RespaldoAcademicoImportException(
                    "Hoja «{$hoja->getTitle()}», fila {$filaExcel}: el ID fue modificado. "
                    . "ID visible {$id}; ID original {$idOriginal}. Restaura el ID original o exporta un respaldo nuevo."
                );
            }

            if (isset($idsVistos[$id])) {
                throw new RespaldoAcademicoImportException(
                    "Hoja «{$hoja->getTitle()}», fila {$filaExcel}: el ID {$id} está repetido."
                );
            }

            $idsVistos[$id] = true;
            $datos = [];
            $diferidos = [];

            foreach ($columnasTabla as $columna) {
                if ($columna === 'id') {
                    continue;
                }

                $valor = $this->normalizarValorImportado($fila[$indices[$columna]] ?? null);

                if (in_array($columna, $columnasDiferidas, true)) {
                    $diferidos[$columna] = $valor;
                } else {
                    $datos[$columna] = $valor;
                }
            }

            $filas[] = [
                'fila_excel' => $filaExcel,
                'id' => $id,
                'datos' => $datos,
                'diferidos' => $diferidos,
            ];
        }

        return $filas;
    }

    /**
     * @param array<int,string> $encabezados
     * @param array<int,string> $columnasTabla
     */
    private function validarEncabezados(string $hoja, array $encabezados, array $columnasTabla): void
    {
        $encabezadosNoVacios = array_values(array_filter($encabezados, fn (string $valor) => $valor !== ''));

        if (count($encabezadosNoVacios) !== count(array_unique($encabezadosNoVacios))) {
            throw new RespaldoAcademicoImportException(
                "La hoja «{$hoja}» contiene encabezados repetidos."
            );
        }

        $requeridos = [...$columnasTabla, '__id_original'];
        $faltantes = array_values(array_diff($requeridos, $encabezadosNoVacios));
        $desconocidos = array_values(array_diff($encabezadosNoVacios, $requeridos));

        if ($faltantes !== []) {
            throw new RespaldoAcademicoImportException(
                "La hoja «{$hoja}» está incompleta. Faltan columnas: " . implode(', ', $faltantes) . '.'
            );
        }

        if ($desconocidos !== []) {
            throw new RespaldoAcademicoImportException(
                "La hoja «{$hoja}» contiene columnas no reconocidas: " . implode(', ', $desconocidos) . '.'
            );
        }
    }

    private function rechazarFormulas(Worksheet $hoja, int $filaExcel, int $cantidadColumnas): void
    {
        for ($columna = 1; $columna <= $cantidadColumnas; $columna++) {
            $coordenada = Coordinate::stringFromColumnIndex($columna) . $filaExcel;
            $celda = $hoja->getCell($coordenada);

            if ($celda->isFormula()) {
                throw new RespaldoAcademicoImportException(
                    "Hoja «{$hoja->getTitle()}», fila {$filaExcel}: no se permiten fórmulas en un respaldo."
                );
            }
        }
    }

    private function filaVacia(array $fila): bool
    {
        foreach ($fila as $valor) {
            if ($valor !== null && trim((string) $valor) !== '') {
                return false;
            }
        }

        return true;
    }

    private function idValido(mixed $valor, string $hoja, int $fila, string $columna): int
    {
        $texto = trim((string) $valor);

        if ($texto === '' || !ctype_digit($texto) || (int) $texto <= 0) {
            throw new RespaldoAcademicoImportException(
                "Hoja «{$hoja}», fila {$fila}: la columna {$columna} debe contener un ID entero positivo."
            );
        }

        return (int) $texto;
    }

    private function normalizarValorImportado(mixed $valor): mixed
    {
        if ($valor === null) {
            return null;
        }

        if (is_string($valor)) {
            return $valor === '' ? null : $valor;
        }

        if (is_bool($valor)) {
            return $valor ? 1 : 0;
        }

        return $valor;
    }


    /**
     * @param array<int,array{fila_excel:int,id:int,datos:array<string,mixed>,diferidos:array<string,mixed>}> $filas
     * @return array{creados:int,actualizados:int,sin_cambios:int,total:int,cambios:array<int,array<string,mixed>>}
     */
    private function compararTabla(string $tabla, array $filas): array
    {
        $resumen = [
            'creados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'total' => count($filas),
            'cambios' => [],
        ];

        foreach ($filas as $fila) {
            $actual = DB::table($tabla)->where('id', $fila['id'])->first();
            $datos = [...$fila['datos'], ...$fila['diferidos']];

            if (! $actual) {
                $resumen['creados']++;
                if (count($resumen['cambios']) < 50) {
                    $resumen['cambios'][] = [
                        'id' => $fila['id'],
                        'accion' => 'crear',
                        'columnas' => array_keys($datos),
                    ];
                }
                continue;
            }

            if ($this->datosIguales((array) $actual, $datos)) {
                $resumen['sin_cambios']++;
                continue;
            }

            $resumen['actualizados']++;
            if (count($resumen['cambios']) < 50) {
                $columnas = [];
                foreach ($datos as $columna => $valor) {
                    if ($this->valorComparable(((array) $actual)[$columna] ?? null) !== $this->valorComparable($valor)) {
                        $columnas[] = $columna;
                    }
                }

                $resumen['cambios'][] = [
                    'id' => $fila['id'],
                    'accion' => 'actualizar',
                    'columnas' => $columnas,
                ];
            }
        }

        return $resumen;
    }

    /**
     * @param array<int,array{fila_excel:int,id:int,datos:array<string,mixed>,diferidos:array<string,mixed>}> $filas
     * @param array<int,string> $columnasDiferidas
     * @return array{creados:int,actualizados:int,sin_cambios:int,total:int}
     */
    private function guardarTabla(string $tabla, array $filas, array $columnasDiferidas): array
    {
        $resumen = [
            'creados' => 0,
            'actualizados' => 0,
            'sin_cambios' => 0,
            'total' => count($filas),
        ];

        foreach ($filas as $fila) {
            try {
                $actual = DB::table($tabla)->where('id', $fila['id'])->first();
                $todosLosDatos = [...$fila['datos'], ...$fila['diferidos']];

                if ($actual) {
                    if ($this->datosIguales((array) $actual, $todosLosDatos)) {
                        $resumen['sin_cambios']++;
                    } else {
                        if ($fila['datos'] !== []) {
                            DB::table($tabla)
                                ->where('id', $fila['id'])
                                ->update($fila['datos']);
                        }

                        $resumen['actualizados']++;
                    }
                } else {
                    DB::table($tabla)->insert([
                        'id' => $fila['id'],
                        ...$fila['datos'],
                    ]);

                    $resumen['creados']++;
                }
            } catch (QueryException $e) {
                throw $this->errorConsulta($tabla, $fila['id'], $fila['fila_excel'], $e);
            }
        }

        if ($columnasDiferidas !== []) {
            foreach ($filas as $fila) {
                if ($fila['diferidos'] === []) {
                    continue;
                }

                try {
                    DB::table($tabla)
                        ->where('id', $fila['id'])
                        ->update($fila['diferidos']);
                } catch (QueryException $e) {
                    throw $this->errorConsulta($tabla, $fila['id'], $fila['fila_excel'], $e);
                }
            }
        }

        return $resumen;
    }

    /**
     * Compara únicamente las columnas que van a escribirse.
     *
     * @param array<string,mixed> $actual
     * @param array<string,mixed> $nuevo
     */
    private function datosIguales(array $actual, array $nuevo): bool
    {
        foreach ($nuevo as $columna => $valorNuevo) {
            $valorActual = $actual[$columna] ?? null;

            if ($this->valorComparable($valorActual) !== $this->valorComparable($valorNuevo)) {
                return false;
            }
        }

        return true;
    }

    private function valorComparable(mixed $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }

        if (is_bool($valor)) {
            return $valor ? '1' : '0';
        }

        return (string) $valor;
    }

    private function errorConsulta(string $tabla, int $id, int $filaExcel, QueryException $e): RespaldoAcademicoImportException
    {
        $detalle = trim((string) ($e->errorInfo[2] ?? $e->getMessage()));

        return new RespaldoAcademicoImportException(
            "No se pudo importar la tabla {$tabla}, ID {$id}, fila {$filaExcel}. "
            . "Verifica IDs relacionados, valores únicos y campos obligatorios. Detalle: {$detalle}",
            previous: $e,
        );
    }

    private function sincronizarAutoincremento(string $tabla): void
    {
        try {
            $maximo = (int) DB::table($tabla)->max('id');
            $siguiente = max(1, $maximo + 1);
            $driver = DB::getDriverName();

            if ($driver === 'mysql') {
                DB::statement("ALTER TABLE `{$tabla}` AUTO_INCREMENT = {$siguiente}");
            } elseif ($driver === 'pgsql') {
                DB::statement(
                    "SELECT setval(pg_get_serial_sequence('{$tabla}', 'id'), {$maximo}, true)"
                );
            }
        } catch (Throwable $e) {
            // No invalida la importación: los registros ya quedaron correctamente guardados.
            Log::warning('No fue posible sincronizar el autoincremento del respaldo académico.', [
                'tabla' => $tabla,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
