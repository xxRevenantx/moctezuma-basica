<?php

namespace App\Services;

use App\Models\AsignacionMateria;
use App\Models\Calificacion;
use App\Models\CalificacionEntrega;
use App\Models\Inscripcion;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class CalificacionEntregaService
{
    public const DECLARATION = 'Declaro que revisé íntegramente las calificaciones capturadas y confirmo que corresponden a los alumnos, las asignaturas, el grupo y el periodo indicados. Manifiesto mi conformidad para que esta entrega quede registrada con mi identificador de usuario, nombre completo, CURP, fecha y hora como evidencia de responsabilidad académica.';

    public function __construct(private readonly TeacherAcademicScopeService $scope)
    {
    }

    /**
     * @param array{ciclo_escolar_id:int,nivel_id:int,generacion_id:int,grado_id:int,grupo_id:int,semestre_id:?int,periodo_id:int,assignment_ids:array<int,int>,student_ids:array<int,int>} $context
     */
    public function create(User $user, array $context): CalificacionEntrega
    {
        $user->loadMissing('persona');
        $personaId = $this->scope->personaIdOrFail($user);

        $delivery = DB::transaction(function () use ($user, $personaId, $context): CalificacionEntrega {
            $authorized = $this->scope->validateGradePayload(
                user: $user,
                cicloEscolarId: $context['ciclo_escolar_id'],
                nivelId: $context['nivel_id'],
                generacionId: $context['generacion_id'],
                gradoId: $context['grado_id'],
                grupoId: $context['grupo_id'],
                semestreId: $context['semestre_id'],
                periodoId: $context['periodo_id'],
                payloadAssignmentIds: $context['assignment_ids'],
                payloadStudentIds: $context['student_ids'],
                lock: true,
            );

            $existing = CalificacionEntrega::query()
                ->where('user_id', $user->id)
                ->where('periodo_id', $context['periodo_id'])
                ->where('grupo_id', $context['grupo_id'])
                ->where('estado', 'confirmada')
                ->lockForUpdate()
                ->exists();

            if ($existing) {
                throw ValidationException::withMessages([
                    'calificaciones' => 'Esta entrega ya fue confirmada. Solo administración puede reabrirla para una nueva versión.',
                ]);
            }

            $assignments = AsignacionMateria::query()
                ->with('materia:id,materia,clave')
                ->whereIn('id', $authorized['assignment_ids'])
                ->lockForUpdate()
                ->orderByRaw('CASE WHEN orden IS NULL THEN 1 ELSE 0 END')
                ->orderBy('orden')
                ->orderBy('id')
                ->get();

            $students = Inscripcion::query()
                ->whereIn('id', $authorized['student_ids'])
                ->lockForUpdate()
                ->orderBy('apellido_paterno')
                ->orderBy('apellido_materno')
                ->orderBy('nombre')
                ->get();

            $grades = Calificacion::query()
                ->where('periodo_id', $context['periodo_id'])
                ->whereIn('asignacion_materia_id', $authorized['assignment_ids'])
                ->whereIn('inscripcion_id', $authorized['student_ids'])
                ->lockForUpdate()
                ->get()
                ->keyBy(fn (Calificacion $grade): string => $grade->inscripcion_id.'-'.$grade->asignacion_materia_id);

            $snapshot = [];
            foreach ($students as $student) {
                foreach ($assignments as $assignment) {
                    $grade = $grades->get($student->id.'-'.$assignment->id);
                    $snapshot[] = [
                        'calificacion_id' => $grade?->id,
                        'inscripcion_id' => (int) $student->id,
                        'asignacion_materia_id' => (int) $assignment->id,
                        'matricula' => (string) ($student->matricula ?? ''),
                        'alumno_nombre' => $this->studentName($student),
                        'materia_nombre' => (string) ($assignment->materia?->materia ?? 'Materia'),
                        'calificacion' => $grade?->calificacion,
                        'observacion' => $grade?->observacion,
                        'es_numerica' => (bool) ($grade?->es_numerica ?? false),
                        'valor_numerico' => $grade?->valor_numerico,
                    ];
                }
            }

            if (collect($snapshot)->contains(fn (array $row): bool => blank($row['calificacion']))) {
                throw ValidationException::withMessages([
                    'calificaciones' => 'No se puede confirmar una entrega incompleta. Todas las calificaciones deben estar capturadas.',
                ]);
            }

            $version = (int) CalificacionEntrega::query()
                ->where('user_id', $user->id)
                ->where('periodo_id', $context['periodo_id'])
                ->where('grupo_id', $context['grupo_id'])
                ->max('version') + 1;

            $confirmedAt = now();
            $folio = sprintf(
                'CAL-%s-%06d-%s-V%02d',
                $confirmedAt->format('YmdHis'),
                $user->id,
                Str::upper(Str::random(4)),
                $version
            );

            $canonicalSnapshot = json_encode(
                $snapshot,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR
            );

            $delivery = CalificacionEntrega::query()->create([
                'folio' => $folio,
                'user_id' => $user->id,
                'persona_id' => $personaId,
                'periodo_id' => $context['periodo_id'],
                'ciclo_escolar_id' => $context['ciclo_escolar_id'],
                'nivel_id' => $context['nivel_id'],
                'generacion_id' => $context['generacion_id'],
                'grado_id' => $context['grado_id'],
                'grupo_id' => $context['grupo_id'],
                'semestre_id' => $context['semestre_id'],
                'version' => $version,
                'estado' => 'confirmada',
                'docente_nombre' => $this->teacherName($user),
                'docente_curp' => mb_strtoupper(trim((string) $user->persona?->curp)),
                'correo_institucional' => $user->email,
                'declaracion' => self::DECLARATION,
                'ip_confirmacion' => request()->ip(),
                'user_agent' => Str::limit((string) request()->userAgent(), 1000, ''),
                'confirmada_at' => $confirmedAt,
                'totales' => [
                    'alumnos' => $students->count(),
                    'materias' => $assignments->count(),
                    'registros' => count($snapshot),
                    'capturadas' => collect($snapshot)->whereNotNull('calificacion')->count(),
                ],
                'snapshot_sha256' => hash('sha256', $canonicalSnapshot),
            ]);

            $delivery->detalles()->createMany($snapshot);

            return $delivery;
        }, 3);

        try {
            return $this->generatePdf($delivery);
        } catch (Throwable $exception) {
            report($exception);
            DB::transaction(fn () => $delivery->delete());

            throw ValidationException::withMessages([
                'calificaciones' => 'Las calificaciones no pudieron confirmarse porque falló la generación del comprobante PDF. Los cambios quedaron como borrador; intenta confirmar nuevamente.',
            ]);
        }
    }

    public function generatePdf(CalificacionEntrega $delivery): CalificacionEntrega
    {
        $delivery->load([
            'detalles',
            'periodo.periodoBasica:id,periodo,descripcion',
            'periodo.parcialBachillerato:id,parcial,descripcion',
            'cicloEscolar',
            'nivel:id,nombre',
            'generacion',
            'grado:id,nombre',
            'grupo.asignacionGrupo:id,nombre',
            'semestre:id,numero',
        ]);

        $pdfBytes = Pdf::loadView('pdf.calificacion-entrega-docente', [
            'entrega' => $delivery,
            'materias' => $delivery->detalles->groupBy('asignacion_materia_id'),
        ])->setPaper('letter', 'landscape')->output();

        $disk = 'local';
        $path = 'calificaciones/entregas/'.$delivery->confirmada_at->format('Y/m').'/'.$delivery->folio.'.pdf';
        Storage::disk($disk)->put($path, $pdfBytes);

        $delivery->forceFill([
            'pdf_disk' => $disk,
            'pdf_path' => $path,
            'pdf_sha256' => hash('sha256', $pdfBytes),
        ])->save();

        return $delivery->fresh();
    }

    private function teacherName(User $user): string
    {
        $name = collect([
            $user->persona?->nombre,
            $user->persona?->apellido_paterno,
            $user->persona?->apellido_materno,
        ])->filter()->map(fn ($value) => trim((string) $value))->implode(' ');

        return $name !== '' ? $name : (string) $user->name;
    }

    private function studentName(Inscripcion $student): string
    {
        return collect([
            $student->apellido_paterno,
            $student->apellido_materno,
            $student->nombre,
        ])->filter()->map(fn ($value) => trim((string) $value))->implode(' ');
    }
}
