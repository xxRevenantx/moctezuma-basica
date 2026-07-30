<?php

namespace App\Enums;

enum EstadoInscripcionCiclo: string
{
    case EN_CURSO = 'en_curso';
    case CERRADO = 'cerrado';
    case ANULADO = 'anulado';

    public static function normalizar(?string $valor): string
    {
        $valor = mb_strtolower(trim((string) $valor));

        return match ($valor) {
            '', 'activo', 'abierto', 'en curso', 'en_curso' => self::EN_CURSO->value,
            'cerrado', 'finalizado', 'finalizada', 'concluido', 'concluida' => self::CERRADO->value,
            'anulado', 'anulada', 'no iniciado', 'no_iniciado' => self::ANULADO->value,
            default => $valor,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::EN_CURSO => 'En curso',
            self::CERRADO => 'Cerrado',
            self::ANULADO => 'Anulado: no inició',
        };
    }
}
