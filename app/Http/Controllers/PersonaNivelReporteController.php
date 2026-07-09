<?php

namespace App\Http\Controllers;

use App\Exports\PersonaNivelReporteExport;
use App\Models\PersonaNivelDetalle;
use App\Models\PersonaNivelHistorial;
use App\Models\TipoDocumentoPersonal;
use App\Services\CargaLaboralPersonaNivelService;
use App\Services\ExpedientePersonalResumenService;
use Barryvdh\DomPDF\Facade\Pdf;
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

        [$titulo, $encabezados, $filas] = $tipo === 'historial'
            ? $this->datosHistorial($request)
            : $this->datosPlantilla($tipo, $cargaService, $expedienteService, $request);

        $nombre = $tipo . '_personal_' . now()->format('Y_m_d_H_i_s');

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
        ])->setPaper('letter', 'landscape')->download($nombre . '.pdf');
    }

    private function datosPlantilla(
        string $tipo,
        CargaLaboralPersonaNivelService $cargaService,
        ExpedientePersonalResumenService $expedienteService,
        Request $request,
    ): array {
        $detalles = PersonaNivelDetalle::query()
            ->with([
                'cabecera.persona', 'cabecera.nivel', 'personaRole.rolePersona', 'grado',
                'grupo.asignacionGrupo', 'asignacionMateria.materia',
                'asignacionMateria.horarios.hora', 'actividadAdministrativa',
            ])
            ->when($request->filled('nivel_id'), fn ($query) => $query->whereHas('cabecera', fn ($cabecera) => $cabecera->where('nivel_id', $request->integer('nivel_id'))))
            ->when($request->filled('persona_id'), fn ($query) => $query->whereHas('cabecera', fn ($cabecera) => $cabecera->where('persona_id', $request->integer('persona_id'))))
            ->when($request->filled('grado_id'), fn ($query) => $query->where('grado_id', $request->integer('grado_id')))
            ->when($request->filled('grupo_id'), fn ($query) => $query->where('grupo_id', $request->integer('grupo_id')))
            ->orderBy('persona_nivel_id')
            ->orderBy('orden')
            ->get();

        $tiposDocumento = TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'es_obligatorio']);

        if ($tipo === 'carga') {
            $cargasCabecera = $detalles
                ->groupBy('persona_nivel_id')
                ->map(fn (Collection $items) => $cargaService->calcularCabecera($items));

            $titulo = 'Carga laboral del personal por nivel';
            $encabezados = [
                'No.', 'Personal', 'Nivel', 'Función', 'Grado', 'Grupo', 'Materia / actividad',
                'Horas automáticas', 'Ajuste', 'Frente a grupo', 'Administrativas', 'Total',
                'Límite', 'Alerta', 'Estado',
            ];

            $filas = $detalles->values()->map(function (PersonaNivelDetalle $detalle, int $index) use ($cargaService, $cargasCabecera) {
                $carga = $cargaService->calcular($detalle);
                $global = $cargasCabecera->get($detalle->persona_nivel_id, $carga);

                return [
                    $index + 1,
                    $this->nombrePersona($detalle),
                    $detalle->cabecera?->nivel?->nombre ?? '—',
                    $detalle->personaRole?->rolePersona?->nombre ?? '—',
                    $detalle->grado?->nombre ?? '—',
                    $detalle->grupo?->asignacionGrupo?->nombre ?? '—',
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

        $titulo = 'Plantilla general de personal por nivel';
        $encabezados = [
            'No.', 'Personal', 'Nivel', 'Función', 'Grado', 'Grupo', 'Titular',
            'Materia', 'Inicio', 'Término', 'Estado', 'Expediente', 'Documentos faltantes',
            'Grado de estudios', 'Especialidad',
        ];

        $filas = $detalles->values()->map(function (PersonaNivelDetalle $detalle, int $index) use ($expedientesPorPersona) {
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
                $detalle->es_titular_principal ? 'Principal' : ($detalle->es_titular ? 'Auxiliar' : 'No'),
                $detalle->nombreMateria() ?? '—',
                optional($detalle->fecha_inicio)->format('d/m/Y') ?? '—',
                optional($detalle->fecha_fin)->format('d/m/Y') ?? 'Vigente',
                ucfirst($detalle->estado),
                $expediente['porcentaje'] . '%',
                implode(', ', $expediente['faltantes']) ?: 'Ninguno',
                $persona?->grado_estudios ?? '—',
                $persona?->especialidad ?? '—',
            ];
        });

        return [$titulo, $encabezados, $filas];
    }

    private function datosHistorial(Request $request): array
    {
        $titulo = 'Historial de movimientos de la plantilla';
        $encabezados = ['No.', 'Fecha', 'Personal', 'Nivel', 'Acción', 'Descripción', 'Usuario'];

        $filas = PersonaNivelHistorial::query()
            ->with(['persona', 'nivel', 'usuario'])
            ->when($request->filled('nivel_id'), fn ($query) => $query->where('nivel_id', $request->integer('nivel_id')))
            ->when($request->filled('persona_id'), fn ($query) => $query->where('persona_id', $request->integer('persona_id')))
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
