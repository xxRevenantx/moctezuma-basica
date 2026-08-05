<?php

namespace App\Livewire\Profesor;

use App\Models\CalificacionEntrega;
use App\Models\CicloEscolar;
use App\Services\TeacherAcademicScopeService;
use Livewire\Component;

class PortalDocente extends Component
{
    public function render(TeacherAcademicScopeService $scope)
    {
        $user = auth()->user();
        $niveles = $scope->assignedLevels($user);
        $cicloActual = CicloEscolar::query()->where('es_actual', true)->first();
        $entregas = CalificacionEntrega::query()
            ->with(['nivel:id,nombre', 'grado:id,nombre', 'grupo.asignacionGrupo:id,nombre'])
            ->where('user_id', $user->id)
            ->latest('confirmada_at')
            ->limit(5)
            ->get();

        return view('livewire.profesor.portal-docente', compact('niveles', 'cicloActual', 'entregas'));
    }
}
