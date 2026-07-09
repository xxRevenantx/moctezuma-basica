<?php

namespace App\Services;

use App\Models\DocumentoPersonal;
use App\Models\TipoDocumentoPersonal;
use Illuminate\Support\Collection;

class ExpedientePersonalResumenService
{
    /**
     * @return array{porcentaje:int,total:int,completos:int,faltantes:array<int,string>}
     */
    public function paraPersona(int $personaId, ?Collection $tipos = null): array
    {
        $tipos ??= TipoDocumentoPersonal::query()
            ->where('activo', true)
            ->orderBy('orden')
            ->get(['id', 'nombre', 'es_obligatorio']);

        $base = $tipos->where('es_obligatorio', true);
        if ($base->isEmpty()) {
            $base = $tipos;
        }

        $presentes = DocumentoPersonal::query()
            ->where('persona_id', $personaId)
            ->where('es_actual', true)
            ->whereIn('estado', ['recibido', 'validado'])
            ->whereIn('tipo_documento_personal_id', $base->pluck('id'))
            ->pluck('tipo_documento_personal_id')
            ->unique();

        $faltantes = $base
            ->reject(fn ($tipo) => $presentes->contains($tipo->id))
            ->pluck('nombre')
            ->values()
            ->all();

        $total = $base->count();
        $completos = $total - count($faltantes);
        $porcentaje = $total > 0 ? (int) round(($completos / $total) * 100) : 100;

        return compact('porcentaje', 'total', 'completos', 'faltantes');
    }
}
