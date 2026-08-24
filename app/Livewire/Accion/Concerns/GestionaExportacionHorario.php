<?php

namespace App\Livewire\Accion\Concerns;

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Dia;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Hora;
use App\Models\Horario as HorarioModel;
use App\Models\HorarioDocenteConfiguracion;
use App\Models\Materia;
use App\Models\Nivel;
use App\Models\Semestre;
use App\Models\TallerSesion;
use App\Services\CicloNivelGateService;
use App\Services\ContextoEscolarService;
use App\Services\GroqHorarioService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;
use Livewire\Component;
use App\Exports\HorarioExport;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

trait GestionaExportacionHorario
{
    public function exportarHorario()
    {
        if (!$this->puedeDescargarHorario) {
            $this->dispatch('swal', [
                'title' => 'Selecciona todos los filtros antes de exportar el horario.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            return null;
        }

        $grupo = $this->obtenerGrupoSeleccionado();

        if (!$grupo) {
            $this->dispatch('swal', [
                'title' => 'El grupo ya no pertenece al ciclo y contexto seleccionados.',
                'icon' => 'warning',
                'position' => 'top-end',
            ]);

            $this->cargarGrupos();

            return null;
        }

        $nombreNivel = mb_strtoupper($this->nivel?->nombre ?? $this->slug_nivel ?? 'NIVEL');

        $nombreGeneracion = Generacion::query()
            ->where('id', $this->generacion_id)
            ->select('anio_ingreso', 'anio_egreso')
            ->first();

        $textoGeneracion = $nombreGeneracion
            ? $nombreGeneracion->anio_ingreso . '_' . $nombreGeneracion->anio_egreso
            : 'SIN_GENERACION';

        $nombreGrado = Grado::query()
            ->where('id', $this->grado_id)
            ->value('nombre') ?? 'GRADO';

        $nombreGrupo = $this->textoGrupo($grupo);

        $textoSemestre = '';

        if ($this->esBachillerato) {
            $semestre = Semestre::query()
                ->where('id', $this->semestre_id)
                ->value('numero');

            $textoSemestre = '_SEMESTRE_' . Str::slug((string) ($semestre ?? $this->semestre_id), '_');
        }

        $nombreArchivo = 'HORARIO_' .
            Str::slug($nombreNivel, '_') .
            '_GENERACION_' . Str::slug($textoGeneracion, '_') .
            '_GRADO_' . Str::slug($nombreGrado, '_') .
            '_GRUPO_' . Str::slug($nombreGrupo, '_') .
            $textoSemestre .
            '.xlsx';

        return Excel::download(
            new HorarioExport(
                nivel_id: $this->nivel?->id ? (int) $this->nivel->id : null,
                grado_id: $this->grado_id ? (int) $this->grado_id : null,
                grupo_id: $this->grupo_id ? (int) $this->grupo_id : null,
                generacion_id: $this->generacion_id ? (int) $this->generacion_id : null,
                semestre_id: $this->semestre_id ? (int) $this->semestre_id : null,
                esBachillerato: $this->esBachillerato,
                ciclo_escolar_id: $this->ciclo_escolar_id ? (int) $this->ciclo_escolar_id : null,
            ),
            $nombreArchivo
        );
    }

}
