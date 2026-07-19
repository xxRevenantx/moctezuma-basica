<?php

namespace App\Services;

use App\Models\CicloEscolar;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Persona;
use RuntimeException;

class CredencialImagenService
{
    private const WIDTH = 2008;
    private const HEIGHT = 650;

    public function verificarDisponibilidad(): void
    {
        if (! extension_loaded('gd') || ! function_exists('imagecreatetruecolor')) {
            throw new RuntimeException(
                'La extensión GD de PHP no está habilitada. Actívala en php.ini y reinicia el servidor antes de generar credenciales en imagen.'
            );
        }

        if (! function_exists('imagettftext')) {
            throw new RuntimeException(
                'La instalación de GD no incluye soporte FreeType. Es necesario para imprimir correctamente los textos de las credenciales.'
            );
        }
    }

    public function verificarRecursos(string $tipo): void
    {
        $this->verificarDisponibilidad();
        $this->fuenteRegular();
        $this->fuenteBold();

        $plantilla = $tipo === 'profesor'
            ? public_path('imagenes/credencial_profesor.jpg')
            : public_path('imagenes/credencial.jpg');

        if (! is_file($plantilla) || ! is_readable($plantilla)) {
            throw new RuntimeException('No se encontró la plantilla de credencial requerida: ' . $plantilla);
        }
    }

    public function renderAlumno(
        Inscripcion $alumno,
        Nivel $nivel,
        ?CicloEscolar $cicloEscolar,
        string $formato = 'png'
    ): string {
        $this->verificarDisponibilidad();

        $canvas = $this->cargarPlantilla(public_path('imagenes/credencial.jpg'));
        $regular = $this->fuenteRegular();
        $bold = $this->fuenteBold();

        $negro = imagecolorallocate($canvas, 17, 24, 39);
        $gris = imagecolorallocate($canvas, 71, 85, 105);
        $blanco = imagecolorallocate($canvas, 255, 255, 255);
        $borde = imagecolorallocate($canvas, 203, 213, 225);

        $this->dibujarFoto(
            $canvas,
            $alumno->foto_data_uri,
            28,
            220,
            270,
            325,
            $borde,
            $gris,
            $regular
        );

        $this->textoCentrado(
            $canvas,
            'CREDENCIAL DEL ESTUDIANTE',
            27,
            442,
            876,
            238,
            $blanco,
            $bold
        );

        $this->textoAjustado(
            $canvas,
            'C.C.T. ' . ($nivel->cct ?: 'NO ESPECIFICADO'),
            22,
            350,
            238,
            615,
            $blanco,
            $bold,
            18
        );

        $nombre = $this->nombreCompleto(
            $alumno->nombre,
            $alumno->apellido_paterno,
            $alumno->apellido_materno
        );

        $grupo = $alumno->grupo?->asignacionGrupo?->nombre ?: 'NO ESPECIFICADO';
        $grado = $alumno->grado?->nombre ?: 'NO ESPECIFICADO';
        $nivelNombre = $alumno->nivel?->nombre ?: $nivel->nombre ?: 'NO ESPECIFICADO';
        $vigencia = $cicloEscolar?->fin_anio
            ? 'AGOSTO ' . $cicloEscolar->fin_anio
            : 'NO ESPECIFICADA';

        $y = 292;
        $y = $this->lineaEtiquetaValor($canvas, 'Nombre:', $nombre, 28, 350, $y, 600, $negro, $bold, $regular, 2);
        $y = $this->lineaEtiquetaValor($canvas, 'Matrícula:', $alumno->matricula ?: 'NO ESPECIFICADA', 26, 350, $y + 5, 600, $negro, $bold, $regular);
        $y = $this->lineaEtiquetaValor($canvas, 'CURP:', $alumno->curp ?: 'NO ESPECIFICADA', 26, 350, $y + 4, 600, $negro, $bold, $regular);
        $y = $this->lineaEtiquetaValor($canvas, 'Nivel:', mb_strtoupper($nivelNombre), 25, 350, $y + 4, 600, $negro, $bold, $regular);
        $y = $this->lineaEtiquetaValor($canvas, 'Grado y grupo:', mb_strtoupper($grado . '°  "' . $grupo . '"'), 25, 350, $y + 4, 600, $negro, $bold, $regular);
        $this->lineaEtiquetaValor($canvas, 'Vigencia:', $vigencia, 25, 350, $y + 4, 600, $negro, $bold, $regular);

        $nombreDirector = $this->nombreDirector($nivel);
        $this->textoCentrado($canvas, $nombreDirector, 20, 475, 815, 590, $negro, $bold, 16);

        return $this->codificar($canvas, $formato);
    }

    public function renderProfesor(
        Persona $persona,
        Nivel $nivel,
        string $vigencia,
        string $cargoPredeterminado,
        string $formato = 'png'
    ): string {
        $this->verificarDisponibilidad();

        $canvas = $this->cargarPlantilla(public_path('imagenes/credencial_profesor.jpg'));
        $regular = $this->fuenteRegular();
        $bold = $this->fuenteBold();

        $negro = imagecolorallocate($canvas, 17, 24, 39);
        $gris = imagecolorallocate($canvas, 71, 85, 105);
        $blanco = imagecolorallocate($canvas, 255, 255, 255);
        $borde = imagecolorallocate($canvas, 203, 213, 225);

        $this->dibujarFoto(
            $canvas,
            $persona->foto_data_uri,
            28,
            220,
            270,
            325,
            $borde,
            $gris,
            $regular
        );

        $this->textoCentrado(
            $canvas,
            'CREDENCIAL DEL PROFESOR',
            27,
            442,
            876,
            238,
            $blanco,
            $bold
        );

        $this->textoAjustado(
            $canvas,
            'C.C.T. ' . ($nivel->cct ?: 'NO ESPECIFICADO'),
            22,
            350,
            238,
            615,
            $blanco,
            $bold,
            18
        );

        $personaNivel = $persona->personaNiveles->firstWhere('nivel_id', $nivel->id);
        $rolPrincipal = $personaNivel?->detalles
            ?->map(fn ($detalle) => $detalle->personaRole?->rolePersona?->nombre)
            ->filter()
            ->first();

        $nombre = $this->nombreCompleto(
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno,
            $persona->titulo
        );

        $y = 292;
        $y = $this->lineaEtiquetaValor($canvas, 'Nombre:', $nombre, 28, 350, $y, 600, $negro, $bold, $regular, 2);
        $y = $this->lineaEtiquetaValor($canvas, 'Cargo:', mb_strtoupper($rolPrincipal ?: $cargoPredeterminado ?: 'PROFESOR'), 26, 350, $y + 5, 600, $negro, $bold, $regular, 2);
        $y = $this->lineaEtiquetaValor($canvas, 'CURP:', $persona->curp ?: 'NO ESPECIFICADA', 25, 350, $y + 4, 600, $negro, $bold, $regular);
        $y = $this->lineaEtiquetaValor($canvas, 'Nivel:', mb_strtoupper($nivel->nombre ?: 'NO ESPECIFICADO'), 25, 350, $y + 4, 600, $negro, $bold, $regular);
        $this->lineaEtiquetaValor($canvas, 'Vigencia:', mb_strtoupper($vigencia ?: 'NO ESPECIFICADA'), 25, 350, $y + 4, 600, $negro, $bold, $regular);

        $nombreDirector = $this->nombreDirector($nivel);
        $this->textoCentrado($canvas, $nombreDirector, 20, 475, 815, 590, $negro, $bold, 16);

        return $this->codificar($canvas, $formato);
    }

    public function extension(string $formato): string
    {
        return strtolower($formato) === 'jpg' ? 'jpg' : 'png';
    }

    public function mime(string $formato): string
    {
        return strtolower($formato) === 'jpg' ? 'image/jpeg' : 'image/png';
    }

    public function nombreArchivoAlumno(Inscripcion $alumno, string $formato): string
    {
        $base = trim(($alumno->matricula ?: '') . '_' . $this->nombreCompleto(
            $alumno->nombre,
            $alumno->apellido_paterno,
            $alumno->apellido_materno
        ));

        return $this->normalizarNombreArchivo($base ?: 'alumno_' . $alumno->id)
            . '.' . $this->extension($formato);
    }

    public function nombreArchivoProfesor(Persona $persona, string $formato): string
    {
        $numero = collect([
            $persona->getAttribute('numero_personal'),
            $persona->getAttribute('numero_empleado'),
            $persona->getAttribute('clave_personal'),
        ])->first(fn ($valor) => filled($valor));

        $nombre = $this->nombreCompleto(
            $persona->nombre,
            $persona->apellido_paterno,
            $persona->apellido_materno
        );

        $base = trim(($numero ? $numero . '_' : '') . $nombre);

        return $this->normalizarNombreArchivo($base ?: 'profesor_' . $persona->id)
            . '.' . $this->extension($formato);
    }

    public function normalizarNombreArchivo(string $valor): string
    {
        $valor = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $valor) ?: $valor;
        $valor = mb_strtolower($valor);
        $valor = preg_replace('/[^a-z0-9]+/i', '_', $valor) ?: 'credencial';

        return trim($valor, '_');
    }

    private function cargarPlantilla(string $ruta): \GdImage
    {
        if (! is_file($ruta) || ! is_readable($ruta)) {
            throw new RuntimeException('No se encontró la plantilla de credencial: ' . $ruta);
        }

        $contenido = file_get_contents($ruta);
        $imagen = $contenido !== false ? @imagecreatefromstring($contenido) : false;

        if (! $imagen instanceof \GdImage) {
            throw new RuntimeException('La plantilla de credencial no es una imagen válida.');
        }

        if (imagesx($imagen) === self::WIDTH && imagesy($imagen) === self::HEIGHT) {
            return $imagen;
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled(
            $canvas,
            $imagen,
            0,
            0,
            0,
            0,
            self::WIDTH,
            self::HEIGHT,
            imagesx($imagen),
            imagesy($imagen)
        );
        imagedestroy($imagen);

        return $canvas;
    }

    private function dibujarFoto(
        \GdImage $canvas,
        ?string $dataUri,
        int $x,
        int $y,
        int $ancho,
        int $alto,
        int $borde,
        int $texto,
        string $fuente
    ): void {
        $foto = $this->imagenDesdeDataUri($dataUri);

        if ($foto instanceof \GdImage) {
            $this->copiarRecortando($canvas, $foto, $x, $y, $ancho, $alto);
            imagedestroy($foto);
            imagerectangle($canvas, $x, $y, $x + $ancho, $y + $alto, $borde);

            return;
        }

        imagefilledrectangle($canvas, $x, $y, $x + $ancho, $y + $alto, imagecolorallocate($canvas, 248, 250, 252));
        imagerectangle($canvas, $x, $y, $x + $ancho, $y + $alto, $borde);
        $this->textoCentrado($canvas, 'FOTO + SELLO', 22, $x, $x + $ancho, $y + (int) ($alto / 2), $texto, $fuente, 16);
    }

    private function imagenDesdeDataUri(?string $dataUri): ?\GdImage
    {
        if (blank($dataUri)) {
            return null;
        }

        $contenido = $dataUri;
        if (str_starts_with($dataUri, 'data:')) {
            $partes = explode(',', $dataUri, 2);
            if (count($partes) !== 2) {
                return null;
            }
            $contenido = base64_decode($partes[1], true);
        }

        if (! is_string($contenido) || $contenido === '') {
            return null;
        }

        $imagen = @imagecreatefromstring($contenido);

        return $imagen instanceof \GdImage ? $imagen : null;
    }

    private function copiarRecortando(
        \GdImage $destino,
        \GdImage $origen,
        int $x,
        int $y,
        int $ancho,
        int $alto
    ): void {
        $origenAncho = imagesx($origen);
        $origenAlto = imagesy($origen);
        $escala = max($ancho / $origenAncho, $alto / $origenAlto);
        $recorteAncho = (int) round($ancho / $escala);
        $recorteAlto = (int) round($alto / $escala);
        $origenX = max(0, (int) (($origenAncho - $recorteAncho) / 2));
        $origenY = max(0, (int) (($origenAlto - $recorteAlto) / 2));

        imagecopyresampled(
            $destino,
            $origen,
            $x,
            $y,
            $origenX,
            $origenY,
            $ancho,
            $alto,
            $recorteAncho,
            $recorteAlto
        );
    }

    private function lineaEtiquetaValor(
        \GdImage $canvas,
        string $etiqueta,
        string $valor,
        int $tamano,
        int $x,
        int $y,
        int $anchoMaximo,
        int $color,
        string $bold,
        string $regular,
        int $maxLineas = 1
    ): int {
        imagettftext($canvas, $tamano, 0, $x, $y, $color, $bold, $etiqueta);
        $anchoEtiqueta = $this->anchoTexto($etiqueta, $tamano, $bold) + 12;
        $lineas = $this->envolverTexto($valor, $tamano, $regular, max(120, $anchoMaximo - $anchoEtiqueta), $maxLineas);
        $altoLinea = $tamano + 8;

        foreach ($lineas as $indice => $linea) {
            $lineaX = $indice === 0 ? $x + $anchoEtiqueta : $x + $anchoEtiqueta;
            imagettftext($canvas, $tamano, 0, $lineaX, $y + ($indice * $altoLinea), $color, $regular, $linea);
        }

        return $y + (count($lineas) * $altoLinea);
    }

    private function textoAjustado(
        \GdImage $canvas,
        string $texto,
        int $tamano,
        int $x,
        int $y,
        int $anchoMaximo,
        int $color,
        string $fuente,
        int $minimo = 12
    ): void {
        while ($tamano > $minimo && $this->anchoTexto($texto, $tamano, $fuente) > $anchoMaximo) {
            $tamano--;
        }

        imagettftext($canvas, $tamano, 0, $x, $y, $color, $fuente, $texto);
    }

    private function textoCentrado(
        \GdImage $canvas,
        string $texto,
        int $tamano,
        int $xInicio,
        int $xFin,
        int $y,
        int $color,
        string $fuente,
        int $minimo = 12
    ): void {
        $anchoDisponible = $xFin - $xInicio;
        while ($tamano > $minimo && $this->anchoTexto($texto, $tamano, $fuente) > $anchoDisponible) {
            $tamano--;
        }

        $ancho = $this->anchoTexto($texto, $tamano, $fuente);
        $x = $xInicio + (int) (($anchoDisponible - $ancho) / 2);
        imagettftext($canvas, $tamano, 0, $x, $y, $color, $fuente, $texto);
    }

    private function envolverTexto(
        string $texto,
        int $tamano,
        string $fuente,
        int $anchoMaximo,
        int $maxLineas
    ): array {
        $palabras = preg_split('/\s+/', trim($texto)) ?: [];
        $lineas = [];
        $actual = '';

        foreach ($palabras as $palabra) {
            $prueba = trim($actual . ' ' . $palabra);
            if ($actual === '' || $this->anchoTexto($prueba, $tamano, $fuente) <= $anchoMaximo) {
                $actual = $prueba;
                continue;
            }

            $lineas[] = $actual;
            $actual = $palabra;

            if (count($lineas) >= $maxLineas - 1) {
                break;
            }
        }

        if ($actual !== '' && count($lineas) < $maxLineas) {
            $lineas[] = $actual;
        }

        if ($lineas === []) {
            return [''];
        }

        if (count($lineas) === $maxLineas) {
            $ultimo = array_key_last($lineas);
            while ($this->anchoTexto($lineas[$ultimo], $tamano, $fuente) > $anchoMaximo && mb_strlen($lineas[$ultimo]) > 3) {
                $lineas[$ultimo] = mb_substr($lineas[$ultimo], 0, -2) . '…';
            }
        }

        return $lineas;
    }

    private function anchoTexto(string $texto, int $tamano, string $fuente): int
    {
        $caja = imagettfbbox($tamano, 0, $fuente, $texto);

        return abs(($caja[2] ?? 0) - ($caja[0] ?? 0));
    }

    private function codificar(\GdImage $canvas, string $formato): string
    {
        ob_start();
        if (strtolower($formato) === 'jpg') {
            imagejpeg($canvas, null, (int) config('credenciales.jpg_quality', 100));
        } else {
            imagepng($canvas, null, (int) config('credenciales.png_compression', 6));
        }
        $contenido = ob_get_clean();
        imagedestroy($canvas);

        if (! is_string($contenido) || $contenido === '') {
            throw new RuntimeException('No fue posible codificar la imagen de la credencial.');
        }

        return $contenido;
    }

    private function fuenteRegular(): string
    {
        $rutas = [
            storage_path('fonts/calibri-regular.ttf'),
            storage_path('fonts/ARIAL.TTF'),
            storage_path('fonts/arial_normal_d661c247fda660698475fe32cbcd315f.ttf'),
        ];

        return $this->primeraFuenteDisponible($rutas);
    }

    private function fuenteBold(): string
    {
        $rutas = [
            storage_path('fonts/calibri-bold.ttf'),
            storage_path('fonts/ARIALBD.TTF'),
            storage_path('fonts/arial_bold_efe7d97205fecabca41b3e78a2bbe25a.ttf'),
        ];

        return $this->primeraFuenteDisponible($rutas);
    }

    private function primeraFuenteDisponible(array $rutas): string
    {
        foreach ($rutas as $ruta) {
            if (is_file($ruta) && is_readable($ruta)) {
                return $ruta;
            }
        }

        throw new RuntimeException('No se encontró una fuente TTF válida en storage/fonts.');
    }

    private function nombreDirector(Nivel $nivel): string
    {
        $director = $nivel->director;
        if (! $director) {
            return 'DIRECTOR(A)';
        }

        return $this->nombreCompleto(
            $director->nombre,
            $director->apellido_paterno,
            $director->apellido_materno,
            $director->titulo
        );
    }

    private function nombreCompleto(
        ?string $nombre,
        ?string $apellidoPaterno,
        ?string $apellidoMaterno,
        ?string $titulo = null
    ): string {
        return mb_strtoupper(trim(implode(' ', array_filter([
            $titulo,
            $nombre,
            $apellidoPaterno,
            $apellidoMaterno,
        ]))));
    }
}
