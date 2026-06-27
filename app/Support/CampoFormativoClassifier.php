<?php

namespace App\Support;

use Illuminate\Support\Str;

final class CampoFormativoClassifier
{
    public const LENGUAJES = 'lenguajes';

    public const SABERES = 'saberes-pensamiento-cientifico';

    public const ETICA = 'etica-naturaleza-sociedades';

    public const HUMANO = 'humano-comunitario';

    public const SIN_CAMPO = 'sin-campo-formativo';

    public static function sugerir(string $nombre): string
    {
        $texto = self::normalizar($nombre);

        if ($texto === '') {
            return self::SIN_CAMPO;
        }

        // Frases específicas primero. Evita que "Educación Física" sea
        // clasificada como Física en Saberes y pensamiento científico.
        if (self::contiene($texto, [
            'educacion fisica',
            'tutoria y educacion socioemocional',
            'educacion socioemocional',
            'tutoria',
            'socioemocional',
            'tecnologia',
            'orientacion',
            'vida saludable',
            'taller',
            'comunitar',
        ])) {
            return self::HUMANO;
        }

        if (self::contiene($texto, [
            'formacion civica y etica',
            'formacion civica',
            'historia',
            'geografia',
            'civica',
            'etica',
            'sociedad',
            'sociales',
            'derecho',
            'ciudadania',
            'humanidades',
        ])) {
            return self::ETICA;
        }

        if (self::contiene($texto, [
            'espanol',
            'ingles',
            'artes',
            'artistica',
            'lengua',
            'literatura',
            'lectura',
            'redaccion',
            'comunicacion',
            'musica',
            'frances',
        ])) {
            return self::LENGUAJES;
        }

        if (self::contiene($texto, [
            'matemat',
            'algebra',
            'geometr',
            'calculo',
            'biolog',
            'fisica',
            'quimica',
            'ciencia',
            'naturales',
            'ecologia',
            'laboratorio',
        ])) {
            return self::SABERES;
        }

        return self::SIN_CAMPO;
    }

    private static function normalizar(string $texto): string
    {
        return trim((string) preg_replace(
            '/\s+/u',
            ' ',
            Str::lower(Str::ascii($texto))
        ));
    }

    /**
     * @param array<int, string> $terminos
     */
    private static function contiene(string $texto, array $terminos): bool
    {
        foreach ($terminos as $termino) {
            if (str_contains($texto, $termino)) {
                return true;
            }
        }

        return false;
    }
}
