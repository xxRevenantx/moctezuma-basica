<?php

namespace App\Console\Commands;

use App\Services\RiesgoAcademicoService;
use Illuminate\Console\Command;

class EvaluarRiesgoAcademico extends Command
{
    protected $signature = 'academico:evaluar-riesgo
        {--ciclo= : ID del ciclo escolar}
        {--nivel= : ID del nivel educativo}
        {--inscripcion= : ID de la inscripción}
        {--sin-casos : Evalúa sin abrir casos automáticos}
        {--json : Devuelve el resultado en JSON}';

    protected $description = 'Calcula el semáforo de riesgo académico y sincroniza casos, alertas y vencimientos.';

    public function handle(RiesgoAcademicoService $service): int
    {
        $resultado = $service->evaluarLote([
            'ciclo_escolar_id' => $this->option('ciclo') ?: null,
            'nivel_id' => $this->option('nivel') ?: null,
            'inscripcion_id' => $this->option('inscripcion') ?: null,
        ], null, ! (bool) $this->option('sin-casos'));

        if ($this->option('json')) {
            $this->line(json_encode($resultado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        } else {
            $this->info('Evaluación de riesgo finalizada.');
            $this->table(['Concepto', 'Total'], collect($resultado)->map(fn ($valor, $clave) => [$clave, $valor])->values()->all());
        }

        return $resultado['errores'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
