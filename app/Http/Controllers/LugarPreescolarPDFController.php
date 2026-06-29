<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Grado;
use App\Models\Inscripcion;
use App\Models\LugarPreescolar;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

use Carbon\Carbon;
use Illuminate\Http\Request;

class LugarPreescolarPDFController extends Controller
{
    public function show(Request $request, LugarPreescolar $lugarPreescolar)
    {
        $lugarPreescolar->load([
            'alumno.nivel.director',
            'alumno.grado',
            'alumno.generacion',
            'alumno.grupo.asignacionGrupo',
            'cicloEscolar',
        ]);

        $alumno = $lugarPreescolar->alumno;

        $fechaPdf = $this->fechaPdf($request->query('fecha'));

        $pdf = Pdf::loadView('pdf.reconocimiento_preescolar_pdf', [
            'reconocimiento' => $lugarPreescolar,
            'alumno' => $alumno,
            'cicloEscolar' => $lugarPreescolar->cicloEscolar,
            'fechaPdf' => $fechaPdf,
            'educadoraNombre' => $this->obtenerEducadora($alumno),
            'directoraNombre' => $this->obtenerDirectora($alumno),
            'supervisoraNombre' => $this->obtenerSupervisora($alumno),
            'logoPrincipal' => $this->publicImagePath('imagenes/logo-letra.png'),
            'logoPenacho' => $this->publicImagePath('imagenes/logo-seg.png') ?: $this->publicImagePath('penacho.jpg'),
            'marcaAgua' => $this->publicImagePath('imagenes/logo-letra.png') ?: $this->publicImagePath('logo.png'),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('reconocimiento-preescolar-' . $alumno->id . '.pdf');
    }

    private function publicImagePath(string $path): ?string
    {
        $fullPath = public_path($path);

        return file_exists($fullPath) ? $fullPath : null;
    }

    private function obtenerDirectora($alumno): string
    {
        $director = $alumno->nivel?->director;

        if (!$director) {
            return 'DIRECCIÓN';
        }

        return trim(mb_strtoupper(collect([
            $director->titulo ?? null,
            $director->nombre ?? null,
            $director->apellido_paterno ?? null,
            $director->apellido_materno ?? null,
        ])->filter()->implode(' ')));
    }

    private function obtenerSupervisora($alumno): string
    {
        if (!$alumno->nivel?->supervisor) {
            return 'SUPERVISIÓN';
        }

        $supervisora = $alumno->nivel->supervisor;

        return trim(mb_strtoupper(collect([
            $supervisora->titulo ?? null,
            $supervisora->nombre ?? null,
            $supervisora->apellido_paterno ?? null,
            $supervisora->apellido_materno ?? null,
        ])->filter()->implode(' ')));
    }

    private function obtenerEducadora($alumno): string
    {
        if (
            !Schema::hasTable('persona_nivel_detalles') ||
            !Schema::hasTable('persona_nivel') ||
            !Schema::hasTable('persona_role') ||
            !Schema::hasTable('role_personas') ||
            !Schema::hasTable('personas')
        ) {
            return 'EDUCADORA';
        }

        $persona = DB::table('persona_nivel_detalles as pnd')
            ->join('persona_nivel as pn', 'pn.id', '=', 'pnd.persona_nivel_id')
            ->join('persona_role as pr', 'pr.id', '=', 'pnd.persona_role_id')
            ->join('role_personas as rp', 'rp.id', '=', 'pr.role_persona_id')
            ->join('personas as p', 'p.id', '=', 'pr.persona_id')
            ->where('pn.nivel_id', $alumno->nivel_id)
            ->whereColumn('pn.persona_id', 'pr.persona_id')
            ->where(function ($q) {
                $q->whereNull('p.status')->orWhere('p.status', true);
            })
            ->whereIn('rp.slug', [
                'maestro_frente_a_grupo',
                'docente',
                'director_con_grupo',
            ])
            ->where(function ($q) use ($alumno) {
                $q->where('pnd.grado_id', $alumno->grado_id)
                    ->orWhereNull('pnd.grado_id');
            })
            ->where(function ($q) use ($alumno) {
                $q->where('pnd.grupo_id', $alumno->grupo_id)
                    ->orWhereNull('pnd.grupo_id');
            })
            ->orderByRaw(
                'CASE WHEN pnd.grupo_id = ? THEN 0 WHEN pnd.grupo_id IS NULL THEN 1 ELSE 2 END',
                [$alumno->grupo_id ?? 0]
            )
            ->orderByRaw(
                'CASE WHEN pnd.grado_id = ? THEN 0 WHEN pnd.grado_id IS NULL THEN 1 ELSE 2 END',
                [$alumno->grado_id ?? 0]
            )
            ->select([
                'p.titulo',
                'p.nombre',
                'p.apellido_paterno',
                'p.apellido_materno',
            ])
            ->first();

        if (!$persona) {
            return 'EDUCADORA';
        }

        return trim(mb_strtoupper(collect([
            $persona->titulo ?? null,
            $persona->nombre ?? null,
            $persona->apellido_paterno ?? null,
            $persona->apellido_materno ?? null,
        ])->filter()->implode(' ')));
    }


    public function diploma(Request $request, Inscripcion $inscripcion)
    {
        $request->validate([
            'ciclo_escolar_id' => ['required', 'integer', 'exists:ciclo_escolares,id'],
            'fecha' => ['nullable', 'date'],
        ]);

        $inscripcion->load([
            'nivel.director',
            'nivel.supervisor',
            'grado',
            'generacion',
            'grupo.asignacionGrupo',
        ]);

        abort_unless(
            $inscripcion->nivel?->slug === 'preescolar',
            404,
            'El alumno no pertenece al nivel preescolar.'
        );

        $gradoTerminalId = Grado::query()
            ->where('nivel_id', $inscripcion->nivel_id)
            ->orderByDesc('orden')
            ->orderByDesc('id')
            ->value('id');

        abort_unless(
            $gradoTerminalId !== null && (int) $inscripcion->grado_id === (int) $gradoTerminalId,
            403,
            'El diploma de preescolar solo está disponible para tercer grado.'
        );

        $cicloEscolar = CicloEscolar::query()->findOrFail(
            $request->integer('ciclo_escolar_id')
        );

        $alumno = $inscripcion;

        $fechaPdf = $this->fechaPdf($request->query('fecha'));

        $pdf = Pdf::loadView('pdf.diploma_preescolar_pdf', [
            'alumno' => $alumno,
            'cicloEscolar' => $cicloEscolar,
            'fechaPdf' => $fechaPdf,
            'educadoraNombre' => $this->obtenerEducadora($alumno),
            'directoraNombre' => $this->obtenerDirectora($alumno),
            'supervisoraNombre' => $this->obtenerSupervisora($alumno),
            'logoPrincipal' => $this->publicImagePath('imagenes/logo-letra.png'),
            'logoPenacho' => $this->publicImagePath('imagenes/logo-seg.png') ?: $this->publicImagePath('penacho.jpg'),
            'marcaAgua' => $this->publicImagePath('imagenes/logo-letra.png') ?: $this->publicImagePath('logo.png'),
        ])->setPaper('letter', 'landscape');

        return $pdf->stream('diploma-preescolar-' . $alumno->id . '.pdf');
    }

    private function fechaPdf(?string $fecha): string
    {
        try {
            $fecha = $fecha ?: now()->format('Y-m-d');

            return Carbon::parse($fecha)
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        } catch (\Throwable $e) {
            return now()
                ->locale('es')
                ->translatedFormat('d \\d\\e F \\d\\e Y');
        }
    }
}
