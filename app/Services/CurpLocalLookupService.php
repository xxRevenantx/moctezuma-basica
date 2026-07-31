<?php

namespace App\Services;

use App\Models\Inscripcion;

class CurpLocalLookupService
{
    /**
     * Expresión oficial práctica para una CURP mexicana de 18 caracteres.
     * La validación de fecha se realiza por separado para evitar aceptar
     * combinaciones como 31 de febrero.
     */
    private const CURP_REGEX = '/^[A-Z][AEIOUX][A-Z]{2}\d{2}(?:0[1-9]|1[0-2])(?:0[1-9]|[12]\d|3[01])[HM](?:AS|BC|BS|CC|CL|CM|CS|CH|DF|DG|GT|GR|HG|JC|MC|MN|MS|NT|NL|OC|PL|QT|QR|SP|SL|SR|TC|TS|TL|VZ|YN|ZS|NE)[B-DF-HJ-NP-TV-Z]{3}[A-Z\d]\d$/';

    public function normalizar(?string $curp): string
    {
        $curp = mb_strtoupper(trim((string) $curp));

        return preg_replace('/[^A-Z0-9]/', '', $curp) ?? '';
    }

    /**
     * @return array{valida: bool, estado: string, mensaje: string}
     */
    public function validarFormato(?string $curp): array
    {
        $curp = $this->normalizar($curp);

        if ($curp === '') {
            return [
                'valida' => false,
                'estado' => 'inicial',
                'mensaje' => 'Escribe una CURP para comprobar si ya está registrada.',
            ];
        }

        if (mb_strlen($curp) < 18) {
            return [
                'valida' => false,
                'estado' => 'incompleta',
                'mensaje' => 'Completa los 18 caracteres de la CURP.',
            ];
        }

        if (mb_strlen($curp) > 18 || ! preg_match(self::CURP_REGEX, $curp)) {
            return [
                'valida' => false,
                'estado' => 'invalida',
                'mensaje' => 'El formato de la CURP no es válido.',
            ];
        }

        if (! $this->fechaInternaEsValida($curp)) {
            return [
                'valida' => false,
                'estado' => 'invalida',
                'mensaje' => 'La fecha incluida en la CURP no es válida.',
            ];
        }

        return [
            'valida' => true,
            'estado' => 'valida',
            'mensaje' => 'Formato de CURP válido.',
        ];
    }

    public function existe(string $curp): bool
    {
        return Inscripcion::withTrashed()
            ->where('curp', $this->normalizar($curp))
            ->exists();
    }

    /**
     * Devuelve solamente los datos necesarios para presentar el resultado
     * local en el formulario. No expone información del tutor.
     *
     * @return array<string, mixed>|null
     */
    public function buscar(string $curp): ?array
    {
        $curp = $this->normalizar($curp);

        $inscripcion = Inscripcion::withTrashed()
            ->with([
                'nivel:id,nombre,slug',
                'grado:id,nombre',
                'semestre:id,numero',
                'generacion:id,nombre,anio_ingreso,anio_egreso',
                'grupo:id,asignacion_grupo_id,clave',
                'grupo.asignacionGrupo:id,nombre',
                'cicloEscolar:id,inicio_anio,fin_anio,es_actual,cerrado_at',
            ])
            ->where('curp', $curp)
            ->orderByRaw('deleted_at IS NULL DESC')
            ->orderByDesc('updated_at')
            ->first();

        if (! $inscripcion) {
            return null;
        }

        $estatus = $inscripcion->estatusNormalizado();
        $slugNivel = $inscripcion->nivel?->slug;
        $grupo = $inscripcion->grupo?->asignacionGrupo?->nombre
            ?: $inscripcion->grupo?->clave;
        $generacion = $inscripcion->generacion?->etiqueta;
        $ciclo = $inscripcion->cicloEscolar
            ? $inscripcion->cicloEscolar->inicio_anio . '-' . $inscripcion->cicloEscolar->fin_anio
            : null;

        $ubicacion = $inscripcion->semestre
            ? $inscripcion->semestre->numero . '° semestre'
            : $inscripcion->grado?->nombre;

        $accion = match (true) {
            $inscripcion->trashed() => 'registro_historico',
            in_array($estatus, ['baja_temporal', 'baja_definitiva', 'traslado', 'trasladado', 'inactivo', 'suspendido'], true) => 'reingreso',
            $estatus === 'egresado' => 'egresado',
            $estatus === 'preinscrito' => 'preinscrito',
            default => 'editar_matricula',
        };

        $usuario = auth()->user();
        $puedeEditar = $usuario?->canAccess('alumnos.editar') ?? false;
        $puedeVerExpediente = ($usuario?->is_admin ?? false)
            || ($usuario?->canAccess('documentos.organizar') ?? false);
        $puedeReingresar = ($usuario?->is_admin ?? false)
            || ($usuario?->rol_sistema === 'administrador');

        return [
            'id' => (int) $inscripcion->id,
            'nombre_completo' => trim(implode(' ', array_filter([
                $inscripcion->nombre,
                $inscripcion->apellido_paterno,
                $inscripcion->apellido_materno,
            ]))),
            'iniciales' => $inscripcion->iniciales,
            'curp' => $inscripcion->curp,
            'matricula' => $inscripcion->matricula,
            'estatus' => $estatus,
            'estatus_etiqueta' => $inscripcion->etiqueta_estatus,
            'activo' => (bool) $inscripcion->activo,
            'eliminado' => $inscripcion->trashed(),
            'nivel' => $inscripcion->nivel?->nombre,
            'nivel_slug' => $slugNivel,
            'ubicacion' => $ubicacion,
            'generacion' => $generacion,
            'grupo' => $grupo,
            'ciclo' => $ciclo,
            'ultimo_movimiento' => optional($inscripcion->fecha_estatus ?: $inscripcion->updated_at)?->format('d/m/Y H:i'),
            'foto_url' => $inscripcion->foto_url,
            'accion_recomendada' => $accion,
            'mensaje' => $this->mensajeSegunEstado($estatus, $inscripcion->trashed()),
            'url_editar' => $puedeEditar && $slugNivel && ! $inscripcion->trashed()
                ? route('misrutas.matricula.editar', [
                    'slug_nivel' => $slugNivel,
                    'inscripcion' => $inscripcion->id,
                ])
                : null,
            'url_reingreso' => $puedeReingresar && $slugNivel
                ? route('misrutas.reingreso-alumno', ['slug_nivel' => $slugNivel])
                : null,
            'url_expediente' => $puedeVerExpediente
                ? route('misrutas.expedientes.show', ['inscripcion' => $inscripcion->id])
                : null,
        ];
    }

    private function fechaInternaEsValida(string $curp): bool
    {
        $anio = (int) mb_substr($curp, 4, 2);
        $mes = (int) mb_substr($curp, 6, 2);
        $dia = (int) mb_substr($curp, 8, 2);
        $homoclave = mb_substr($curp, 16, 1);
        $siglo = ctype_digit($homoclave) ? 1900 : 2000;

        return checkdate($mes, $dia, $siglo + $anio);
    }

    private function mensajeSegunEstado(string $estatus, bool $eliminado): string
    {
        if ($eliminado) {
            return 'La CURP pertenece a un registro histórico eliminado. Revisa el expediente antes de continuar.';
        }

        return match ($estatus) {
            'activo', 'reingreso', 'no_promovido' => 'El alumno ya tiene una matrícula vigente. No se puede crear una inscripción duplicada.',
            'preinscrito' => 'El alumno ya está preinscrito. Actívalo o edita su matrícula actual en lugar de registrarlo otra vez.',
            'baja_temporal', 'baja_definitiva', 'traslado', 'trasladado', 'inactivo', 'suspendido' => 'El alumno tiene historial previo. Utiliza el flujo de reingreso para conservarlo.',
            'egresado' => 'El alumno figura como egresado. Revisa su expediente antes de iniciar una nueva trayectoria.',
            'no_reinscrito', 'pendiente_reinscripcion' => 'El alumno ya existe y tiene continuidad pendiente. Utiliza el flujo de continuidad o reingreso.',
            default => 'La CURP ya pertenece a un alumno registrado. Revisa su matrícula antes de continuar.',
        };
    }
}
