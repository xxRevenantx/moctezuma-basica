<?php

namespace App\Livewire\Alumno;

use App\Services\LineaTiempoAcademicaAlumnoService;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

class LineaTiempoAcademica extends Component
{
    public bool $abierto = false;

    /** @var array<string, mixed>|null */
    public ?array $trayectoria = null;

    #[On('abrir-linea-tiempo-academica')]
    public function abrir(int $alumnoId): void
    {
        abort_unless(auth()->user()?->canAccess('alumnos.consultar'), 403);

        $this->reset('trayectoria');

        try {
            $this->trayectoria = app(LineaTiempoAcademicaAlumnoService::class)->construir($alumnoId);
            $this->abierto = true;
        } catch (Throwable $exception) {
            report($exception);

            $this->abierto = false;
            $this->trayectoria = null;
            $this->dispatch(
                'notify',
                type: 'error',
                message: 'No fue posible construir la trayectoria académica del alumno.'
            );
        }
    }

    public function cerrar(): void
    {
        $this->abierto = false;
        $this->trayectoria = null;
    }

    public function render()
    {
        return view('livewire.alumno.linea-tiempo-academica');
    }
}
