<?php

namespace App\Exports\Inscripciones;

use App\Models\Ciclo;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Nivel;
use App\Models\Semestre;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CatalogosInscripcionesSheet implements FromArray, ShouldAutoSize, WithTitle, WithEvents
{
    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        $filas = [];

        $filas[] = [
            'ciclo_escolar_id',
            'ciclo_escolar',
            '',
            'nivel_id',
            'nivel',
            '',
            'grado_id',
            'grado',
            '',
            'generacion_id',
            'generacion',
            '',
            'grupo_id',
            'grupo',
            'grupo_nivel',
            'grupo_grado',
            'semestre_id',
            'semestre',
            '',
            'ciclo_id',
            'periodo_inscripcion',
        ];

        $ciclosEscolares = CicloEscolar::query()
            ->orderByDesc('inicio_anio')
            ->orderByDesc('fin_anio')
            ->get();

        $niveles = Nivel::query()
            ->orderBy('id')
            ->get(['id', 'nombre', 'slug']);

        $grados = Grado::query()
            ->with('nivel:id,nombre')
            ->orderBy('nivel_id')
            ->orderBy('orden')
            ->orderBy('id')
            ->get();

        $generaciones = Generacion::query()
            ->with('nivel:id,nombre')
            ->orderBy('nivel_id')
            ->orderByDesc('anio_ingreso')
            ->get();

        $grupos = Grupo::query()
            ->with([
                'asignacionGrupo:id,nombre',
                'nivel:id,nombre,slug',
                'grado:id,nombre,orden',
                'generacion:id,anio_ingreso,anio_egreso',
                'semestre:id,numero',
            ])
            ->leftJoin('asignacion_grupos', 'asignacion_grupos.id', '=', 'grupos.asignacion_grupo_id')
            ->select('grupos.*')
            ->orderBy('grupos.nivel_id')
            ->orderBy('grupos.grado_id')
            ->orderBy('grupos.generacion_id')
            ->orderBy('grupos.semestre_id')
            ->orderBy('asignacion_grupos.nombre')
            ->orderBy('grupos.id')
            ->get();

        $semestres = Semestre::query()
            ->with('grado:id,nombre')
            ->orderBy('numero')
            ->orderBy('id')
            ->get();

        $ciclos = Ciclo::query()
            ->orderBy('id')
            ->get();

        $maximo = max(
            $ciclosEscolares->count(),
            $niveles->count(),
            $grados->count(),
            $generaciones->count(),
            $grupos->count(),
            $semestres->count(),
            $ciclos->count(),
            1
        );

        for ($i = 0; $i < $maximo; $i++) {
            $cicloEscolar = $ciclosEscolares[$i] ?? null;
            $nivel = $niveles[$i] ?? null;
            $grado = $grados[$i] ?? null;
            $generacion = $generaciones[$i] ?? null;
            $grupo = $grupos[$i] ?? null;
            $semestre = $semestres[$i] ?? null;
            $ciclo = $ciclos[$i] ?? null;

            $filas[] = [
                $cicloEscolar?->id,
                $cicloEscolar ? "{$cicloEscolar->inicio_anio} - {$cicloEscolar->fin_anio}" : '',
                '',

                $nivel?->id,
                $nivel?->nombre,
                '',

                $grado?->id,
                $grado ? $this->textoGrado($grado) : '',
                '',

                $generacion?->id,
                $generacion ? $this->textoGeneracion($generacion) : '',
                '',

                $grupo?->id,
                $grupo ? $this->textoGrupo($grupo) : '',
                $grupo?->nivel?->nombre,
                $grupo?->grado?->nombre,

                $semestre?->id,
                $semestre ? $this->textoSemestre($semestre) : '',
                '',

                $ciclo?->id,
                $ciclo?->ciclo,
            ];
        }

        return $filas;
    }

    private function textoGrado($grado): string
    {
        $nivel = $grado->nivel?->nombre;

        return trim(($nivel ? "{$nivel} · " : '') . $grado->nombre);
    }

    private function textoGeneracion($generacion): string
    {
        $nivel = $generacion->nivel?->nombre;
        $texto = "{$generacion->anio_ingreso} - {$generacion->anio_egreso}";

        return trim(($nivel ? "{$nivel} · " : '') . $texto);
    }

    private function textoGrupo($grupo): string
    {
        $nombreGrupo = $grupo->asignacionGrupo?->nombre ?? 'Sin grupo';

        $partes = [
            $nombreGrupo,
            $grupo->grado?->nombre,
            $grupo->generacion ? "{$grupo->generacion->anio_ingreso}-{$grupo->generacion->anio_egreso}" : null,
        ];

        if ($grupo->semestre) {
            $partes[] = 'Semestre ' . $grupo->semestre->numero;
        }

        return implode(' · ', array_filter($partes));
    }

    private function textoSemestre($semestre): string
    {
        $grado = $semestre->grado?->nombre;

        return trim(($grado ? "{$grado} · " : '') . 'Semestre ' . $semestre->numero);
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $hoja = $event->sheet->getDelegate();

                $hoja->freezePane('A2');

                $hoja->getStyle('A1:U1')->applyFromArray([
                    'font' => [
                        'bold' => true,
                        'color' => ['rgb' => 'FFFFFF'],
                    ],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                ]);

                $hoja->getStyle('A:U')->getAlignment()->setVertical('center');
            },
        ];
    }
}
