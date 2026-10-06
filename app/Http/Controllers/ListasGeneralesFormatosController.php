<?php

namespace App\Http\Controllers;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Nivel;
use App\Models\Persona;
use App\Models\TallerSesion;
use App\Services\ContextoEscolarService;
use App\Services\ListasGeneralesCicloService;
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
        $estadoCiclo = (string) $request->input('estado_ciclo', app(ListasGeneralesCicloService::class)->estadoPredeterminado($cicloEscolar));
        abort_unless(array_key_exists($estadoCiclo, app(ListasGeneralesCicloService::class)->estadosDisponibles()), 422, 'El estado del ciclo seleccionado no es válido.');

        $filtros = [];
        $ids = [];

        if ($modo === 'seleccionados') {
            $ids = $this->idsSeleccionados($request, 'alumnos', 'alumno');
        }

        if ($modo === 'nivel') {
            abort_unless($nivel, 422, 'Selecciona un nivel para generar el documento.');
            $filtros['nivel_id'] = (int) $nivel->id;
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
                soloActivo: (bool) $cicloEscolar->es_actual,
            );

            abort_unless($grupo, 404, 'El grupo seleccionado no pertenece al ciclo escolar seleccionado.');

            $filtros = [
                'nivel_id' => (int) $nivel->id,
                'generacion_id' => $generacionId,
                'grado_id' => $gradoId,
                'grupo_id' => $grupoId,
            ];
            $esBachillerato ? $filtros['semestre_id'] = $semestreId : $filtros['sin_semestre'] = 1;
        }

        $alumnos = app(ListasGeneralesCicloService::class)->alumnos(
            cicloEscolarId: (int) $cicloEscolar->id,
            filtros: $filtros,
            estado: $estadoCiclo,
            ids: $ids,
        );

        if ($modo === 'seleccionados') {
            $idsEncontrados = $alumnos->pluck('id')->map(fn ($id): int => (int) $id)->unique()->values()->all();
            $invalidos = array_values(array_diff($ids, $idsEncontrados));
            abort_if($invalidos !== [], 422, 'Uno o más alumnos seleccionados no pertenecen al ciclo o al estado seleccionado. Actualiza la selección e inténtalo nuevamente.');
        }

        abort_if($alumnos->isEmpty(), 404, 'No se encontraron alumnos para el ciclo, estado y contexto seleccionados.');

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
            . '-' . Str::slug($cicloEscolar->nombre, '-')
            . '-' . Str::slug($estadoCiclo, '-')
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
            'estadoCiclo' => $estadoCiclo,
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

            abort_if($invalidos !== [], 422, 'Uno o más profesores seleccionados no tienen carga académica válida en el ciclo seleccionado. Actualiza la selección e inténtalo nuevamente.');
        }

        abort_if($profesores->isEmpty(), 404, 'No se encontraron profesores con carga académica en el ciclo seleccionado.');

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
            . '-' . Str::slug($cicloEscolar->nombre, '-')
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
            ->where(function (Builder $candidato) use ($cicloEscolarId): void {
                $candidato
                    ->whereHas('asignacionMaterias', fn (Builder $carga) => $carga
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
                ->whereHas('asignacionMaterias', fn (Builder $carga) => $carga
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
