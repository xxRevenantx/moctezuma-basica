<?php

namespace App\Imports;

use App\Models\CampoFormativo;
use App\Models\Grado;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Support\CampoFormativoClassifier;
use App\Support\ReglasMateriaBachillerato;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MateriasImport implements ToCollection, WithHeadingRow
{
    public int $importadas = 0;

    public int $actualizadas = 0;

    public array $errores = [];

    public function collection(Collection $rows)
    {
        foreach ($rows as $indice => $row) {
            $filaExcel = $indice + 2;

            $datos = [
                'nivel_id' => $row['nivel_id'] ?? null,
                'grado_id' => $row['grado_id'] ?? null,
                'semestre_id' => $row['semestre_id'] ?? null,
                'campo_formativo_id' => $row['campo_formativo_id'] ?? null,
                'materia' => trim((string) ($row['materia'] ?? '')),
                'clave' => trim((string) ($row['clave'] ?? '')),
                'creditos_certificados' => filled($row['creditos_certificados'] ?? null)
                    ? (float) $row['creditos_certificados']
                    : null,
                'slug' => trim((string) ($row['slug'] ?? '')),
                'calificable' => $this->convertirBooleano($row['calificable'] ?? 0),
                'extra' => $this->convertirBooleano($row['extra'] ?? 0),
                'receso' => $this->convertirBooleano($row['receso'] ?? 0),
                'participa_en_calificacion_oficial' => $this->convertirBooleano($row['participa_en_calificacion_oficial'] ?? 1),
            ];

            if ($datos['slug'] === '' && $datos['materia'] !== '') {
                $datos['slug'] = Str::slug($datos['materia']);
            }

            $validacion = Validator::make($datos, [
                'nivel_id' => [
                    'required',
                    'integer',
                    Rule::exists('niveles', 'id'),
                ],
                'grado_id' => [
                    'required',
                    'integer',
                    Rule::exists('grados', 'id'),
                ],
                'semestre_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('semestres', 'id'),
                ],
                'campo_formativo_id' => [
                    'nullable',
                    'integer',
                    Rule::exists('campos_formativos', 'id'),
                ],
                'materia' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'clave' => [
                    'nullable',
                    'string',
                    'max:50',
                ],
                'creditos_certificados' => [
                    'nullable',
                    'numeric',
                    'gt:0',
                    'max:9999.99',
                ],
                'slug' => [
                    'required',
                    'string',
                    'max:255',
                ],
                'calificable' => [
                    'required',
                    'boolean',
                ],
                'extra' => [
                    'required',
                    'boolean',
                ],
                'receso' => [
                    'required',
                    'boolean',
                ],
                'participa_en_calificacion_oficial' => [
                    'required',
                    'boolean',
                ],
            ], [
                'nivel_id.required' => 'El nivel es obligatorio.',
                'nivel_id.exists' => 'El nivel no existe.',
                'grado_id.required' => 'El grado es obligatorio.',
                'grado_id.exists' => 'El grado no existe.',
                'semestre_id.exists' => 'El semestre no existe.',
                'materia.required' => 'La materia es obligatoria.',
                'slug.required' => 'El slug es obligatorio.',
            ]);

            if ($validacion->fails()) {
                $this->errores[] = [
                    'fila' => $filaExcel,
                    'errores' => $validacion->errors()->all(),
                ];

                continue;
            }

            $nivel = Nivel::find($datos['nivel_id']);
            $grado = Grado::find($datos['grado_id']);

            if (!$nivel || !$grado) {
                $this->errores[] = [
                    'fila' => $filaExcel,
                    'errores' => ['No se encontró el nivel o grado indicado.'],
                ];

                continue;
            }

            if ((int) $datos['nivel_id'] === 4 && blank($datos['semestre_id'])) {
                $this->errores[] = [
                    'fila' => $filaExcel,
                    'errores' => ['Para bachillerato es obligatorio indicar semestre_id.'],
                ];

                continue;
            }

            if ((int) $datos['nivel_id'] !== 4) {
                $datos['semestre_id'] = null;
            }

            if (ReglasMateriaBachillerato::esBachillerato($datos['nivel_id'])) {
                $datos = ReglasMateriaBachillerato::normalizarAtributos($datos);

                if (ReglasMateriaBachillerato::esPromediable($datos) && ! is_numeric($datos['creditos_certificados'])) {
                    $this->errores[] = [
                        'fila' => $filaExcel,
                        'errores' => ['Las materias oficiales de bachillerato requieren creditos_certificados.'],
                    ];

                    continue;
                }
            } else {
                if ((bool) $datos['receso']) {
                    $datos['calificable'] = false;
                    $datos['extra'] = false;
                    $datos['participa_en_calificacion_oficial'] = false;
                }

                if (! (bool) $datos['calificable'] || (bool) $datos['extra']) {
                    $datos['participa_en_calificacion_oficial'] = false;
                }

                $datos['creditos_certificados'] = null;
            }

            if (blank($datos['campo_formativo_id'])) {
                $datos['campo_formativo_id'] = CampoFormativo::query()
                    ->where('slug', $this->sugerirCampoSlug($datos['materia']))
                    ->value('id');
            }

            $materia = Materia::updateOrCreate(
                [
                    'nivel_id' => $datos['nivel_id'],
                    'grado_id' => $datos['grado_id'],
                    'semestre_id' => $datos['semestre_id'],
                    'slug' => $datos['slug'],
                ],
                [
                    'campo_formativo_id' => $datos['campo_formativo_id'],
                    'materia' => $datos['materia'],
                    'clave' => $datos['clave'] !== '' ? Str::upper($datos['clave']) : null,
                    'creditos_certificados' => $datos['creditos_certificados'],
                    'calificable' => $datos['calificable'],
                    'extra' => $datos['extra'],
                    'receso' => $datos['receso'],
                    'participa_en_calificacion_oficial' => $datos['participa_en_calificacion_oficial'],
                ]
            );

            if ($materia->wasRecentlyCreated) {
                $this->importadas++;
            } else {
                $this->actualizadas++;
            }
        }
    }

    private function sugerirCampoSlug(string $nombre): string
    {
        return CampoFormativoClassifier::sugerir($nombre);
    }

    private function convertirBooleano($valor): bool
    {
        if (is_bool($valor)) {
            return $valor;
        }

        $valor = Str::lower(trim((string) $valor));

        return in_array($valor, ['1', 'si', 'sí', 'true', 'verdadero', 'x'], true);
    }
}
