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
