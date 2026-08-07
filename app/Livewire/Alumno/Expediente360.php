<?php

namespace App\Livewire\Alumno;

use App\Services\Expediente360AlumnoService;
use Livewire\Component;

class Expediente360 extends Component
{
    public int $inscripcionId;
    public string $seccion = 'resumen';
    /** @var array<string, mixed> */
    public array $expediente = [];

    public function mount(int $inscripcion): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);

        $this->inscripcionId = $inscripcion;
        $this->cargar();
    }

    public function cambiarSeccion(string $seccion): void
    {
        $permitidas = ['resumen', 'personales', 'responsables', 'academico', 'calificaciones', 'documentos', 'movimientos', 'seguimiento'];
        $this->seccion = in_array($seccion, $permitidas, true) ? $seccion : 'resumen';
    }

    public function actualizarExpediente(): void
    {
        $this->cargar();
        $this->dispatch('notify', type: 'success', message: 'Expediente 360° actualizado con la información más reciente.');
    }

    public function abrirTrayectoria(): void
    {
        $this->dispatch('abrir-linea-tiempo-academica', alumnoId: $this->inscripcionId);
    }

    public function claseEstatus(string $estatus): string
    {
        return match ($estatus) {
            'activo', 'reingreso' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
            'egresado' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950/50 dark:text-indigo-300',
            'preinscrito', 'pendiente_reinscripcion' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
            'archivado', 'inactivo', 'no_reinscrito' => 'bg-slate-200 text-slate-700 dark:bg-neutral-800 dark:text-slate-300',
            default => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
        };
    }

    public function claseDocumento(string $estado): string
    {
        return match ($estado) {
            'validado', 'emitida' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300',
            'recibido' => 'bg-sky-100 text-sky-700 dark:bg-sky-950/50 dark:text-sky-300',
            'pendiente' => 'bg-amber-100 text-amber-700 dark:bg-amber-950/50 dark:text-amber-300',
            'rechazado', 'cancelada' => 'bg-rose-100 text-rose-700 dark:bg-rose-950/50 dark:text-rose-300',
            default => 'bg-slate-100 text-slate-600 dark:bg-neutral-800 dark:text-slate-300',
        };
    }

    public function claseRiesgo(?string $nivel): string
    {
        return match ($nivel) {
            'moderado' => 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/60 dark:bg-amber-950/30 dark:text-amber-300',
            'alto' => 'border-orange-200 bg-orange-50 text-orange-700 dark:border-orange-900/60 dark:bg-orange-950/30 dark:text-orange-300',
            'critico' => 'border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-900/60 dark:bg-rose-950/30 dark:text-rose-300',
            default => 'border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-900/60 dark:bg-emerald-950/30 dark:text-emerald-300',
        };
    }

    public function render()
    {
        return view('livewire.alumno.expediente-360', [
            'puedeEditar' => (bool) auth()->user()?->canAccess('alumnos.editar'),
            'puedeDocumentos' => (bool) auth()->user()?->canAccess('documentos.organizar'),
            'puedeSeguimiento' => (bool) auth()->user()?->canAccess('seguimiento.consultar'),
        ]);
    }

    private function cargar(): void
    {
        $this->expediente = app(Expediente360AlumnoService::class)->construir($this->inscripcionId);
    }
}
