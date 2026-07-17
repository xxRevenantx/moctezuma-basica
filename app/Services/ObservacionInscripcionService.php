<?php

namespace App\Services;

use App\Models\HistorialObservacionInscripcion;
use App\Models\Inscripcion;
use App\Models\ObservacionInscripcion;
use Illuminate\Support\Str;

class ObservacionInscripcionService
{
    public const LIMITE_CARACTERES = 5000;

    /**
     * Limpia el HTML para conservar únicamente formato editorial sencillo.
     */
    public function sanitizar(?string $html): ?string
    {
        $html = trim((string) $html);

        if ($html === '') {
            return null;
        }

        // Elimina bloques completos que nunca deben almacenarse.
        $html = preg_replace(
            '#<(script|style|iframe|object|embed|svg|form|input|button|textarea|select|option)[^>]*>.*?</\1>#is',
            '',
            $html
        ) ?? $html;

        $html = strip_tags(
            $html,
            '<p><br><strong><b><em><i><u><ul><ol><li><blockquote><h3><h4>'
        );

        // Quita atributos y conserva solamente text-align con valores seguros.
        $html = preg_replace_callback('/<([a-z0-9]+)([^>]*)>/i', function (array $coincidencia): string {
            $etiqueta = mb_strtolower($coincidencia[1]);
            $atributos = $coincidencia[2] ?? '';
            $alineacion = null;

            if (preg_match('/text-align\s*:\s*(left|center|right|justify)/i', $atributos, $resultado)) {
                $alineacion = mb_strtolower($resultado[1]);
            }

            return '<'.$etiqueta.($alineacion ? ' style="text-align: '.$alineacion.';"' : '').'>';
        }, $html) ?? $html;

        // Normaliza elementos equivalentes.
        $html = str_ireplace(['<b>', '</b>', '<i>', '</i>'], ['<strong>', '</strong>', '<em>', '</em>'], $html);
        $html = preg_replace('/<p>\s*(?:&nbsp;|<br\s*\/?\s*>|\s)*<\/p>/i', '', $html) ?? $html;
        $html = preg_replace('/(?:\s|&nbsp;)+/u', ' ', $html) ?? $html;
        $html = trim($html);

        return $this->textoPlano($html) === '' ? null : $html;
    }

    public function desdeTextoPlano(?string $texto): ?string
    {
        $texto = trim((string) $texto);

        if ($texto === '') {
            return null;
        }

        // Si Excel contiene HTML previo, se limpia; en caso contrario se convierte cada línea en párrafo.
        if ($texto !== strip_tags($texto)) {
            return $this->sanitizar($texto);
        }

        $lineas = preg_split('/\R/u', $texto) ?: [$texto];
        $parrafos = collect($lineas)
            ->map(fn (string $linea): string => trim($linea))
            ->filter(fn (string $linea): bool => $linea !== '')
            ->map(fn (string $linea): string => '<p>'.e($linea).'</p>')
            ->implode('');

        return $this->sanitizar($parrafos);
    }

    public function textoPlano(?string $html): string
    {
        $texto = strip_tags((string) $html);
        $texto = html_entity_decode($texto, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $texto = preg_replace('/\s+/u', ' ', $texto) ?? $texto;

        return trim($texto);
    }

    public function longitud(?string $html): int
    {
        return mb_strlen($this->textoPlano($html));
    }

    public function excedeLimite(?string $html): bool
    {
        return $this->longitud($html) > self::LIMITE_CARACTERES;
    }

    /**
     * Guarda la observación del alumno para un ciclo escolar y registra su historial.
     */
    public function guardar(
        Inscripcion $inscripcion,
        int $cicloEscolarId,
        ?string $contenido,
        string $origen = 'edicion',
        ?int $usuarioId = null,
    ): ?ObservacionInscripcion {
        $contenido = $this->sanitizar($contenido);

        $observacion = ObservacionInscripcion::query()
            ->where('inscripcion_id', $inscripcion->id)
            ->where('ciclo_escolar_id', $cicloEscolarId)
            ->first();

        $anterior = $this->sanitizar($observacion?->contenido);

        if ($this->normalizarComparacion($anterior) === $this->normalizarComparacion($contenido)) {
            return $observacion;
        }

        // No crea registros vacíos cuando nunca existió contenido.
        if (! $observacion && blank($contenido)) {
            return null;
        }

        $observacion ??= new ObservacionInscripcion([
            'inscripcion_id' => $inscripcion->id,
            'ciclo_escolar_id' => $cicloEscolarId,
            'creado_por' => $usuarioId,
        ]);

        $observacion->contenido = $contenido;
        $observacion->actualizado_por = $usuarioId;
        $observacion->save();

        HistorialObservacionInscripcion::query()->create([
            'observacion_inscripcion_id' => $observacion->id,
            'inscripcion_id' => $inscripcion->id,
            'ciclo_escolar_id' => $cicloEscolarId,
            'usuario_id' => $usuarioId,
            'contenido_anterior' => $anterior,
            'contenido_nuevo' => $contenido,
            'origen' => Str::limit(trim($origen) ?: 'edicion', 30, ''),
        ]);

        return $observacion->refresh();
    }

    private function normalizarComparacion(?string $contenido): string
    {
        return preg_replace('/\s+/u', ' ', trim((string) $contenido)) ?? trim((string) $contenido);
    }
}
