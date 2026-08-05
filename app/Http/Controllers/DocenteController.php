<?php

namespace App\Http\Controllers;

use App\Models\Nivel;
use App\Services\TeacherAcademicScopeService;
use Illuminate\Contracts\View\View;

class DocenteController extends Controller
{
    public function horario(TeacherAcademicScopeService $scope): View
    {
        $scope->personaIdOrFail(auth()->user());

        return view('docente.horario');
    }

    public function calificaciones(string $slug_nivel, TeacherAcademicScopeService $scope): View
    {
        $user = auth()->user();
        $scope->personaIdOrFail($user);

        $nivel = Nivel::query()->where('slug', $slug_nivel)->firstOrFail();
        abort_if($nivel->slug === 'preescolar', 404);
        abort_unless(
            $scope->assignedLevels($user)->contains(fn ($item): bool => (int) $item->id === (int) $nivel->id),
            403,
            'No tienes materias asignadas en este nivel.'
        );

        return view('docente.calificaciones', compact('nivel'));
    }

    public function fichas(TeacherAcademicScopeService $scope): View
    {
        $user = auth()->user();
        $scope->personaIdOrFail($user);

        abort_unless(
            $scope->hasCurrentPreschoolContext($user),
            403,
            'Las fichas descriptivas están reservadas para la docente titular de un grupo activo de preescolar.'
        );

        return view('docente.fichas');
    }
}
