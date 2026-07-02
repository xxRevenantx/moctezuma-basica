<?php

namespace App\Imports\Inscripciones;

use App\Models\Grupo;
use App\Models\Inscripcion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class InscripcionesImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    use Importable;

    public int $creados = 0;
    public int $actualizados = 0;

    public function collection(Collection $rows): void
    {
        DB::transaction(function () use ($rows) {
            foreach ($rows as $row) {
                $grupo = Grupo::query()
                    ->with(['nivel', 'grado', 'generacion', 'semestre'])
                    ->findOrFail((int) $row['grupo_id']);

                $matricula = mb_strtoupper(trim((string) $row['matricula']));

                $datosInscripcion = [
                    'curp' => mb_strtoupper(trim((string) $row['curp'])),
                    'matricula' => $matricula,
                    'folio' => $this->limpiarTexto($row['folio'] ?? null),

                    'nombre' => $this->limpiarTextoMayuscula($row['nombre'] ?? null),
                    'apellido_paterno' => $this->limpiarTextoMayuscula($row['apellido_paterno'] ?? null),
                    'apellido_materno' => $this->limpiarTextoMayuscula($row['apellido_materno'] ?? null),
                    'fecha_nacimiento' => $this->normalizarFecha($row['fecha_nacimiento'] ?? null),
                    'genero' => mb_strtoupper(trim((string) $row['genero'])),

                    'fecha_inscripcion' => $this->normalizarFecha($row['fecha_inscripcion'] ?? now()->toDateString()),

                    'nivel_id' => (int) $row['nivel_id'],
                    'grado_id' => (int) $row['grado_id'],
                    'generacion_id' => (int) $row['generacion_id'],
                    'grupo_id' => (int) $row['grupo_id'],
                    'semestre_id' => !empty($row['semestre_id']) ? (int) $row['semestre_id'] : null,
                    'ciclo_id' => (int) $row['ciclo_id'],

                    'activo' => true,
                    'estatus' => 'activo',
                    'fecha_estatus' => $this->normalizarFecha($row['fecha_inscripcion'] ?? now()->toDateString()),

                    'pais_nacimiento' => null,
                    'estado_nacimiento' => null,
                    'lugar_nacimiento' => null,

                    'calle' => null,
                    'numero_exterior' => null,
                    'numero_interior' => null,
                    'colonia' => null,
                    'codigo_postal' => null,
                    'municipio' => null,
                    'estado_residencia' => null,
                    'ciudad_residencia' => null,

                    'foto_path' => null,
                    'tutor_id' => null,
                    'fecha_baja' => null,
                    'motivo_baja' => null,
                    'observaciones_baja' => null,
                ];

                $inscripcion = Inscripcion::withTrashed()
                    ->where(function ($query) use ($matricula, $datosInscripcion) {
                        $query->where('matricula', $matricula)
                            ->orWhere('curp', $datosInscripcion['curp']);
                    })
                    ->first();

                if ($inscripcion) {
                    $inscripcion->restore();
                    $inscripcion->update($datosInscripcion);
                    $this->actualizados++;
                } else {
                    Inscripcion::query()->create($datosInscripcion);
                    $this->creados++;
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.curp' => [
                'required',
                'string',
                'max:18',
                'regex:/^[A-Z0-9]+$/i',
            ],
            '*.matricula' => [
                'required',
                'string',
                'max:50',
                'regex:/^[A-Z0-9\-]+$/i',
            ],
            '*.folio' => [
                'nullable',
                'string',
                'max:50',
            ],
            '*.nombre' => [
                'required',
                'string',
                'max:255',
            ],
            '*.apellido_paterno' => [
                'required',
                'string',
                'max:255',
            ],
            '*.apellido_materno' => [
                'nullable',
                'string',
                'max:255',
            ],
            '*.fecha_nacimiento' => [
                'required',
                'date',
            ],
            '*.genero' => [
                'required',
                Rule::in(['H', 'M', 'h', 'm']),
            ],
            '*.fecha_inscripcion' => [
                'required',
                'date',
            ],
            '*.ciclo_escolar_id' => [
                'required',
                'integer',
                Rule::exists('ciclo_escolares', 'id'),
            ],
            '*.nivel_id' => [
                'required',
                'integer',
                Rule::exists('niveles', 'id'),
            ],
            '*.grado_id' => [
                'required',
                'integer',
                Rule::exists('grados', 'id'),
            ],
            '*.generacion_id' => [
                'required',
                'integer',
                Rule::exists('generaciones', 'id'),
            ],
            '*.grupo_id' => [
                'required',
                'integer',
                Rule::exists('grupos', 'id'),
            ],
            '*.semestre_id' => [
                'nullable',
                'integer',
                Rule::exists('semestres', 'id'),
            ],
            '*.ciclo_id' => [
                'required',
                'integer',
                Rule::exists('ciclos', 'id'),
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $filas = $validator->getData();

            foreach ($filas as $indice => $fila) {
                $this->validarRelacionGrupo($validator, $indice, $fila);
                $this->validarCurpUnicaPorMatricula($validator, $indice, $fila);
            }
        });
    }

    private function validarRelacionGrupo(Validator $validator, int|string $indice, array $fila): void
    {
        if (empty($fila['grupo_id'])) {
            return;
        }

        $grupo = Grupo::query()->find((int) $fila['grupo_id']);

        if (!$grupo) {
            return;
        }

        $nivelId = (int) ($fila['nivel_id'] ?? 0);
        $gradoId = (int) ($fila['grado_id'] ?? 0);
        $generacionId = (int) ($fila['generacion_id'] ?? 0);
        $semestreId = !empty($fila['semestre_id']) ? (int) $fila['semestre_id'] : null;

        if ((int) $grupo->nivel_id !== $nivelId) {
            $validator->errors()->add(
                "{$indice}.nivel_id",
                'El nivel no coincide con el grupo seleccionado.'
            );
        }

        if ((int) $grupo->grado_id !== $gradoId) {
            $validator->errors()->add(
                "{$indice}.grado_id",
                'El grado no coincide con el grupo seleccionado.'
            );
        }

        if ((int) $grupo->generacion_id !== $generacionId) {
            $validator->errors()->add(
                "{$indice}.generacion_id",
                'La generación no coincide con el grupo seleccionado.'
            );
        }

        if ($semestreId !== null && (int) $grupo->semestre_id !== $semestreId) {
            $validator->errors()->add(
                "{$indice}.semestre_id",
                'El semestre no coincide con el grupo seleccionado.'
            );
        }

        if ($semestreId === null && $grupo->semestre_id !== null) {
            $validator->errors()->add(
                "{$indice}.semestre_id",
                'Este grupo pertenece a bachillerato y requiere semestre_id.'
            );
        }
    }

    private function validarCurpUnicaPorMatricula(Validator $validator, int|string $indice, array $fila): void
    {
        $matricula = mb_strtoupper(trim((string) ($fila['matricula'] ?? '')));
        $curp = mb_strtoupper(trim((string) ($fila['curp'] ?? '')));

        if ($matricula === '' || $curp === '') {
            return;
        }

        $curpUsada = Inscripcion::query()
            ->where('curp', $curp)
            ->where('matricula', '!=', $matricula)
            ->exists();

        if ($curpUsada) {
            $validator->errors()->add(
                "{$indice}.curp",
                'La CURP ya está registrada con otra matrícula.'
            );
        }
    }

    public function customValidationMessages(): array
    {
        return [
            '*.curp.required' => 'La CURP es obligatoria.',
            '*.curp.regex' => 'La CURP solo debe contener letras y números.',
            '*.curp.max' => 'La CURP no debe superar 18 caracteres.',

            '*.matricula.required' => 'La matrícula es obligatoria.',
            '*.matricula.regex' => 'La matrícula solo debe contener letras, números y guiones.',

            '*.nombre.required' => 'El nombre es obligatorio.',
            '*.apellido_paterno.required' => 'El apellido paterno es obligatorio.',

            '*.fecha_nacimiento.required' => 'La fecha de nacimiento es obligatoria.',
            '*.fecha_nacimiento.date' => 'La fecha de nacimiento debe tener formato válido.',

            '*.genero.required' => 'El género es obligatorio.',
            '*.genero.in' => 'El género debe ser H o M.',

            '*.fecha_inscripcion.required' => 'La fecha de inscripción es obligatoria.',
            '*.fecha_inscripcion.date' => 'La fecha de inscripción debe tener formato válido.',

            '*.ciclo_escolar_id.required' => 'El ciclo escolar es obligatorio.',
            '*.ciclo_escolar_id.exists' => 'El ciclo escolar seleccionado no existe.',

            '*.nivel_id.required' => 'El nivel es obligatorio.',
            '*.nivel_id.exists' => 'El nivel seleccionado no existe.',

            '*.grado_id.required' => 'El grado es obligatorio.',
            '*.grado_id.exists' => 'El grado seleccionado no existe.',

            '*.generacion_id.required' => 'La generación es obligatoria.',
            '*.generacion_id.exists' => 'La generación seleccionada no existe.',

            '*.grupo_id.required' => 'El grupo es obligatorio.',
            '*.grupo_id.exists' => 'El grupo seleccionado no existe.',

            '*.semestre_id.exists' => 'El semestre seleccionado no existe.',

            '*.ciclo_id.required' => 'El periodo de inscripción es obligatorio.',
            '*.ciclo_id.exists' => 'El periodo de inscripción seleccionado no existe.',
        ];
    }

    private function limpiarTexto($valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function limpiarTextoMayuscula($valor): ?string
    {
        $texto = $this->limpiarTexto($valor);

        return $texto ? mb_strtoupper($texto) : null;
    }

    private function normalizarFecha($valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        if (is_numeric($valor)) {
            return ExcelDate::excelToDateTimeObject($valor)->format('Y-m-d');
        }

        return date('Y-m-d', strtotime((string) $valor));
    }
}
