<?php

namespace App\Exports\Periodos;

use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\MesesBachillerato;
use App\Models\MesesBasica;
use App\Models\Nivel;
use App\Models\Parcial;
use App\Models\PeriodosBasica;
use App\Models\Semestre;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class CatalogosPeriodosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Catálogos';
    }

    public function array(): array
    {
        $niveles = Nivel::query()->orderBy('id')->get();
        $ciclos = CicloEscolar::query()->orderByDesc('inicio_anio')->get();
        $generaciones = Generacion::query()
            ->with('nivel:id,nombre')
            ->whereNull('deleted_at')
            ->orderBy('nivel_id')
            ->orderByDesc('anio_ingreso')
            ->get();
        $semestres = Semestre::query()
            ->with('grado:id,nombre')
            ->whereNull('deleted_at')
            ->orderBy('numero')
            ->get();
        $mesesBasica = MesesBasica::query()->orderBy('id')->get();
        $periodosBasica = PeriodosBasica::query()->orderBy('periodo')->get();
        $mesesBachillerato = MesesBachillerato::query()->orderBy('id')->get();
        $parciales = Parcial::query()->orderBy('parcial')->get();

        $filas = [[
            'nivel_id',
            'ciclo_escolar_id',
            'generacion_id',
            'semestre_id',
            'mes_basica_id',
            'periodo_basica_id',
            'mes_bachillerato_id',
            'parcial_bachillerato_id',
        ]];

        $maximo = max(
            $niveles->count(),
            $ciclos->count(),
            $generaciones->count(),
            $semestres->count(),
            $mesesBasica->count(),
            $periodosBasica->count(),
            $mesesBachillerato->count(),
            $parciales->count(),
            1
        );

        for ($i = 0; $i < $maximo; $i++) {
            $nivel = $niveles[$i] ?? null;
            $ciclo = $ciclos[$i] ?? null;
            $generacion = $generaciones[$i] ?? null;
            $semestre = $semestres[$i] ?? null;
            $mesBasica = $mesesBasica[$i] ?? null;
            $periodoBasica = $periodosBasica[$i] ?? null;
            $mesBachillerato = $mesesBachillerato[$i] ?? null;
            $parcial = $parciales[$i] ?? null;

            $filas[] = [
                $nivel ? "{$nivel->id} | {$nivel->nombre}" : '',
                $ciclo ? "{$ciclo->id} | {$ciclo->inicio_anio}-{$ciclo->fin_anio}" : '',
                $generacion
                    ? "{$generacion->id} | {$generacion->nivel?->nombre} {$generacion->anio_ingreso}-{$generacion->anio_egreso}"
                    : '',
                $semestre
                    ? "{$semestre->id} | {$semestre->numero}° semestre"
                    : '',
                $mesBasica ? "{$mesBasica->id} | {$mesBasica->meses}" : '',
                $periodoBasica ? "{$periodoBasica->id} | {$periodoBasica->descripcion}" : '',
                $mesBachillerato ? "{$mesBachillerato->id} | {$mesBachillerato->meses}" : '',
                $parcial ? "{$parcial->id} | {$parcial->descripcion}" : '',
            ];
        }

        return $filas;
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $hoja->freezePane('A2');
                $hoja->setAutoFilter('A1:H1');
                $hoja->getStyle('A1:H1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '88AC2E'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                    ],
                ]);
                $hoja->getStyle('A:H')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
            },
        ];
    }
}
