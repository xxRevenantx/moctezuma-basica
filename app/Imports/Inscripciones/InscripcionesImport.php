<?php

namespace App\Imports\Inscripciones;

use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Services\AsignacionEscolarService;
use App\Services\ObservacionInscripcionService;
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
        DB::transaction(function () use ($rows): void {
            $observacionesService = app(ObservacionInscripcionService::class);
            $asignacionService = app(AsignacionEscolarService::class);

            foreach ($rows as $row) {
                $claveGrupo = mb_strtoupper(trim((string) $row['clave_grupo']));

                $grupo = Grupo::query()
                    ->with(['nivel', 'grado', 'generacion', 'semestre', 'cicloEscolar'])
                    ->where('clave', $claveGrupo)
                    ->where('estado', 'activo')
                    ->firstOrFail();

                $asignacionService->validarAsignacion([
                    'grupo_id' => $grupo->id,
                    'ciclo_escolar_id' => $grupo->ciclo_escolar_id,
                    'nivel_id' => $grupo->nivel_id,
                    'grado_id' => $grupo->grado_id,
                    'generacion_id' => $grupo->generacion_id,
                    'semestre_id' => $grupo->semestre_id,
                ]);

                $matricula = mb_strtoupper(trim((string) $row['matricula']));
                $curp = mb_strtoupper(trim((string) $row['curp']));
                $fechaInscripcion = $this->normalizarFecha(
                    $row['fecha_inscripcion'] ?? now()->toDateString()
                );
                $estadoInscripcion = strtolower(trim((string) ($row['estado_inscripcion'] ?? 'inscrito')));
                $tipoIngreso = strtolower(trim((string) ($row['tipo_ingreso'] ?? 'nuevo_ingreso')));
                $motivoCapturaHistorica = $this->limpiarTexto($row['motivo_captura_historica'] ?? null);
                $estaInscrito = $estadoInscripcion === 'inscrito';

                $observacionImportada = $observacionesService->desdeTextoPlano(
                    $row['observaciones'] ?? null
                );

                $datosInscripcion = [
                    'curp' => $curp,
                    'matricula' => $matricula,
                    'folio' => $this->limpiarTexto($row['folio'] ?? null),

                    'nombre' => $this->limpiarTextoMayuscula($row['nombre'] ?? null),
                    'apellido_paterno' => $this->limpiarTextoMayuscula($row['apellido_paterno'] ?? null),
                    'apellido_materno' => $this->limpiarTextoMayuscula($row['apellido_materno'] ?? null),
                    'fecha_nacimiento' => $this->normalizarFecha($row['fecha_nacimiento'] ?? null),
                    'genero' => mb_strtoupper(trim((string) $row['genero'])),

                    'fecha_inscripcion' => $fechaInscripcion,
                    'ciclo_escolar_id' => (int) $grupo->ciclo_escolar_id,
                    'ciclo_id' => (int) $row['momento_ingreso_id'],

                    // Toda la ubicación académica se deriva de una única clave de grupo.
                    'nivel_id' => (int) $grupo->nivel_id,
                    'grado_id' => (int) $grupo->grado_id,
                    'generacion_id' => (int) $grupo->generacion_id,
                    'grupo_id' => (int) $grupo->id,
                    'semestre_id' => $grupo->semestre_id ? (int) $grupo->semestre_id : null,

                    'activo' => $estaInscrito,
                    'estatus' => $estaInscrito ? 'activo' : 'preinscrito',
                    'fecha_estatus' => $fechaInscripcion,
                    'motivo_estatus' => $tipoIngreso === 'captura_historica' ? $motivoCapturaHistorica : null,
                    'tipo_ultimo_ingreso' => $tipoIngreso,
                    'fecha_ultimo_ingreso' => $fechaInscripcion,
                    'usuario_acceso_activo' => $estaInscrito,
                ];

                $inscripcion = Inscripcion::withTrashed()
                    ->where(function ($query) use ($matricula, $curp): void {
                        $query->where('matricula', $matricula)
                            ->orWhere('curp', $curp);
                    })
                    ->first();

                if ($inscripcion) {
                    $inscripcion->restore();

                    $datosActualizacion = $datosInscripcion;
                    foreach (['folio', 'apellido_materno'] as $campoOpcional) {
                        if ($datosActualizacion[$campoOpcional] === null) {
                            unset($datosActualizacion[$campoOpcional]);
                        }
                    }

                    $inscripcion->update($datosActualizacion);
                    $this->actualizados++;
                } else {
                    $inscripcion = Inscripcion::query()->create($datosInscripcion);
                    $this->creados++;
                }

                // Una celda vacía no borra observaciones previamente registradas.
                if ($observacionImportada !== null) {
                    $observacionesService->guardar(
                        inscripcion: $inscripcion,
                        cicloEscolarId: (int) $grupo->ciclo_escolar_id,
                        contenido: $observacionImportada,
                        origen: 'importacion',
                        usuarioId: auth()->id(),
                    );
                }
            }
        });
    }

    public function rules(): array
    {
        return [
            '*.curp' => ['required', 'string', 'max:18', 'regex:/^[A-Z0-9]+$/i'],
            '*.matricula' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9\-]+$/i'],
            '*.folio' => ['nullable', 'string', 'max:50'],
            '*.nombre' => ['required', 'string', 'max:255'],
            '*.apellido_paterno' => ['required', 'string', 'max:255'],
            '*.apellido_materno' => ['nullable', 'string', 'max:255'],
            '*.fecha_nacimiento' => [
                'required',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarFechaImportada($attribute, $value, $fail),
            ],
            '*.genero' => ['required', Rule::in(['H', 'M', 'h', 'm'])],
            '*.fecha_inscripcion' => [
                'required',
                fn (string $attribute, mixed $value, \Closure $fail) => $this->validarFechaImportada($attribute, $value, $fail),
            ],
            '*.clave_grupo' => [
                'required',
                'string',
                'max:120',
                Rule::exists('grupos', 'clave')->where(fn ($query) => $query->where('estado', 'activo')),
            ],
            '*.momento_ingreso_id' => ['required', 'integer', Rule::exists('ciclos', 'id')],
            '*.tipo_ingreso' => [
                'required',
                Rule::in(['nuevo_ingreso', 'traslado', 'captura_historica']),
            ],
            '*.motivo_captura_historica' => ['nullable', 'string', 'max:500'],
            '*.estado_inscripcion' => [
                'required',
                Rule::in(['preinscrito', 'inscrito']),
            ],
            '*.observaciones' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (app(ObservacionInscripcionService::class)->excedeLimite($value)) {
                        $fail('Las observaciones no deben superar 5,000 caracteres.');
                    }
                },
            ],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            foreach ($validator->getData() as $indice => $fila) {
                $this->validarGrupoImportable($validator, $indice, $fila);
                $this->validarCurpUnicaPorMatricula($validator, $indice, $fila);
            }
        });
    }

    private function validarGrupoImportable(Validator $validator, int|string $indice, array $fila): void
    {
        $claveGrupo = mb_strtoupper(trim((string) ($fila['clave_grupo'] ?? '')));

        if ($claveGrupo === '') {
            return;
        }

        $grupo = Grupo::query()
            ->where('clave', $claveGrupo)
            ->first();

        if (!$grupo) {
            return;
        }

        if (!$grupo->ciclo_escolar_id) {
            $validator->errors()->add(
                "{$indice}.clave_grupo",
                'El grupo no tiene un ciclo escolar asignado. Corrígelo en Estructura → Grupos.'
            );
        }

        if ($grupo->estado !== 'activo') {
            $validator->errors()->add(
                "{$indice}.clave_grupo",
                'El grupo seleccionado está inactivo.'
            );
        }

        $tipoIngreso = strtolower(trim((string) ($fila['tipo_ingreso'] ?? 'nuevo_ingreso')));
        $motivoHistorico = trim((string) ($fila['motivo_captura_historica'] ?? ''));
        $grupo->loadMissing('cicloEscolar');

        if ($grupo->cicloEscolar?->cerrado_at && $tipoIngreso !== 'captura_historica') {
            $validator->errors()->add(
                "{$indice}.tipo_ingreso",
                'El ciclo escolar del grupo está cerrado. Usa captura_historica y registra el motivo.'
            );
        }

        if ($tipoIngreso === 'captura_historica') {
            if (mb_strlen($motivoHistorico) < 10) {
                $validator->errors()->add(
                    "{$indice}.motivo_captura_historica",
                    'La captura histórica requiere un motivo de al menos 10 caracteres.'
                );
            }

            if (! auth()->user()?->canAccess('academico.editar')) {
                $validator->errors()->add(
                    "{$indice}.tipo_ingreso",
                    'No tienes permiso para importar capturas históricas.'
                );
            }
        }

        $matricula = mb_strtoupper(trim((string) ($fila['matricula'] ?? '')));
        $curp = mb_strtoupper(trim((string) ($fila['curp'] ?? '')));

        if ($matricula === '' || $curp === '') {
            return;
        }

        $existente = Inscripcion::withTrashed()
            ->where(fn ($query) => $query
                ->where('matricula', $matricula)
                ->orWhere('curp', $curp))
            ->first();

        if ($existente && $existente->grupo_id && (int) $existente->grupo_id !== (int) $grupo->id) {
            $validator->errors()->add(
                "{$indice}.clave_grupo",
                'El alumno ya pertenece a otro grupo. Utiliza la acción “Cambiar asignación escolar” para conservar el historial.'
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

        $curpUsada = Inscripcion::withTrashed()
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
            '*.clave_grupo.required' => 'La clave del grupo es obligatoria.',
            '*.clave_grupo.exists' => 'La clave del grupo no existe o el grupo está inactivo.',
            '*.momento_ingreso_id.required' => 'El momento de ingreso es obligatorio.',
            '*.momento_ingreso_id.exists' => 'El momento de ingreso seleccionado no existe.',
            '*.tipo_ingreso.required' => 'El tipo de ingreso es obligatorio.',
            '*.tipo_ingreso.in' => 'El tipo de ingreso no es válido.',
            '*.motivo_captura_historica.max' => 'El motivo de captura histórica no debe superar 500 caracteres.',
            '*.estado_inscripcion.required' => 'El estado inicial es obligatorio.',
            '*.estado_inscripcion.in' => 'El estado inicial debe ser preinscrito o inscrito.',
            '*.observaciones.string' => 'Las observaciones deben ser texto.',
        ];
    }

    private function validarFechaImportada(string $attribute, mixed $value, \Closure $fail): void
    {
        if (is_numeric($value)) {
            try {
                ExcelDate::excelToDateTimeObject($value);
                return;
            } catch (\Throwable) {
                $fail('La fecha no es válida.');
                return;
            }
        }

        if (strtotime((string) $value) === false) {
            $fail('La fecha no es válida. Usa el formato yyyy-mm-dd.');
        }
    }

    private function limpiarTexto(mixed $valor): ?string
    {
        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function limpiarTextoMayuscula(mixed $valor): ?string
    {
        $texto = $this->limpiarTexto($valor);

        return $texto ? mb_strtoupper($texto) : null;
    }

    private function normalizarFecha(mixed $valor): ?string
    {
        if (empty($valor)) {
            return null;
        }

        if (is_numeric($valor)) {
            return ExcelDate::excelToDateTimeObject($valor)->format('Y-m-d');
        }

        $timestamp = strtotime((string) $valor);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }
}
