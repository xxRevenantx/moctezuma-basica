<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImagenPersonalService
{
    public function guardar(UploadedFile $archivo, string $directorio = 'personal', int $maxDimension = 1200, bool $soloNombre = true): string
    {
        $mime = strtolower((string) $archivo->getMimeType());
        $extension = match ($mime) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            default => strtolower($archivo->getClientOriginalExtension() ?: 'jpg'),
        };

        $nombre = Str::uuid() . '.' . $extension;
        $ruta = trim($directorio, '/') . '/' . $nombre;
        $disco = (string) config('filesystems.fotos_disk', 'public');

        if (! $this->puedeOptimizar($mime)) {
            $guardada = $archivo->storeAs($directorio, $nombre, $disco);

            if (! $guardada) {
                throw new RuntimeException('No fue posible guardar la fotografía.');
            }

            return $soloNombre ? basename($guardada) : $guardada;
        }

        $origen = $this->crearRecurso($archivo->getRealPath(), $mime);

        if (! $origen) {
            $guardada = $archivo->storeAs($directorio, $nombre, $disco);

            if (! $guardada) {
                throw new RuntimeException('No fue posible guardar la fotografía.');
            }

            return $soloNombre ? basename($guardada) : $guardada;
        }

        $ancho = imagesx($origen);
        $alto = imagesy($origen);
        $escala = min(1, $maxDimension / max($ancho, $alto));
        $nuevoAncho = max(1, (int) floor($ancho * $escala));
        $nuevoAlto = max(1, (int) floor($alto * $escala));

        $destino = imagecreatetruecolor($nuevoAncho, $nuevoAlto);

        if (in_array($mime, ['image/png', 'image/webp'], true)) {
            imagealphablending($destino, false);
            imagesavealpha($destino, true);
            $transparente = imagecolorallocatealpha($destino, 0, 0, 0, 127);
            imagefilledrectangle($destino, 0, 0, $nuevoAncho, $nuevoAlto, $transparente);
        }

        imagecopyresampled(
            $destino,
            $origen,
            0,
            0,
            0,
            0,
            $nuevoAncho,
            $nuevoAlto,
            $ancho,
            $alto
        );

        ob_start();
        $guardado = match ($mime) {
            'image/jpeg', 'image/jpg' => imagejpeg($destino, null, 82),
            'image/png' => imagepng($destino, null, 8),
            'image/webp' => imagewebp($destino, null, 82),
            default => false,
        };
        $contenido = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($destino);

        if (! $guardado || ! is_string($contenido) || $contenido === '') {
            throw new RuntimeException('No fue posible optimizar la fotografía.');
        }

        if (! Storage::disk($disco)->put($ruta, $contenido)) {
            throw new RuntimeException('No fue posible guardar la fotografía optimizada.');
        }

        return $soloNombre ? $nombre : $ruta;
    }

    /**
     * Guarda una fotografía de alumno con medida institucional 2.5 x 3 cm.
     *
     * La relación 5:6 se materializa como 295 x 354 px a 300 ppp. Se recorta
     * desde el centro sin deformar, se corrige la orientación EXIF cuando está
     * disponible y se normaliza a JPEG para mantener consistencia en credenciales
     * y documentos.
     */
    public function guardarFotografiaAlumno(
        UploadedFile $archivo,
        string $directorio = 'inscripciones/fotos',
        bool $soloNombre = false
    ): string {
        $mime = strtolower((string) $archivo->getMimeType());

        if (! in_array($mime, ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'], true)) {
            throw new RuntimeException('El archivo no es una fotografía JPG, PNG o WebP válida.');
        }

        if (! function_exists('imagejpeg') || ! $this->puedeOptimizar($mime)) {
            throw new RuntimeException('El servidor no tiene disponible la extensión GD con soporte JPEG necesaria para normalizar fotografías.');
        }

        $origen = $this->crearRecurso($archivo->getRealPath(), $mime);

        if (! $origen) {
            throw new RuntimeException('No fue posible leer la fotografía seleccionada.');
        }

        if (in_array($mime, ['image/jpeg', 'image/jpg'], true)) {
            $origen = $this->corregirOrientacionExif($origen, $archivo->getRealPath());
        }

        $ancho = imagesx($origen);
        $alto = imagesy($origen);

        if ($ancho < 1 || $alto < 1) {
            imagedestroy($origen);
            throw new RuntimeException('La fotografía no tiene dimensiones válidas.');
        }

        $destinoAncho = 295;
        $destinoAlto = 354;
        $relacionDestino = $destinoAncho / $destinoAlto;
        $relacionOrigen = $ancho / $alto;

        if ($relacionOrigen > $relacionDestino) {
            $recorteAlto = $alto;
            $recorteAncho = (int) round($alto * $relacionDestino);
            $origenX = (int) floor(($ancho - $recorteAncho) / 2);
            $origenY = 0;
        } else {
            $recorteAncho = $ancho;
            $recorteAlto = (int) round($ancho / $relacionDestino);
            $origenX = 0;
            $origenY = (int) floor(($alto - $recorteAlto) / 2);
        }

        $destino = imagecreatetruecolor($destinoAncho, $destinoAlto);
        $blanco = imagecolorallocate($destino, 255, 255, 255);
        imagefilledrectangle($destino, 0, 0, $destinoAncho, $destinoAlto, $blanco);

        imagecopyresampled(
            $destino,
            $origen,
            0,
            0,
            $origenX,
            $origenY,
            $destinoAncho,
            $destinoAlto,
            $recorteAncho,
            $recorteAlto
        );

        if (function_exists('imageresolution')) {
            @imageresolution($destino, 300, 300);
        }

        ob_start();
        $guardado = imagejpeg($destino, null, 90);
        $contenido = ob_get_clean();

        imagedestroy($origen);
        imagedestroy($destino);

        if (! $guardado || ! is_string($contenido) || $contenido === '') {
            throw new RuntimeException('No fue posible generar la fotografía institucional.');
        }

        $nombre = Str::uuid() . '.jpg';
        $ruta = trim($directorio, '/') . '/' . $nombre;
        $disco = (string) config('filesystems.fotos_disk', 'public');

        if (! Storage::disk($disco)->put($ruta, $contenido)) {
            throw new RuntimeException('No fue posible guardar la fotografía institucional.');
        }

        return $soloNombre ? $nombre : $ruta;
    }

    private function corregirOrientacionExif(mixed $imagen, string $ruta): mixed
    {
        if (! function_exists('exif_read_data')) {
            return $imagen;
        }

        try {
            $exif = @exif_read_data($ruta);
            $orientacion = (int) ($exif['Orientation'] ?? 1);

            $rotada = match ($orientacion) {
                3 => imagerotate($imagen, 180, 0),
                6 => imagerotate($imagen, -90, 0),
                8 => imagerotate($imagen, 90, 0),
                default => false,
            };

            if ($rotada !== false) {
                imagedestroy($imagen);
                return $rotada;
            }
        } catch (\Throwable) {
            // Si EXIF no puede leerse, se conserva la imagen original.
        }

        return $imagen;
    }

    public function eliminar(?string $foto): void
    {
        if (blank($foto)) {
            return;
        }

        $ruta = str_starts_with($foto, 'personal/') ? $foto : 'personal/' . ltrim($foto, '/');
        Storage::disk((string) config('filesystems.fotos_disk', 'public'))->delete($ruta);
    }

    public function eliminarRuta(?string $ruta): void
    {
        if (blank($ruta)) {
            return;
        }

        Storage::disk((string) config('filesystems.fotos_disk', 'public'))->delete(ltrim((string) $ruta, '/'));
    }

    private function puedeOptimizar(string $mime): bool
    {
        if (! function_exists('imagecreatetruecolor')) {
            return false;
        }

        return match ($mime) {
            'image/jpeg', 'image/jpg' => function_exists('imagecreatefromjpeg') && function_exists('imagejpeg'),
            'image/png' => function_exists('imagecreatefrompng') && function_exists('imagepng'),
            'image/webp' => function_exists('imagecreatefromwebp') && function_exists('imagewebp'),
            default => false,
        };
    }

    private function crearRecurso(string $ruta, string $mime): mixed
    {
        return match ($mime) {
            'image/jpeg', 'image/jpg' => @imagecreatefromjpeg($ruta),
            'image/png' => @imagecreatefrompng($ruta),
            'image/webp' => @imagecreatefromwebp($ruta),
            default => false,
        };
    }
}
