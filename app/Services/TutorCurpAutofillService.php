<?php

namespace App\Services;

use App\Models\Inscripcion;
use App\Models\Tutor;

class TutorCurpAutofillService
{
    public function __construct(
        private readonly CurpLocalLookupService $curpLocal,
    ) {
    }

    public function normalizar(?string $curp): string
    {
        return $this->curpLocal->normalizar($curp);
    }

    /**
     * @return array{
     *   curp:string,
     *   valida:bool,
     *   estado:string,
     *   mensaje:string,
     *   tutor_existente:?array<string,mixed>,
     *   alumno_existente:?array<string,mixed>
     * }
     */
    public function validarLocal(?string $curp): array
    {
        $curp = $this->normalizar($curp);
        $formato = $this->curpLocal->validarFormato($curp);

        if (! ($formato['valida'] ?? false)) {
            return [
                'curp' => $curp,
                'valida' => false,
                'estado' => (string) ($formato['estado'] ?? 'invalida'),
                'mensaje' => (string) ($formato['mensaje'] ?? 'El formato de la CURP no es válido.'),
                'tutor_existente' => null,
                'alumno_existente' => null,
            ];
        }

        $tutor = Tutor::query()
            ->withCount(['relacionesActivas'])
            ->where('curp', $curp)
            ->first();

        $alumno = Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre',
                'grado:id,nombre',
                'semestre:id,numero',
                'grupo:id,clave',
                'cicloEscolar:id,inicio_anio,fin_anio',
            ])
            ->where('curp', $curp)
            ->orderByRaw('CASE WHEN deleted_at IS NULL THEN 0 ELSE 1 END')
            ->orderByDesc('updated_at')
            ->first();

        return [
            'curp' => $curp,
            'valida' => true,
            'estado' => $tutor ? 'encontrada' : 'disponible',
            'mensaje' => $tutor
                ? 'La CURP ya pertenece a un responsable registrado.'
                : 'La CURP no existe entre los responsables. Puedes consultar sus datos externos.',
            'tutor_existente' => $tutor ? [
                'id' => (int) $tutor->id,
                'nombre_completo' => $tutor->nombre_completo,
                'curp' => $tutor->curp,
                'telefono' => $tutor->telefono_celular ?: $tutor->telefono_casa,
                'correo' => $tutor->correo_electronico,
                'activo' => (bool) $tutor->activo,
                'relaciones_activas' => (int) $tutor->relaciones_activas_count,
            ] : null,
            'alumno_existente' => $alumno ? [
                'id' => (int) $alumno->id,
                'nombre_completo' => trim(collect([
                    $alumno->nombre,
                    $alumno->apellido_paterno,
                    $alumno->apellido_materno,
                ])->filter()->join(' ')),
                'matricula' => $alumno->matricula,
                'estatus' => $alumno->etiqueta_estatus,
                'nivel' => $alumno->nivel?->nombre,
                'ubicacion' => $alumno->semestre
                    ? $alumno->semestre->numero . '° semestre'
                    : $alumno->grado?->nombre,
                'grupo' => $alumno->grupo?->clave,
                'ciclo' => $alumno->cicloEscolar
                    ? $alumno->cicloEscolar->inicio_anio . '-' . $alumno->cicloEscolar->fin_anio
                    : null,
                'eliminado' => $alumno->trashed(),
            ] : null,
        ];
    }

    /** @return array<string, string|null> */
    public function datosDesdePayload(array $payload): array
    {
        $datos = is_array($payload['datos'] ?? null) ? $payload['datos'] : [];
        $generoServicio = mb_strtoupper(trim((string) ($datos['genero'] ?? '')));
        $generoTutor = match ($generoServicio) {
            'H' => 'M',
            'M' => 'F',
            default => null,
        };

        return [
            'curp' => $this->normalizar((string) ($datos['curp'] ?? '')),
            'nombre' => $this->titleCase((string) ($datos['nombre'] ?? '')),
            'apellido_paterno' => $this->titleCase((string) ($datos['apellido_paterno'] ?? '')),
            'apellido_materno' => $this->titleCase((string) ($datos['apellido_materno'] ?? '')),
            'genero' => $generoTutor,
            'fecha_nacimiento' => filled($datos['fecha_nacimiento'] ?? null)
                ? (string) $datos['fecha_nacimiento']
                : null,
            'estado_nacimiento' => filled($datos['estado_nacimiento'] ?? null)
                ? trim((string) $datos['estado_nacimiento'])
                : null,
        ];
    }

    /**
     * @param array<string, mixed> $actuales
     * @param array<string, mixed> $externos
     * @return array{valores:array<string,mixed>,diferencias:array<int,array<string,mixed>>,aplicados:int,conservados:int}
     */
    public function aplicar(array $actuales, array $externos, bool $reemplazar = false): array
    {
        $valores = $actuales;
        $diferencias = [];
        $aplicados = 0;
        $conservados = 0;

        foreach (['nombre', 'apellido_paterno', 'apellido_materno', 'genero', 'fecha_nacimiento', 'estado_nacimiento'] as $campo) {
            $externo = $externos[$campo] ?? null;
            if (blank($externo)) {
                continue;
            }

            $actual = $actuales[$campo] ?? null;
            $sonDistintos = filled($actual)
                && mb_strtolower(trim((string) $actual)) !== mb_strtolower(trim((string) $externo));

            if ($sonDistintos) {
                $diferencias[] = [
                    'campo' => $campo,
                    'actual' => $actual,
                    'externo' => $externo,
                ];
            }

            if ($reemplazar || blank($actual)) {
                $valores[$campo] = $externo;
                $aplicados++;
            } else {
                $conservados++;
            }
        }

        return compact('valores', 'diferencias', 'aplicados', 'conservados');
    }

    private function titleCase(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));
        if ($value === '') {
            return '';
        }

        $value = mb_convert_case(mb_strtolower($value, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
        foreach ([' De ', ' Del ', ' La ', ' Las ', ' Los ', ' Y ', ' E '] as $particle) {
            $value = str_replace($particle, mb_strtolower($particle, 'UTF-8'), $value);
        }

        return $value;
    }
}
