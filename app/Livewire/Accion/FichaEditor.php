<?php

namespace App\Livewire\Accion;

use App\Models\CicloEscolar;
use App\Models\FichaDescriptiva;
use App\Models\FichaEstado;
use App\Services\CicloNivelGateService;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

/**
 * Editor independiente del listado. Reutiliza la autorización, saneamiento,
 * control de concurrencia, historial y Groq del módulo de Fichas existente.
 * No existe ningún modal dentro del listado, y TinyMCE solo vive en esta ruta.
 */
class FichaEditor extends Ficha
{
    #[Locked]
    public string $urlRegreso = '';

    /** @var array<string, mixed> */
    #[Locked]
    public array $filtrosRegreso = [];

    public function mount(?string $slug_nivel = null, ?string $slug_grado = null, ?int $inscripcion = null): void
    {
        // Esta página usa una ruta FIJA: /nivel/preescolar/fichas/{inscripcion}/editar.
        // Livewire no recibe {slug_nivel} ni {slug_grado}. Una firma string no anulable
        // provoca TypeError cuando el contenedor inyecta null en esos argumentos.
        // Conservar la firma compatible con Ficha::mount y fijar explícitamente el nivel.
        parent::mount('preescolar', null);

        // Resolver el ID desde el único parámetro declarado en la ruta, sin depender
        // del orden en que el contenedor de Laravel entregue los argumentos de mount().
        $inscripcionRuta = request()->route('inscripcion');
        $idValidado = filter_var(
            $inscripcionRuta ?? $inscripcion,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1]]
        );
        abort_unless($idValidado !== false && $idValidado !== null, 404);
        $inscripcionId = (int) $idValidado;

        $periodo = filter_var(request()->query('periodo', 1), FILTER_VALIDATE_INT);
        abort_unless(in_array($periodo, [1, 2, 3], true), 404, 'Periodo no válido.');
        $this->periodo = (int) $periodo;

        $ciclo = request()->query('ciclo_escolar_id');
        if ($ciclo !== null) {
            $id = filter_var($ciclo, FILTER_VALIDATE_INT);
            abort_unless($id && $id > 0 && CicloEscolar::query()->whereKey($id)->exists(), 404);
            $this->ciclo_escolar_id = (int) $id;
        }

        $fecha = request()->query('fecha_lugar');
        if (is_string($fecha) && mb_strlen($fecha) <= 300) {
            $this->fecha_lugar = $fecha;
        }

        // El método heredado verifica nivel, ciclo, vigencia y grupo autorizado.
        // Además carga la versión SHA256 del campo para el control de concurrencia.
        $this->abrirModal($inscripcionId);
        $alumno = $this->alumnoModal;
        abort_unless($alumno, 404);
        $this->generacion_id = $alumno->generacion_id;
        $this->grado_id = $alumno->grado_id;
        $this->grupo_id = $alumno->grupo_id;

        // Regreso solo a rutas internas conocidas: nunca se acepta una URL libre.
        foreach (['generacion_id', 'grado_id', 'grupo_id', 'busqueda', 'fecha_lugar', 'estadoFiltro', 'vistaAlumnos', 'page'] as $clave) {
            $valor = request()->query($clave);
            if (is_scalar($valor) && trim((string) $valor) !== '') {
                $this->filtrosRegreso[$clave] = (string) $valor;
            }
        }
        $this->filtrosRegreso['ciclo_escolar_id'] = $this->ciclo_escolar_id;
        $this->filtrosRegreso['periodo'] = $this->periodo;
        $this->urlRegreso = auth()->user()?->isProfessor()
            ? route('docente.fichas', $this->filtrosRegreso)
            : route('submodulos.accion', array_merge([
                'slug_nivel' => 'preescolar',
                'accion' => 'fichas',
            ], $this->filtrosRegreso));
    }

    /**
     * Guarda el campo desde el editor enriquecido en una sola petición y
     * confirma el resultado. Reutiliza autorización, historial, sanitización
     * y control de concurrencia de Ficha::guardarBorrador().
     *
     * @return array{guardado: bool, mensaje: string|null}
     */
    public function guardarEditor(string $html, CicloNivelGateService $gate): array
    {
        $this->descripcion = $html;
        $this->estadoGuardado = 'pendiente';
        $this->resetValidation();

        $this->guardarBorrador($gate);

        $mensaje = $this->getErrorBag()->first();

        return [
            'guardado' => $this->estadoGuardado === 'guardado' && $mensaje === '',
            'mensaje' => $mensaje !== '' ? $mensaje : null,
        ];
    }

    public function render(): View
    {
        $alumno = $this->alumnoModal;
        abort_unless($alumno, 404);

        $resumen = FichaDescriptiva::query()
            ->where('inscripcion_id', $alumno->id)
            ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
            ->where('periodo', $this->periodo)
            ->pluck('descripcion', 'campo')
            ->all();

        $estado = $this->tablasControlListas
            ? (FichaEstado::query()
                ->where('inscripcion_id', $alumno->id)
                ->where('ciclo_escolar_id', $this->ciclo_escolar_id)
                ->where('periodo', $this->periodo)
                ->value('estado') ?? 'borrador')
            : 'borrador';

        return view('livewire.accion.ficha-editor', [
            'alumno' => $alumno,
            'resumenCampos' => $resumen,
            'estadoFicha' => $estado,
            'urlPdf' => $this->urlPdfAlumno((int) $alumno->id),
            'urlWord' => $this->urlWordAlumno((int) $alumno->id),
        ]);
    }
}
