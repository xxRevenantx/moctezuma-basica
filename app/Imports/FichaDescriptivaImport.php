<?php

namespace App\Imports;

use App\Models\FichaDescriptiva;
use App\Models\Inscripcion;
use App\Models\Periodos;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class FichaDescriptivaImport implements ToCollection, WithHeadingRow
{
    private ?int $periodoOficialId = null;
    public function __construct(
        private readonly int $nivelId,
        private readonly int $gradoId,
        private readonly ?int $grupoId,
        private readonly ?int $generacionId,
        private readonly int $cicloEscolarId,
        private readonly int $periodo
    ) {
        $this->periodoOficialId = Periodos::query()
            ->where('ciclo_escolar_id', $this->cicloEscolarId)
            ->where('nivel_id', $this->nivelId)
            ->whereHas('periodoBasica', fn ($query) => $query->where('periodo', $this->periodo))
            ->value('id');
    }

    public function collection(Collection $rows): void
    {
        if (!$this->periodoOficialId) {
            throw ValidationException::withMessages([
                'archivo_fichas' => 'No existe un periodo oficial compatible con el ciclo y nivel seleccionados.',
            ]);
        }

        $errores = [];

        foreach ($rows as $index => $row) {
            $filaExcel = $index + 2;

            $inscripcionId = (int) ($row['id_inscripcion'] ?? 0);

            if ($inscripcionId <= 0) {
                $errores[] = "Fila {$filaExcel}: falta ID_INSCRIPCION.";
                continue;
            }

            $alumno = Inscripcion::query()
                ->where('id', $inscripcionId)
                ->where('ciclo_escolar_id', $this->cicloEscolarId)
                ->where('nivel_id', $this->nivelId)
                ->where('grado_id', $this->gradoId)
                ->when($this->grupoId, fn($q) => $q->where('grupo_id', $this->grupoId))
                ->when($this->generacionId, fn($q) => $q->where('generacion_id', $this->generacionId))
                ->where('activo', true)
                ->first();

            if (!$alumno) {
                $errores[] = "Fila {$filaExcel}: el alumno no pertenece al nivel, grado, grupo o generación seleccionada.";
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | IMPORTANTE
            |--------------------------------------------------------------------------
            | Estos nombres de campo DEBEN coincidir con FichaController::CAMPOS:
            |
            | lenguajes
            | saberes
            | etica
            | humano
            | recomendaciones
            |
            | Por eso aunque el Excel tenga encabezados largos, aquí se guardan
            | con las llaves reales que usa el sistema.
            */

            $campos = [
                'lenguajes' => $this->obtenerValor($row, [
                    'campo_lenguajes',
                    'lenguajes',
                ]),

                'saberes' => $this->obtenerValor($row, [
                    'campo_saberes',
                    'saberes',
                    'campo_saberes_y_pensamiento_cientifico',
                    'saberes_y_pensamiento_cientifico',
                    'campo_saberes_y_pensamiento_cien_tifico',
                ]),

                'etica' => $this->obtenerValor($row, [
                    'campo_etica',
                    'etica',
                    'campo_etica_naturaleza_y_sociedades',
                    'etica_naturaleza_y_sociedades',
                    'campo_etica_naturaleza_y_socie_dades',
                ]),

                'humano' => $this->obtenerValor($row, [
                    'campo_humano',
                    'humano',
                    'campo_de_lo_humano_y_lo_comunitario',
                    'de_lo_humano_y_lo_comunitario',
                    'campo_de_lo_humano_y_lo_comu_nitario',
                ]),

                'recomendaciones' => $this->obtenerValor($row, [
                    'recomendaciones',
                    'recomendacione_s',
                ]),
            ];

            foreach ($campos as $campo => $descripcion) {
                $descripcion = $this->limpiarDescripcion($descripcion);

                if ($descripcion === '') {
                    continue;
                }

                FichaDescriptiva::query()->updateOrCreate(
                    [
                        'inscripcion_id' => $alumno->id,
                        'ciclo_escolar_id' => $this->cicloEscolarId,
                        'periodo' => $this->periodo,
                        'periodo_id' => $this->periodoOficialId,
                        'campo' => $campo,
                    ],
                    [
                        'nivel_id' => $alumno->nivel_id,
                        'grado_id' => $alumno->grado_id,
                        'grupo_id' => $alumno->grupo_id,
                        'generacion_id' => $alumno->generacion_id,
                        'descripcion' => $descripcion,
                        'capturado_por' => Auth::id(),
                        'fecha_captura' => now(),
                    ]
                );
            }
        }

        if (count($errores) > 0) {
            throw ValidationException::withMessages([
                'archivo_fichas' => implode("\n", array_slice($errores, 0, 20)),
            ]);
        }
    }

    private function obtenerValor(Collection $row, array $posiblesColumnas): string
    {
        foreach ($posiblesColumnas as $columna) {
            $valor = $row[$columna] ?? null;

            if ($valor !== null && trim((string) $valor) !== '') {
                return (string) $valor;
            }
        }

        return '';
    }

    private function limpiarDescripcion(mixed $descripcion): string
    {
        $descripcion = trim((string) $descripcion);

        if ($descripcion === '') {
            return '';
        }

        return trim(strip_tags(
            $descripcion,
            '<p><br><strong><b><em><i><u><s><strike><span><ul><ol><li><table><thead><tbody><tr><th><td><h1><h2><h3><h4><h5><h6><blockquote>'
        ));
    }
}
