<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Services\ContextoEscolarService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ListasGeneralesFormatosController extends Controller
{
    public function __invoke(Request $request)
    {
        abort_unless((bool) (auth()->user()?->is_admin ?? false), 403);

        $opcion = (string) $request->input('opcion_descarga', 'personalizadores');
        abort_unless(in_array($opcion, ['personalizadores', 'etiquetas'], true), 404, 'El formato seleccionado no está disponible en modo global.');

        $modo = (string) $request->input('modo_descarga', 'seleccionados');
        abort_unless(in_array($modo, ['seleccionados', 'grupo', 'nivel', 'todos_activos'], true), 422, 'El alcance seleccionado no es válido.');

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
            $ids = $this->idsSeleccionados($request);
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
            $idsSolicitados = $this->idsSeleccionados($request);
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

    /**
     * @return array<int, int>
     */
    private function idsSeleccionados(Request $request): array
    {
        $valor = $request->input('alumnos', '');
        $valores = collect(is_array($valor) ? $valor : explode(',', (string) $valor))
            ->map(fn ($id): string => trim((string) $id))
            ->filter(fn (string $id): bool => $id !== '')
            ->values();

        abort_if($valores->isEmpty(), 422, 'Selecciona al menos un alumno para generar el documento.');
        abort_if($valores->count() > 500, 422, 'No se pueden seleccionar más de 500 alumnos en una sola descarga manual.');

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
