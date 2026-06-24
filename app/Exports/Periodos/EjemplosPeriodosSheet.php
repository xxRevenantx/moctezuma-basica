<?php

namespace App\Exports\Periodos;

use App\Models\CicloEscolar;
use App\Models\Generacion;
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

class EjemplosPeriodosSheet implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    public function title(): string
    {
        return 'Ejemplos';
    }

    public function array(): array
    {
        $ciclo = CicloEscolar::query()->orderByDesc('inicio_anio')->first();
        $nivelBasica = Nivel::query()->where('slug', '!=', 'bachillerato')->orderBy('id')->first();
        $nivelBachillerato = Nivel::query()->where('slug', 'bachillerato')->first();
        $mesBasica = MesesBasica::query()->orderBy('id')->first();
        $periodoBasica = PeriodosBasica::query()->orderBy('periodo')->first();
        $generacion = $nivelBachillerato
            ? Generacion::query()->where('nivel_id', $nivelBachillerato->id)->orderByDesc('anio_ingreso')->first()
            : null;
        $semestre = Semestre::query()->with('mesesBachillerato')->orderBy('numero')->first();
        $parcial = Parcial::query()->orderBy('parcial')->first();

        return [
            ['EJEMPLOS DE LLENADO. ESTA HOJA NO SE IMPORTA. COPIA SOLO LOS DATOS NECESARIOS A LA HOJA “Periodos”.'],
            [],
            [
                'tipo',
                'nivel_id',
                'ciclo_escolar_id',
                'generacion_id',
                'semestre_id',
                'mes_basica_id',
                'periodo_basica_id',
                'mes_bachillerato_id',
                'parcial_bachillerato_id',
                'fecha_inicio',
                'fecha_fin',
            ],
            [
                'BASICA',
                $nivelBasica ? "{$nivelBasica->id} | {$nivelBasica->nombre}" : '',
                $ciclo ? "{$ciclo->id} | {$ciclo->inicio_anio}-{$ciclo->fin_anio}" : '',
                '',
                '',
                $mesBasica ? "{$mesBasica->id} | {$mesBasica->meses}" : '',
                $periodoBasica ? "{$periodoBasica->id} | {$periodoBasica->descripcion}" : '',
                '',
                '',
                $ciclo ? "{$ciclo->inicio_anio}-09-01" : '',
                $ciclo ? "{$ciclo->inicio_anio}-11-30" : '',
            ],
            [
                'BACHILLERATO',
                $nivelBachillerato ? "{$nivelBachillerato->id} | {$nivelBachillerato->nombre}" : '',
                $ciclo ? "{$ciclo->id} | {$ciclo->inicio_anio}-{$ciclo->fin_anio}" : '',
                $generacion
                    ? "{$generacion->id} | {$generacion->anio_ingreso}-{$generacion->anio_egreso}"
                    : '',
                $semestre ? "{$semestre->id} | {$semestre->numero}° semestre" : '',
                '',
                '',
                $semestre?->mesesBachillerato
                    ? "{$semestre->mesesBachillerato->id} | {$semestre->mesesBachillerato->meses}"
                    : '',
                $parcial ? "{$parcial->id} | {$parcial->descripcion}" : '',
                $ciclo ? "{$ciclo->inicio_anio}-08-15" : '',
                $ciclo ? "{$ciclo->fin_anio}-01-31" : '',
            ],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $hoja = $event->sheet->getDelegate();
                $hoja->mergeCells('A1:K1');
                $hoja->getStyle('A1:K1')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '7C2D12']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => 'FFEDD5'],
                    ],
                    'alignment' => [
                        'horizontal' => Alignment::HORIZONTAL_CENTER,
                        'vertical' => Alignment::VERTICAL_CENTER,
                    ],
                ]);
                $hoja->getRowDimension(1)->setRowHeight(30);
                $hoja->getStyle('A3:K3')->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                    'fill' => [
                        'fillType' => Fill::FILL_SOLID,
                        'startColor' => ['rgb' => '006492'],
                    ],
                ]);
                $hoja->getStyle('J4:K5')->getNumberFormat()->setFormatCode('yyyy-mm-dd');
            },
        ];
    }
}
