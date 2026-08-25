<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Mantiene el ciclo escolar de trabajo entre pantallas administrativas.
 *
 * El parámetro ?ciclo_escolar= tiene prioridad para enlaces directos. Si no
 * existe, se reutiliza el último ciclo seleccionado en sesión y finalmente se
 * recurre al ciclo actual o al más reciente disponible.
 */
class ContextoCicloEscolarSesion
{
    public const SESSION_KEY = 'moctezuma.contexto_academico.ciclo_escolar_id';

    public function resolver(Collection $ciclos): ?int
    {
        if ($ciclos->isEmpty()) {
            return null;
        }

        $candidatos = [
            request()->integer('ciclo_escolar'),
            request()->integer('ciclo_escolar_id'),
            (int) session(self::SESSION_KEY, 0),
            (int) ($ciclos->firstWhere('es_actual', true)?->id ?? 0),
            (int) ($ciclos->first()?->id ?? 0),
        ];

        foreach ($candidatos as $cicloId) {
            if ($cicloId > 0 && $ciclos->contains('id', $cicloId)) {
                $this->recordar($cicloId);

                return $cicloId;
            }
        }

        return null;
    }

    public function recordar(int|string|null $cicloId): void
    {
        $cicloId = (int) $cicloId;

        if ($cicloId > 0) {
            session()->put(self::SESSION_KEY, $cicloId);
        }
    }
}
