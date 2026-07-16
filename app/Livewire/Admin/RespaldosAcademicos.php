<?php

namespace App\Livewire\Admin;

use App\Exceptions\RespaldoAcademicoImportException;
use App\Exports\Respaldos\RespaldoAcademicoExport;
use App\Services\RespaldoAcademicoService;
use App\Support\RespaldoAcademico;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Component;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Throwable;

class RespaldosAcademicos extends Component
{
    use WithFileUploads;

    public $archivoAlumnos = null;
    public $archivoCalificaciones = null;

    public bool $confirmarAlumnos = false;
    public bool $confirmarCalificaciones = false;

    /** @var array<string,mixed> */
    public array $resumenAlumnos = [];

    /** @var array<string,mixed> */
    public array $resumenCalificaciones = [];


    /** @var array<string,mixed> */
    public array $vistaPreviaAlumnos = [];

    /** @var array<string,mixed> */
    public array $vistaPreviaCalificaciones = [];

    public ?string $checksumVistaPreviaAlumnos = null;
    public ?string $checksumVistaPreviaCalificaciones = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }


    public function updatedArchivoAlumnos(): void
    {
        $this->reset('vistaPreviaAlumnos', 'checksumVistaPreviaAlumnos', 'confirmarAlumnos');
    }

    public function updatedArchivoCalificaciones(): void
    {
        $this->reset('vistaPreviaCalificaciones', 'checksumVistaPreviaCalificaciones', 'confirmarCalificaciones');
    }

    public function previsualizarAlumnos(): void
    {
        $this->previsualizarTipo(RespaldoAcademico::TIPO_ALUMNOS);
    }

    public function previsualizarCalificaciones(): void
    {
        $this->previsualizarTipo(RespaldoAcademico::TIPO_CALIFICACIONES);
    }

    public function exportarAlumnos()
    {
        abort_unless(auth()->user()?->is_admin, 403);

        try {
            return Excel::download(
                new RespaldoAcademicoExport(RespaldoAcademico::TIPO_ALUMNOS),
                RespaldoAcademico::nombreArchivo(RespaldoAcademico::TIPO_ALUMNOS)
            );
        } catch (Throwable $e) {
            report($e);

            $this->dispatch('swal', [
                'title' => 'No se pudo generar el respaldo de alumnos.',
                'text' => $e->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return null;
        }
    }

    public function exportarCalificaciones()
    {
        abort_unless(auth()->user()?->is_admin, 403);

        try {
            return Excel::download(
                new RespaldoAcademicoExport(RespaldoAcademico::TIPO_CALIFICACIONES),
                RespaldoAcademico::nombreArchivo(RespaldoAcademico::TIPO_CALIFICACIONES)
            );
        } catch (Throwable $e) {
            report($e);

            $this->dispatch('swal', [
                'title' => 'No se pudo generar el respaldo de calificaciones.',
                'text' => $e->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);

            return null;
        }
    }

    public function importarAlumnos(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->validate([
            'archivoAlumnos' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
            'confirmarAlumnos' => ['accepted'],
        ], [
            'archivoAlumnos.required' => 'Selecciona el respaldo de alumnos.',
            'archivoAlumnos.file' => 'El archivo seleccionado no es válido.',
            'archivoAlumnos.mimes' => 'El respaldo debe ser un archivo Excel .xlsx o .xls.',
            'archivoAlumnos.max' => 'El archivo no debe pesar más de 50 MB.',
            'confirmarAlumnos.accepted' => 'Confirma que comprendes el alcance de la importación.',
        ]);

        try {
            $ruta = $this->archivoAlumnos->getRealPath();
            $checksum = hash_file('sha256', $ruta);

            if ($this->vistaPreviaAlumnos === [] || $this->checksumVistaPreviaAlumnos !== $checksum) {
                $this->addError('archivoAlumnos', 'Genera y revisa la vista previa antes de importar este archivo.');
                return;
            }

            $servicio = app(RespaldoAcademicoService::class);

            $this->resumenAlumnos = $servicio->importar(
                tipo: RespaldoAcademico::TIPO_ALUMNOS,
                rutaArchivo: $ruta,
                usuarioId: auth()->id(),
            );

            $this->reset('archivoAlumnos', 'confirmarAlumnos', 'vistaPreviaAlumnos', 'checksumVistaPreviaAlumnos');

            $this->dispatch('swal', [
                'title' => 'Alumnos importados correctamente.',
                'text' => 'Los IDs originales se conservaron y no se eliminó ningún registro.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (RespaldoAcademicoImportException $e) {
            $this->addError('archivoAlumnos', $e->getMessage());

            $this->dispatch('swal', [
                'title' => 'El respaldo de alumnos no pudo importarse.',
                'text' => $e->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } catch (Throwable $e) {
            report($e);
            $mensaje = 'Ocurrió un error inesperado. No se guardó ningún cambio.';
            $this->addError('archivoAlumnos', $mensaje);

            $this->dispatch('swal', [
                'title' => 'No se pudo importar el respaldo de alumnos.',
                'text' => $mensaje,
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }

    public function importarCalificaciones(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        $this->validate([
            'archivoCalificaciones' => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
            'confirmarCalificaciones' => ['accepted'],
        ], [
            'archivoCalificaciones.required' => 'Selecciona el respaldo de calificaciones.',
            'archivoCalificaciones.file' => 'El archivo seleccionado no es válido.',
            'archivoCalificaciones.mimes' => 'El respaldo debe ser un archivo Excel .xlsx o .xls.',
            'archivoCalificaciones.max' => 'El archivo no debe pesar más de 50 MB.',
            'confirmarCalificaciones.accepted' => 'Confirma que comprendes el alcance de la importación.',
        ]);

        try {
            $ruta = $this->archivoCalificaciones->getRealPath();
            $checksum = hash_file('sha256', $ruta);

            if ($this->vistaPreviaCalificaciones === [] || $this->checksumVistaPreviaCalificaciones !== $checksum) {
                $this->addError('archivoCalificaciones', 'Genera y revisa la vista previa antes de importar este archivo.');
                return;
            }

            $servicio = app(RespaldoAcademicoService::class);

            $this->resumenCalificaciones = $servicio->importar(
                tipo: RespaldoAcademico::TIPO_CALIFICACIONES,
                rutaArchivo: $ruta,
                usuarioId: auth()->id(),
            );

            $this->reset('archivoCalificaciones', 'confirmarCalificaciones', 'vistaPreviaCalificaciones', 'checksumVistaPreviaCalificaciones');

            $this->dispatch('swal', [
                'title' => 'Calificaciones importadas correctamente.',
                'text' => 'Los IDs originales se conservaron y la bitácora fue restaurada.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (RespaldoAcademicoImportException $e) {
            $this->addError('archivoCalificaciones', $e->getMessage());

            $this->dispatch('swal', [
                'title' => 'El respaldo de calificaciones no pudo importarse.',
                'text' => $e->getMessage(),
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        } catch (Throwable $e) {
            report($e);
            $mensaje = 'Ocurrió un error inesperado. No se guardó ningún cambio.';
            $this->addError('archivoCalificaciones', $mensaje);

            $this->dispatch('swal', [
                'title' => 'No se pudo importar el respaldo de calificaciones.',
                'text' => $mensaje,
                'icon' => 'error',
                'position' => 'top-end',
            ]);
        }
    }


    private function previsualizarTipo(string $tipo): void
    {
        abort_unless(auth()->user()?->is_admin || auth()->user()?->canAccess('respaldos.gestionar'), 403);

        $propiedadArchivo = $tipo === RespaldoAcademico::TIPO_ALUMNOS
            ? 'archivoAlumnos'
            : 'archivoCalificaciones';
        $propiedadResumen = $tipo === RespaldoAcademico::TIPO_ALUMNOS
            ? 'vistaPreviaAlumnos'
            : 'vistaPreviaCalificaciones';
        $propiedadChecksum = $tipo === RespaldoAcademico::TIPO_ALUMNOS
            ? 'checksumVistaPreviaAlumnos'
            : 'checksumVistaPreviaCalificaciones';

        $this->validate([
            $propiedadArchivo => ['required', 'file', 'mimes:xlsx,xls', 'max:51200'],
        ], [
            $propiedadArchivo.'.required' => 'Selecciona primero un archivo de respaldo.',
            $propiedadArchivo.'.mimes' => 'El respaldo debe ser un archivo Excel .xlsx o .xls.',
            $propiedadArchivo.'.max' => 'El archivo no debe pesar más de 50 MB.',
        ]);

        try {
            $archivo = $this->{$propiedadArchivo};
            $ruta = $archivo->getRealPath();
            $this->{$propiedadResumen} = app(RespaldoAcademicoService::class)->previsualizar($tipo, $ruta);
            $this->{$propiedadChecksum} = hash_file('sha256', $ruta);

            $this->dispatch('swal', [
                'title' => 'Vista previa lista.',
                'text' => 'No se guardó ningún cambio. Revisa los totales y después confirma la importación.',
                'icon' => 'success',
                'position' => 'top-end',
            ]);
        } catch (RespaldoAcademicoImportException $e) {
            $this->addError($propiedadArchivo, $e->getMessage());
            $this->{$propiedadResumen} = [];
            $this->{$propiedadChecksum} = null;
        } catch (Throwable $e) {
            report($e);
            $this->addError($propiedadArchivo, 'No se pudo analizar el archivo. No se guardó ningún cambio.');
            $this->{$propiedadResumen} = [];
            $this->{$propiedadChecksum} = null;
        }
    }

    public function render()
    {
        return view('livewire.admin.respaldos-academicos', [
            'estadisticas' => [
                'tutores' => $this->contar('tutores'),
                'alumnos' => $this->contar('inscripciones'),
                'alumnos_activos' => Schema::hasTable('inscripciones')
                    ? DB::table('inscripciones')
                        ->where('activo', true)
                        ->when(
                            Schema::hasColumn('inscripciones', 'deleted_at'),
                            fn ($query) => $query->whereNull('deleted_at')
                        )
                        ->count()
                    : 0,
                'cambios_academicos' => $this->contar('cambios_academicos'),
                'matriculas' => $this->contar('matriculas_alumnos'),
                'movimientos' => $this->contar('movimientos_alumnos'),
                'calificaciones' => $this->contar('calificaciones'),
                'bitacora_calificaciones' => $this->contar('bitacora_calificaciones'),
            ],
        ]);
    }

    private function contar(string $tabla): int
    {
        return Schema::hasTable($tabla) ? DB::table($tabla)->count() : 0;
    }
}
