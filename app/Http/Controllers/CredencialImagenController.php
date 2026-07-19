<?php

namespace App\Http\Controllers;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Persona;
use App\Services\CredencialImagenService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response;
use ZipArchive;

class CredencialImagenController extends Controller
{
    public function __construct(private readonly CredencialImagenService $imagenes)
    {
    }

    public function alumnos(Request $request, string $slug_nivel, string $formato): Response|BinaryFileResponse
    {
        $this->validarEntorno('alumno');
        $formato = $this->validarFormato($formato);
        $nivel = Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $alumnos = $this->obtenerAlumnos($request, $nivel);
        $this->asegurarResultados($alumnos, 'No se encontraron alumnos para generar credenciales.');

        $cicloEscolar = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('id')
            ->first();

        return $this->descargarAlumnos($alumnos, $nivel, $cicloEscolar, $formato);
    }

    public function previewAlumnos(Request $request, string $slug_nivel): Response
    {
        $this->validarEntorno('alumno');
        $nivel = Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->where('slug', $slug_nivel)
            ->firstOrFail();

        $alumno = $this->obtenerAlumnos($request, $nivel)->first();
        abort_unless($alumno, 404, 'No se encontró un alumno para mostrar la vista previa.');

        $cicloEscolar = CicloEscolar::query()
            ->orderByDesc('es_actual')
            ->orderByDesc('id')
            ->first();

        $contenido = $this->imagenes->renderAlumno($alumno, $nivel, $cicloEscolar, 'png');

        return response($contenido, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="vista-previa-credencial.png"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function profesores(Request $request, string $formato): Response|BinaryFileResponse
    {
        $this->validarEntorno('profesor');
        $formato = $this->validarFormato($formato);
        $request->validate([
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'modo_descarga' => ['required', 'string', 'in:nivel,todos,individual,seleccionados'],
            'persona_individual_id' => ['nullable', 'integer', 'exists:personas,id'],
            'persona_id' => ['nullable', 'integer', 'exists:personas,id'],
            'personas' => ['nullable', 'string'],
            'vigencia' => ['nullable', 'string', 'max:120'],
            'cargo' => ['nullable', 'string', 'max:80'],
        ]);

        $nivel = Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->select('id', 'nombre', 'slug', 'cct', 'logo', 'color', 'director_id')
            ->findOrFail($request->integer('nivel_id'));

        $personas = $this->obtenerPersonas($request, $nivel->id);
        $this->asegurarResultados($personas, 'No se encontró personal para generar credenciales.');

        return $this->descargarProfesores(
            $personas,
            $nivel,
            (string) $request->query('vigencia', 'Agosto ' . now()->year),
            (string) $request->query('cargo', 'PROFESOR'),
            $formato
        );
    }

    public function previewProfesores(Request $request): Response
    {
        $this->validarEntorno('profesor');
        $request->validate([
            'nivel_id' => ['required', 'integer', 'exists:niveles,id'],
            'modo_descarga' => ['required', 'string', 'in:nivel,todos,individual,seleccionados'],
            'persona_individual_id' => ['nullable', 'integer', 'exists:personas,id'],
            'persona_id' => ['nullable', 'integer', 'exists:personas,id'],
            'personas' => ['nullable', 'string'],
            'vigencia' => ['nullable', 'string', 'max:120'],
            'cargo' => ['nullable', 'string', 'max:80'],
        ]);

        $nivel = Nivel::query()
            ->with('director:id,titulo,nombre,apellido_paterno,apellido_materno,cargo,status')
            ->select('id', 'nombre', 'slug', 'cct', 'logo', 'color', 'director_id')
            ->findOrFail($request->integer('nivel_id'));

        $persona = $this->obtenerPersonas($request, $nivel->id)->first();
        abort_unless($persona, 404, 'No se encontró personal para mostrar la vista previa.');

        $contenido = $this->imagenes->renderProfesor(
            $persona,
            $nivel,
            (string) $request->query('vigencia', 'Agosto ' . now()->year),
            (string) $request->query('cargo', 'PROFESOR'),
            'png'
        );

        return response($contenido, 200, [
            'Content-Type' => 'image/png',
            'Content-Disposition' => 'inline; filename="vista-previa-credencial-profesor.png"',
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    private function descargarAlumnos(
        Collection $alumnos,
        Nivel $nivel,
        ?CicloEscolar $cicloEscolar,
        string $formato
    ): Response|BinaryFileResponse {
        if ($alumnos->count() === 1) {
            $alumno = $alumnos->first();
            $contenido = $this->imagenes->renderAlumno($alumno, $nivel, $cicloEscolar, $formato);

            return $this->respuestaImagen(
                $contenido,
                $this->imagenes->nombreArchivoAlumno($alumno, $formato),
                $formato
            );
        }

        $this->verificarLimite($alumnos->count());
        $zip = $this->crearZip('credenciales-alumnos');
        $sinFoto = [];

        try {
            foreach ($alumnos as $alumno) {
                $grado = $this->imagenes->normalizarNombreArchivo(
                    'grado_' . ($alumno->grado?->nombre ?: 'sin_grado')
                );
                $grupo = $this->imagenes->normalizarNombreArchivo(
                    'grupo_' . ($alumno->grupo?->asignacionGrupo?->nombre ?: 'sin_grupo')
                );
                $nombre = $this->imagenes->nombreArchivoAlumno($alumno, $formato);
                $rutaInterna = $grado . '/' . $grupo . '/' . $nombre;

                $zip['archivo']->addFromString(
                    $rutaInterna,
                    $this->imagenes->renderAlumno($alumno, $nivel, $cicloEscolar, $formato)
                );

                if (! $alumno->foto_existe) {
                    $sinFoto[] = $nombre;
                }
            }

            $this->agregarAdvertencias($zip['archivo'], $sinFoto);
            $zip['archivo']->close();
        } catch (\Throwable $e) {
            $zip['archivo']->close();
            @unlink($zip['ruta']);
            throw $e;
        }

        return response()->download(
            $zip['ruta'],
            'credenciales_' . $nivel->slug . '_' . now()->format('Ymd_His') . '.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    private function descargarProfesores(
        Collection $personas,
        Nivel $nivel,
        string $vigencia,
        string $cargo,
        string $formato
    ): Response|BinaryFileResponse {
        if ($personas->count() === 1) {
            $persona = $personas->first();
            $contenido = $this->imagenes->renderProfesor($persona, $nivel, $vigencia, $cargo, $formato);

            return $this->respuestaImagen(
                $contenido,
                $this->imagenes->nombreArchivoProfesor($persona, $formato),
                $formato
            );
        }

        $this->verificarLimite($personas->count());
        $zip = $this->crearZip('credenciales-profesores');
        $sinFoto = [];

        try {
            foreach ($personas as $persona) {
                $nombre = $this->imagenes->nombreArchivoProfesor($persona, $formato);
                $zip['archivo']->addFromString(
                    'profesores/' . $nombre,
                    $this->imagenes->renderProfesor($persona, $nivel, $vigencia, $cargo, $formato)
                );

                if (! $persona->foto_existe) {
                    $sinFoto[] = $nombre;
                }
            }

            $this->agregarAdvertencias($zip['archivo'], $sinFoto);
            $zip['archivo']->close();
        } catch (\Throwable $e) {
            $zip['archivo']->close();
            @unlink($zip['ruta']);
            throw $e;
        }

        return response()->download(
            $zip['ruta'],
            'credenciales_profesores_' . $nivel->slug . '_' . now()->format('Ymd_His') . '.zip',
            ['Content-Type' => 'application/zip']
        )->deleteFileAfterSend(true);
    }

    private function obtenerAlumnos(Request $request, Nivel $nivel): Collection
    {
        $modo = (string) $request->query('modo_descarga', 'grupo');
        abort_unless(
            in_array($modo, ['nivel', 'generacion', 'grado', 'semestre', 'grupo', 'individual', 'seleccionados'], true),
            422,
            'El modo de descarga no es válido.'
        );

        $query = Inscripcion::query()
            ->with([
                'nivel',
                'grado',
                'generacion',
                'grupo.asignacionGrupo',
                'semestre',
            ])
            ->where('nivel_id', $nivel->id);

        if ($modo === 'generacion') {
            $query->where('generacion_id', $request->integer('generacion_id'));
        }

        if ($modo === 'grado') {
            $query->where('generacion_id', $request->integer('generacion_id'))
                ->where('grado_id', $request->integer('grado_id'));
        }

        if ($modo === 'semestre') {
            $query->where('generacion_id', $request->integer('generacion_id'))
                ->where('grado_id', $request->integer('grado_id'));

            if (Schema::hasColumn('inscripciones', 'semestre_id')) {
                $query->where('semestre_id', $request->integer('semestre_id'));
            }
        }

        if ($modo === 'grupo') {
            $query->where('generacion_id', $request->integer('generacion_id'))
                ->where('grado_id', $request->integer('grado_id'))
                ->where('grupo_id', $request->integer('grupo_id'));

            if (
                (((int) $nivel->id === 4) || $nivel->slug === 'bachillerato')
                && Schema::hasColumn('inscripciones', 'semestre_id')
                && $request->filled('semestre_id')
            ) {
                $query->where('semestre_id', $request->integer('semestre_id'));
            }
        }

        if ($modo === 'individual') {
            $query->whereKey($request->integer('alumno_id'));
        }

        if ($modo === 'seleccionados') {
            $ids = collect(explode(',', (string) $request->query('alumnos')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $query->whereIn('id', $ids->all());
        }

        return $query
            ->orderBy('grado_id')
            ->orderBy('grupo_id')
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombre')
            ->get();
    }

    private function obtenerPersonas(Request $request, int $nivelId): Collection
    {
        $modo = (string) $request->query('modo_descarga', 'seleccionados');
        $query = Persona::query()
            ->select('personas.*')
            ->with([
                'personaRoles.rolePersona:id,nombre,slug,status',
                'personaNiveles' => function ($consulta) use ($nivelId) {
                    $consulta->where('nivel_id', $nivelId)
                        ->with([
                            'nivel:id,nombre,slug,cct,logo,color,director_id',
                            'detalles.personaRole.rolePersona:id,nombre,slug,status',
                        ]);
                },
            ])
            ->where('personas.status', 1)
            ->whereHas('personaNiveles', fn (Builder $q) => $q->where('nivel_id', $nivelId));

        if ($modo === 'individual') {
            $personaId = $request->integer('persona_individual_id') ?: $request->integer('persona_id');
            $query->whereKey($personaId);
        }

        if ($modo === 'seleccionados') {
            $ids = collect(explode(',', (string) $request->query('personas')))
                ->map(fn ($id) => (int) trim($id))
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return collect();
            }

            $query->whereIn('personas.id', $ids->all());
        }

        return $query
            ->orderBy('personas.apellido_paterno')
            ->orderBy('personas.apellido_materno')
            ->orderBy('personas.nombre')
            ->get();
    }

    private function validarFormato(string $formato): string
    {
        $formato = strtolower($formato);
        abort_unless(in_array($formato, ['png', 'jpg'], true), 404);

        return $formato;
    }

    private function respuestaImagen(string $contenido, string $nombre, string $formato): Response
    {
        return response($contenido, 200, [
            'Content-Type' => $this->imagenes->mime($formato),
            'Content-Disposition' => 'attachment; filename="' . $nombre . '"',
            'Content-Length' => (string) strlen($contenido),
            'Cache-Control' => 'private, no-store',
        ]);
    }

    private function asegurarResultados(Collection $resultados, string $mensaje): void
    {
        abort_if($resultados->isEmpty(), 404, $mensaje);
    }

    private function verificarLimite(int $cantidad): void
    {
        $maximo = max(1, (int) config('credenciales.max_imagenes_por_zip', 250));
        abort_if(
            $cantidad > $maximo,
            422,
            "La selección contiene {$cantidad} credenciales. El máximo permitido por descarga es {$maximo}. Divide la descarga en grupos más pequeños."
        );

        abort_unless(
            class_exists(ZipArchive::class),
            422,
            'La extensión ZIP de PHP no está habilitada. Actívala para descargar credenciales masivas.'
        );
    }

    /** @return array{archivo: ZipArchive, ruta: string} */
    private function crearZip(string $prefijo): array
    {
        $directorio = storage_path('app/temp/credenciales');
        File::ensureDirectoryExists($directorio, 0775, true);
        $ruta = $directorio . DIRECTORY_SEPARATOR . $prefijo . '-' . Str::uuid() . '.zip';
        $zip = new ZipArchive();

        abort_if(
            $zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true,
            422,
            'No fue posible crear el archivo ZIP temporal. Verifica permisos de escritura en storage/app/temp.'
        );

        return ['archivo' => $zip, 'ruta' => $ruta];
    }

    private function validarEntorno(string $tipo): void
    {
        try {
            $this->imagenes->verificarRecursos($tipo);
        } catch (RuntimeException $e) {
            abort(422, $e->getMessage());
        }
    }

    private function agregarAdvertencias(ZipArchive $zip, array $sinFoto): void
    {
        if ($sinFoto === []) {
            return;
        }

        $contenido = "Las siguientes credenciales se generaron sin fotografía:\r\n\r\n"
            . implode("\r\n", $sinFoto)
            . "\r\n\r\nEl espacio FOTO + SELLO se mantuvo visible para permitir su impresión.";

        $zip->addFromString('ADVERTENCIAS.txt', $contenido);
    }
}
