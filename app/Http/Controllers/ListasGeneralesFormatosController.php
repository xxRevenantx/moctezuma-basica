<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\TallerSesion;
use App\Services\ContextoEscolarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ListasGeneralesFormatosController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $opcion = (string) $request->input('opcion_descarga', 'personalizadores');
        abort_unless(in_array($opcion, ['personalizadores', 'etiquetas'], true), 404, 'El formato seleccionado no está disponible en modo global.');

        $audiencia = (string) $request->input('audiencia_personalizador', 'alumnos');
        if ($opcion !== 'personalizadores') {
            $audiencia = 'alumnos';
        }
        abort_unless(in_array($audiencia, ['alumnos', 'profesores'], true), 422, 'El tipo de personalizador seleccionado no es válido.');

        $modo = (string) $request->input('modo_descarga', 'seleccionados');
        $modosPermitidos = $audiencia === 'profesores'
            ? ['seleccionados', 'nivel', 'todos_activos']
            : ['seleccionados', 'grupo', 'nivel', 'todos_activos'];
        abort_unless(in_array($modo, $modosPermitidos, true), 422, 'El alcance seleccionado no es válido.');

        $cicloEscolar = CicloEscolar::query()
            ->when($request->integer('ciclo_escolar_id'), fn (Builder $query) => $query->whereKey($request->integer('ciclo_escolar_id')))
            ->when(!$request->integer('ciclo_escolar_id'), fn (Builder $query) => $query->where('es_actual', true))
            ->first()
            ?? CicloEscolar::query()
                ->orderByDesc('inicio_anio')
                ->orderByDesc('fin_anio')
                ->firstOrFail();

        $nivel = $request->integer('nivel_id')
            ? Nivel::query()->find($request->integer('nivel_id'))
            : null;

        if ($audiencia === 'profesores') {
            return $this->personalizadoresProfesores(
                request: $request,
                modo: $modo,
                cicloEscolar: $cicloEscolar,
                nivel: $nivel,
            );
        }

        return $this->formatosAlumnos(
            request: $request,
            opcion: $opcion,
            modo: $modo,
            cicloEscolar: $cicloEscolar,
            nivel: $nivel,
        );
    }

    private function formatosAlumnos(
        Request $request,
        string $opcion,
        string $modo,
        CicloEscolar $cicloEscolar,
        ?Nivel $nivel,
    ) {
        $query = Inscripcion::query()
            ->visiblesEnListas()
            ->with([
                'nivel:id,nombre,slug',
                'generacion:id,nivel_id,nombre,anio_ingreso,anio_egreso',
                'grado:id,nivel_id,nombre,orden',
                'semestre:id,grado_id,numero,orden_global',
                'grupo:id,nivel_id,grado_id,generacion_id,semestre_id,asignacion_grupo_id',
                'grupo.asignacionGrupo:id,nombre',
            ]);

        if ($modo === 'seleccionados') {
            $ids = $this->idsSeleccionados($request, 'alumnos', 'alumno');
            $query->whereIn('id', $ids);
        }

        if ($modo === 'nivel') {
            abort_unless($nivel, 422, 'Selecciona un nivel para generar el documento.');
            $query->where('nivel_id', $nivel->id);
        }

        if ($modo === 'grupo') {
            abort_unless($nivel, 422, 'Selecciona un nivel para generar el documento.');

            $generacionId = $request->integer('generacion_id');
            $gradoId = $request->integer('grado_id');
            $grupoId = $request->integer('grupo_id');
            $semestreId = $request->integer('semestre_id') ?: null;
            $esBachillerato = (int) $nivel->id === 4 || $nivel->slug === 'bachillerato';

            abort_if(!$generacionId || !$gradoId || !$grupoId, 422, 'Selecciona generación, grado y grupo.');
            abort_if($esBachillerato && !$semestreId, 422, 'Selecciona el semestre de bachillerato.');

            $grupo = app(ContextoEscolarService::class)->grupoValido(
                grupoId: $grupoId,
                nivelId: (int) $nivel->id,
                cicloEscolarId: (int) $cicloEscolar->id,
                generacionId: $generacionId,
                gradoId: $gradoId,
                semestreId: $semestreId,
                bachillerato: $esBachillerato,
            );

            abort_unless($grupo, 404, 'El grupo seleccionado no pertenece al contexto escolar actual.');

            $query
                ->where('nivel_id', $nivel->id)
                ->where('generacion_id', $generacionId)
                ->where('grado_id', $gradoId)
                ->where('grupo_id', $grupoId)
                ->when($esBachillerato, fn (Builder $q) => $q->where('semestre_id', $semestreId));
        }

        $alumnos = $query
            ->orderBy('nivel_id')
            ->orderBy('grado_id')
            ->orderBy('semestre_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($modo === 'seleccionados') {
            $idsSolicitados = $this->idsSeleccionados($request, 'alumnos', 'alumno');
            $idsEncontrados = $alumnos->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $invalidos = array_values(array_diff($idsSolicitados, $idsEncontrados));

            abort_if($invalidos !== [], 422, 'Uno o más alumnos seleccionados ya no están activos. Actualiza la selección e inténtalo nuevamente.');
        }

        abort_if($alumnos->isEmpty(), 404, 'No se encontraron alumnos activos para generar el documento.');

        $vista = $opcion === 'personalizadores'
            ? 'pdf.personalizadores'
            : 'pdf.etiquetas_pdf';

        $nombreAlcance = match ($modo) {
            'seleccionados' => 'seleccionados',
            'grupo' => 'grupo',
            'nivel' => $nivel?->slug ?? 'nivel',
            default => 'todos-los-niveles',
        };

        $nombreArchivo = $opcion
            . '-' . Str::slug($nombreAlcance, '-')
            . '-' . now()->format('Ymd-His')
            . '.pdf';

        return Pdf::loadView($vista, [
            'alumnos' => $alumnos,
            'modoGlobal' => true,
            'nivel' => $nivel,
            'grado' => null,
            'grupo' => null,
            'generacion' => null,
            'semestre' => null,
            'esBachillerato' => false,
            'cicloEscolar' => $cicloEscolar,
            'imagenPersonalizador' => $this->imagenBase64Publica('imagenes/personalizador.jpg'),
        ])
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
    }

    private function personalizadoresProfesores(
        Request $request,
        string $modo,
        CicloEscolar $cicloEscolar,
        ?Nivel $nivel,
    ) {
        $query = $this->consultaProfesores((int) $cicloEscolar->id);

        if ($modo === 'seleccionados') {
            $ids = $this->idsSeleccionados($request, 'profesores', 'profesor');
            $query->whereIn('personas.id', $ids);
        }

        if ($modo === 'nivel') {
            abort_unless($nivel, 422, 'Selecciona un nivel para generar los personalizadores de profesores.');
            $this->filtrarProfesoresPorNivel($query, (int) $nivel->id, (int) $cicloEscolar->id);
        }

        $profesores = $query
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();

        if ($modo === 'seleccionados') {
            $idsSolicitados = $this->idsSeleccionados($request, 'profesores', 'profesor');
            $idsEncontrados = $profesores->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $invalidos = array_values(array_diff($idsSolicitados, $idsEncontrados));

            abort_if($invalidos !== [], 422, 'Uno o más profesores seleccionados ya no están activos o ya no cumplen los criterios docentes del ciclo. Actualiza la selección e inténtalo nuevamente.');
        }

        abort_if($profesores->isEmpty(), 404, 'No se encontraron profesores activos para generar el documento.');

        $profesores->each(function (Persona $profesor): void {
            $profesor->setAttribute('niveles_personalizador', $this->nivelesProfesor($profesor));
        });

        $nombreAlcance = match ($modo) {
            'seleccionados' => 'seleccionados',
            'nivel' => $nivel?->slug ?? 'nivel',
            default => 'todos-los-profesores',
        };

        $nombreArchivo = 'personalizadores-profesores-'
            . Str::slug($nombreAlcance, '-')
            . '-' . now()->format('Ymd-His')
            . '.pdf';

        return Pdf::loadView('pdf.personalizadores_profesores', [
            'profesores' => $profesores,
            'cicloEscolar' => $cicloEscolar,
        ])
            ->setPaper('letter', 'portrait')
            ->stream($nombreArchivo);
    }

    private function consultaProfesores(int $cicloEscolarId): Builder
    {
        return Persona::query()
            ->with([
                'personaNiveles' => fn ($q) => $q
                    ->select('id', 'persona_id', 'nivel_id', 'estado', 'fecha_fin')
                    ->where('estado', 'activo')
                    ->where(function ($vigencia) {
                        $vigencia->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString());
                    })
                    ->with('nivel:id,nombre,slug'),
                'asignacionMaterias' => fn ($q) => $q
                    ->select('id', 'profesor_id', 'ciclo_escolar_id', 'nivel_id', 'estado')
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA])
                    ->with('nivel:id,nombre,slug'),
                'tallerSesiones' => fn ($q) => $q
                    ->select('id', 'profesor_id', 'ciclo_escolar_id', 'estado')
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
                    ->with(['grupos:id,nivel_id', 'grupos.nivel:id,nombre,slug']),
            ])
            ->where('personas.status', true)
            ->where(function (Builder $candidato) use ($cicloEscolarId): void {
                $candidato
                    ->whereHas('rolesPersona', fn (Builder $rol) => $rol
                        ->where('status', true)
                        ->where('es_docente', true))
                    ->orWhereHas('asignacionMaterias', fn (Builder $carga) => $carga
                        ->where('ciclo_escolar_id', $cicloEscolarId)
                        ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA]))
                    ->orWhereHas('tallerSesiones', fn (Builder $taller) => $taller
                        ->where('ciclo_escolar_id', $cicloEscolarId)
                        ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA));
            });
    }

    private function filtrarProfesoresPorNivel(Builder $query, int $nivelId, int $cicloEscolarId): void
    {
        $query->where(function (Builder $porNivel) use ($nivelId, $cicloEscolarId): void {
            $porNivel
                ->whereHas('personaNiveles', fn (Builder $relacion) => $relacion
                    ->where('nivel_id', $nivelId)
                    ->where('estado', 'activo')
                    ->where(function ($vigencia) {
                        $vigencia->whereNull('fecha_fin')->orWhereDate('fecha_fin', '>=', now()->toDateString());
                    }))
                ->orWhereHas('asignacionMaterias', fn (Builder $carga) => $carga
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('nivel_id', $nivelId)
                    ->whereIn('estado', [AsignacionMateria::ESTADO_ACTIVA, AsignacionMateria::ESTADO_CERRADA]))
                ->orWhereHas('tallerSesiones', fn (Builder $taller) => $taller
                    ->where('ciclo_escolar_id', $cicloEscolarId)
                    ->where('estado', '!=', TallerSesion::ESTADO_ARCHIVADA)
                    ->whereHas('grupos', fn (Builder $grupo) => $grupo->where('nivel_id', $nivelId)));
        });
    }

    private function nivelesProfesor(Persona $profesor): string
    {
        $niveles = collect();

        foreach ($profesor->personaNiveles as $relacion) {
            if ($relacion->nivel) {
                $niveles->push($relacion->nivel);
            }
        }

        foreach ($profesor->asignacionMaterias as $carga) {
            if ($carga->nivel) {
                $niveles->push($carga->nivel);
            }
        }

        foreach ($profesor->tallerSesiones as $sesion) {
            foreach ($sesion->grupos as $grupo) {
                if ($grupo->nivel) {
                    $niveles->push($grupo->nivel);
                }
            }
        }

        $nombres = $niveles
            ->filter()
            ->unique(fn ($nivel): int => (int) $nivel->id)
            ->sortBy(fn ($nivel): int => (int) $nivel->id)
            ->pluck('nombre')
            ->filter()
            ->map(fn ($nombre): string => mb_strtoupper((string) $nombre, 'UTF-8'))
            ->values();

        return $nombres->isNotEmpty()
            ? $nombres->implode(' / ')
            : 'SIN NIVEL ASIGNADO';
    }

    /**
     * @return array<int, int>
     */
    private function idsSeleccionados(Request $request, string $campo, string $sustantivo): array
    {
        $valor = $request->input($campo, '');
        $valores = collect(is_array($valor) ? $valor : explode(',', (string) $valor))
            ->map(fn ($id): string => trim((string) $id))
            ->filter(fn (string $id): bool => $id !== '')
            ->values();

        abort_if($valores->isEmpty(), 422, 'Selecciona al menos un ' . $sustantivo . ' para generar el documento.');
        abort_if($valores->count() > 500, 422, 'No se pueden seleccionar más de 500 registros en una sola descarga manual.');

        return $valores
            ->map(function (string $id): int {
                abort_unless(ctype_digit($id) && (int) $id > 0, 422, 'La selección contiene identificadores no válidos.');
                return (int) $id;
            })
            ->unique()
            ->values()
            ->all();
    }

    private function imagenBase64Publica(string $rutaRelativa): ?string
    {
        $ruta = public_path(ltrim($rutaRelativa, '/'));
        if (!is_file($ruta) || !is_readable($ruta)) {
            return null;
        }

        $extension = strtolower(pathinfo($ruta, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => 'application/octet-stream',
        };

        $contenido = file_get_contents($ruta);
        if ($contenido === false) {
            return null;
        }

        return 'data:' . $mime . ';base64,' . base64_encode($contenido);
    }
}
