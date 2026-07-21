<?php

namespace App\Http\Controllers;

use App\Exports\PersonaNivelReporteExport;
use App\Models\CicloEscolar;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaNivelHistorial;
use App\Models\TipoDocumentoPersonal;
use App\Services\CargaLaboralPersonaNivelService;
use App\Services\ExpedientePersonalResumenService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;

class PersonaNivelReporteController extends Controller
{
    public function __invoke(
        Request $request,
        string $tipo,
        string $formato,
        CargaLaboralPersonaNivelService $cargaService,
        ExpedientePersonalResumenService $expedienteService,
    ) {
        abort_unless(in_array($tipo, ['plantilla', 'carga', 'historial'], true), 404);
        abort_unless(in_array($formato, ['pdf', 'excel'], true), 404);

        $ciclo = $this->resolverCiclo($request);

        [$titulo, $encabezados, $filas] = $tipo === 'historial'
            ? $this->datosHistorial($request, $ciclo)
            : $this->datosPlantilla($tipo, $cargaService, $expedienteService, $request, $ciclo);

        $nombre = $tipo . '_personal_' . str_replace('-', '_', $ciclo->nombre) . '_' . now()->format('Y_m_d_H_i_s');

        if ($formato === 'excel') {
            return Excel::download(
                new PersonaNivelReporteExport($filas, $encabezados, $titulo),
                $nombre . '.xlsx'
            );
        }

        return Pdf::loadView('pdf.persona-nivel-reporte', [
            'titulo' => $titulo,
            'encabezados' => $encabezados,
            'filas' => $filas,
            'tipo' => $tipo,
            'cicloEscolar' => $ciclo,
        ])->setPaper('letter', 'landscape')->download($nombre . '.pdf');
    }

    private function resolverCiclo(Request $request): CicloEscolar
    {
        $cicloId = $request->integer('ciclo_escolar_id');

        return CicloEscolar::query()
            ->when($cicloId > 0, fn (Builder $query) => $query->whereKey($cicloId))
            ->when($cicloId <= 0, fn (Builder $query) => $query->where('es_actual', true))
            ->first()
            ?? CicloEscolar::query()->orderByDesc('inicio_anio')->firstOrFail();
    }

    private function datosPlantilla(
        string $tipo,
        CargaLaboralPersonaNivelService $cargaService,
        ExpedientePersonalResumenService $expedienteService,
        Request $request,
        CicloEscolar $ciclo,
    ): array {
        $detalles = PersonaNivelDetalle::query()
            ->whereNull('archivado_at')
            ->whereHas('cicloAsignacion.plantilla', fn (Builder $plantilla) => $plantilla
                ->where('ciclo_escolar_id', $ciclo->id))
            ->with([
                'cabecera.persona', 'cabecera.nivel', 'cicloAsignacion.plantilla.cicloEscolar',
                'personaRole.rolePersona', 'grado', 'grupo.asignacionGrupo', 'grupo.generacion',
                'asignacionMateria.materia', 'asignacionMateria.horarios.hora',
                'actividadAdministrativa',
            ])
            ->when($request->filled('nivel_id'), fn (Builder $query) => $query->whereHas(
                'cabecera',
                fn (Builder $cabecera) => $cabecera->where('nivel_id', $request->integer('nivel_id'))
            ))
            ->when($request->filled('persona_id'), fn (Builder $query) => $query->whereHas(
                'cabecera',
                fn (Builder $cabecera) => $cabecera->where('persona_id', $request->integer('persona_id'))
            ))
            ->when($request->filled('grado_id'), fn (Builder $query) => $query->where('grado_id', $request->integer('grado_id')))
            ->when($request->filled('grupo_id'), fn (Builder $query) => $query->where('grupo_id', $request->integer('grupo_id')))
            ->get()
            ->sortBy(fn (PersonaNivelDetalle $detalle) => sprintf(
                '%06d-%06d-%06d-%010d',
                (int) ($detalle->cabecera?->nivel_id ?? 999999),
                (int) ($detalle->cicloAsignacion?->orden ?? 999999),
                (int) ($detalle->orden ?? 999999),
                (int) $detalle->id,
            ))
            ->values();

        $tiposDocumento = TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'es_obligatorio']);

        if ($tipo === 'carga') {
            $cargasCabecera = $detalles
                ->groupBy('persona_nivel_ciclo_id')
                ->map(fn (Collection $items) => $cargaService->calcularCabecera($items));

            $titulo = "Carga laboral del personal por nivel · Ciclo {$ciclo->nombre}";
            $encabezados = [
                'No.', 'Personal', 'Nivel', 'Función', 'Grado', 'Grupo', 'Generación',
                'Materia / actividad', 'Horas automáticas', 'Ajuste', 'Frente a grupo',
                'Administrativas', 'Total', 'Límite', 'Alerta', 'Estado',
            ];

            $filas = $detalles->map(function (PersonaNivelDetalle $detalle, int $index) use ($cargaService, $cargasCabecera) {
                $carga = $cargaService->calcular($detalle);
                $global = $cargasCabecera->get($detalle->persona_nivel_ciclo_id, $carga);

                return [
                    $index + 1,
                    $this->nombrePersona($detalle),
                    $detalle->cabecera?->nivel?->nombre ?? '—',
                    $detalle->personaRole?->rolePersona?->nombre ?? '—',
                    $detalle->grado?->nombre ?? '—',
                    $detalle->grupo?->asignacionGrupo?->nombre ?? '—',
                    $detalle->grupo?->generacion?->nombre ?? '—',
                    $detalle->nombreMateria() ?? $detalle->actividadAdministrativa?->nombre ?? $detalle->actividad_administrativa_manual ?? '—',
                    $carga['horas_automaticas'],
                    $carga['ajuste'],
                    $carga['horas_frente_grupo'],
                    $global['horas_administrativas'],
                    $global['total'],
                    $global['limite'],
                    $global['sobrecarga'] ? 'SOBRECARGA' : 'Normal',
                    ucfirst($detalle->estado),
                ];
            });

            return [$titulo, $encabezados, $filas];
        }

        $expedientesPorPersona = $detalles
            ->pluck('cabecera.persona_id')
            ->filter()
            ->unique()
            ->mapWithKeys(fn ($personaId) => [
                (int) $personaId => $expedienteService->paraPersona((int) $personaId, $tiposDocumento),
            ]);

        $titulo = "Plantilla general de personal por nivel · Ciclo {$ciclo->nombre}";
        $encabezados = [
            'No.', 'Personal', 'Nivel', 'Función', 'Grado', 'Grupo', 'Generación',
            'Titular', 'Materia', 'Inicio', 'Término', 'Estado', 'Confirmada',
            'Expediente', 'Documentos faltantes', 'Grado de estudios', 'Especialidad',
        ];

        $filas = $detalles->map(function (PersonaNivelDetalle $detalle, int $index) use ($expedientesPorPersona) {
            $persona = $detalle->cabecera?->persona;
            $expediente = $persona
                ? $expedientesPorPersona->get((int) $persona->id)
                : ['porcentaje' => 0, 'faltantes' => []];

            return [
                $index + 1,
                $this->nombrePersona($detalle),
                $detalle->cabecera?->nivel?->nombre ?? '—',
                $detalle->personaRole?->rolePersona?->nombre ?? '—',
                $detalle->grado?->nombre ?? '—',
                $detalle->grupo?->asignacionGrupo?->nombre ?? '—',
                $detalle->grupo?->generacion?->nombre ?? '—',
                $detalle->es_titular_principal ? 'Principal' : ($detalle->es_titular ? 'Auxiliar' : 'No'),
                $detalle->nombreMateria() ?? '—',
                optional($detalle->fecha_inicio)->format('d/m/Y') ?? '—',
                optional($detalle->fecha_fin)->format('d/m/Y') ?? 'Vigente',
                ucfirst($detalle->estado),
                $detalle->confirmado ? 'Sí' : 'Pendiente',
                $expediente['porcentaje'] . '%',
                implode(', ', $expediente['faltantes']) ?: 'Ninguno',
                $persona?->grado_estudios ?? '—',
                $persona?->especialidad ?? '—',
            ];
        });

        return [$titulo, $encabezados, $filas];
    }

    private function datosHistorial(Request $request, CicloEscolar $ciclo): array
    {
        $titulo = "Historial de movimientos de la plantilla · Ciclo {$ciclo->nombre}";
        $encabezados = ['No.', 'Fecha', 'Personal', 'Nivel', 'Acción', 'Descripción', 'Usuario'];

        $filas = PersonaNivelHistorial::query()
            ->with(['persona', 'nivel', 'usuario'])
            ->where(function (Builder $query) use ($ciclo) {
                $query->where('datos_anteriores->_ciclo_escolar_id', $ciclo->id)
                    ->orWhere('datos_nuevos->_ciclo_escolar_id', $ciclo->id)
                    ->orWhereHas('detalle.cicloAsignacion.plantilla', fn (Builder $plantilla) => $plantilla
                        ->where('ciclo_escolar_id', $ciclo->id));
            })
            ->when($request->filled('nivel_id'), fn (Builder $query) => $query->where('nivel_id', $request->integer('nivel_id')))
            ->when($request->filled('persona_id'), fn (Builder $query) => $query->where('persona_id', $request->integer('persona_id')))
            ->latest('fecha')
            ->get()
            ->values()
            ->map(function (PersonaNivelHistorial $movimiento, int $index) {
                $persona = trim(
                    ($movimiento->persona?->nombre ?? '') . ' ' .
                    ($movimiento->persona?->apellido_paterno ?? '') . ' ' .
                    ($movimiento->persona?->apellido_materno ?? '')
                );

                return [
                    $index + 1,
                    optional($movimiento->fecha)->format('d/m/Y H:i') ?? '—',
                    $persona ?: 'Registro eliminado',
                    $movimiento->nivel?->nombre ?? '—',
                    str_replace('_', ' ', ucfirst($movimiento->accion)),
                    $movimiento->descripcion ?? '—',
                    $movimiento->usuario?->name ?? 'Sistema',
                ];
            });

        return [$titulo, $encabezados, $filas];
    }

    private function nombrePersona(PersonaNivelDetalle $detalle): string
    {
        $persona = $detalle->cabecera?->persona;

        return trim(
            (($persona?->titulo ?? '') ? $persona->titulo . ' ' : '') .
            ($persona?->nombre ?? '') . ' ' .
            ($persona?->apellido_paterno ?? '') . ' ' .
            ($persona?->apellido_materno ?? '')
        ) ?: 'Sin nombre';
    }
}
