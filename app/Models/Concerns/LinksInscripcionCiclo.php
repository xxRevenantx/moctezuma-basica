<?php

namespace App\Models\Concerns;

use App\Models\InscripcionCiclo;
use App\Services\HistorialCicloEscolarService;

trait LinksInscripcionCiclo
{
    public static function bootLinksInscripcionCiclo(): void
    {
        static::saving(function ($model): void {
            app(HistorialCicloEscolarService::class)->vincularRegistroAcademico($model);
        });
    }

    public function inscripcionCiclo()
    {
        return $this->belongsTo(InscripcionCiclo::class, 'inscripcion_ciclo_id');
    }
}
