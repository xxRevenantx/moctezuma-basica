<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class CredencialCopiasService
{
    public function maximoPorAlumno(): int
    {
        return max(1, (int) config('credenciales.max_copias_por_alumno', 10));
    }

    /**
     * @return array<int, int> Mapa [inscripcion_id => cantidad_de_copias]
     */
    public function resolver(Collection $alumnos, Request $request): array
    {
        $global = $this->normalizarCantidad($request->query('copias_por_alumno', 1));
        $individuales = $this->parsearIndividuales(
            (string) $request->query('copias_individuales', '')
        );

        return $alumnos
            ->mapWithKeys(function ($alumno) use ($global, $individuales): array {
                $id = (int) $alumno->id;

                return [$id => $individuales[$id] ?? $global];
            })
            ->all();
    }

    public function total(Collection $alumnos, Request $request): int
    {
        return array_sum($this->resolver($alumnos, $request));
    }

    public function expandir(Collection $alumnos, Request $request): Collection
    {
        $copias = $this->resolver($alumnos, $request);

        return $alumnos
            ->flatMap(function ($alumno) use ($copias): array {
                $cantidad = $copias[(int) $alumno->id] ?? 1;

                return array_fill(0, $cantidad, $alumno);
            })
            ->values();
    }

    private function normalizarCantidad(mixed $valor): int
    {
        $texto = trim((string) $valor);

        abort_unless(
            $texto !== '' && preg_match('/^\d+$/', $texto) === 1,
            422,
            'La cantidad de copias por alumno debe ser un número entero.'
        );

        $cantidad = (int) $texto;
        $maximo = $this->maximoPorAlumno();

        abort_unless(
            $cantidad >= 1 && $cantidad <= $maximo,
            422,
            "La cantidad de copias por alumno debe estar entre 1 y {$maximo}."
        );

        return $cantidad;
    }

    /**
     * Formato esperado: "123:2,456:4".
     *
     * @return array<int, int>
     */
    private function parsearIndividuales(string $valor): array
    {
        $valor = trim($valor);

        if ($valor === '') {
            return [];
        }

        $resultado = [];

        foreach (explode(',', $valor) as $segmento) {
            $segmento = trim($segmento);

            if ($segmento === '') {
                continue;
            }

            abort_unless(
                preg_match('/^(\d+):(\d+)$/', $segmento, $coincidencias) === 1,
                422,
                'La configuración de copias individuales no es válida.'
            );

            $alumnoId = (int) $coincidencias[1];
            $cantidad = $this->normalizarCantidad($coincidencias[2]);

            if ($alumnoId > 0) {
                $resultado[$alumnoId] = $cantidad;
            }
        }

        return $resultado;
    }
}
