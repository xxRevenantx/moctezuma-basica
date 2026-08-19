<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Etiquetas de alumnos</title>

    <style>
        @page {
            size: letter portrait;
            margin: 0;
        }

        @font-face {
            font-family: 'RALEWAY';
            font-style: normal;
            src: url('{{ storage_path('fonts/Raleway-Regular.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'RALEWAYBD';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/Raleway-Bold.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/calibri-regular.ttf') }}') format('truetype');
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: 'calibri', Arial, sans-serif;
            font-weight: 700;
            color: #10233f;
        }

        .pagina-etiquetas {
            position: relative;
            width: 21.59cm;
            height: 27.94cm;
            margin: 0;
            padding: 0;
            overflow: hidden;
        }

        .salto-pagina-etiquetas {
            page-break-after: always;
        }

        .fondo-hoja {
            position: absolute;
            top: 0;
            left: 0;
            width: 21.59cm;
            height: 27.94cm;
            object-fit: cover;
            z-index: 1;
        }

        .bloque-alumno {
            position: absolute;
            left: 0.85cm;
            width: 90%;
            margin: 0 auto;
            text-align: center;
            text-transform: uppercase;
            z-index: 2;
        }

        .bloque-alumno.alumno-1 {
            top: 3.15cm;
        }

        .bloque-alumno.alumno-2 {
            top: 17.10cm;
        }

        .nombre-alumno {
            margin: 0;
            padding: 0 0.25cm 0.18cm 0.25cm;
            border-bottom: 0.5mm solid #006492;
            font-size: 60px;
            line-height: 55px;
            font-weight: 700;
            color: #006492;
        }

        .datos-escolares {
            margin-top: 0.28cm;
            font-size: 40px;
            line-height: 1.45;
            font-weight: 700;
            color: #26384f;
        }

        .dato-nivel {
            color: #000000;
        }

        .dato-generacion {
            color: #4a9f00;
        }

        .sin-alumnos {
            margin: 2cm auto;
            padding: 1cm;
            width: 90%;
            border: 1px solid #d6dde5;
            text-align: center;
            font-size: 12pt;
            color: #667085;
        }
    </style>
</head>

<body>
    @php
        $modoGlobalActivo = (bool) ($modoGlobal ?? false);
        $coleccionAlumnos = collect($alumnos ?? [])->values();
        $paginas = $coleccionAlumnos->chunk(2)->map(fn($pagina) => $pagina->values())->values();

        $nombreNivelFijo = mb_strtoupper((string) ($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL')), 'UTF-8');
        $nombreGeneracionFija = $generacion
            ? mb_strtoupper(trim((string) $generacion->anio_ingreso . ' - ' . (string) $generacion->anio_egreso), 'UTF-8')
            : 'SIN GENERACIÓN';

        $fondoPersonalizador =
            $imagenPersonalizador ??
            (file_exists(public_path('imagenes/personalizador.jpg'))
                ? public_path('imagenes/personalizador.jpg')
                : null);

        $datosAlumno = function ($alumno) use ($modoGlobalActivo, $nombreNivelFijo, $nombreGeneracionFija) {
            if (!$alumno) {
                return [
                    'nombre' => '',
                    'nivel' => '',
                    'generacion' => '',
                ];
            }

            $nombre = trim(
                (string) ($alumno->nombre ?? '') . ' ' .
                (string) ($alumno->apellido_paterno ?? '') . ' ' .
                (string) ($alumno->apellido_materno ?? '')
            );

            if (!$modoGlobalActivo) {
                return [
                    'nombre' => $nombre,
                    'nivel' => $nombreNivelFijo,
                    'generacion' => $nombreGeneracionFija,
                ];
            }

            $nivelTexto = mb_strtoupper((string) ($alumno->nivel?->nombre ?? 'SIN NIVEL'), 'UTF-8');
            $generacionTexto = $alumno->generacion
                ? mb_strtoupper(trim((string) $alumno->generacion->anio_ingreso . ' - ' . (string) $alumno->generacion->anio_egreso), 'UTF-8')
                : 'SIN GENERACIÓN';

            return [
                'nombre' => $nombre,
                'nivel' => $nivelTexto,
                'generacion' => $generacionTexto,
            ];
        };
    @endphp

    @forelse ($paginas as $pagina)
        @php
            $alumno1 = $pagina->get(0);
            $alumno2 = $pagina->get(1);
            $datos1 = $datosAlumno($alumno1);
            $datos2 = $datosAlumno($alumno2);
        @endphp

        <div class="pagina-etiquetas {{ !$loop->last ? 'salto-pagina-etiquetas' : '' }}">
            @if ($fondoPersonalizador)
                <img class="fondo-hoja" src="{{ $fondoPersonalizador }}" alt="Personalizador">
            @endif

            @if ($alumno1)
                <div class="bloque-alumno alumno-1">
                    <p class="nombre-alumno">
                        {{ $datos1['nombre'] !== '' ? $datos1['nombre'] : 'ALUMNO' }}
                    </p>

                    <div class="datos-escolares">
                        <span class="dato-nivel">{{ $datos1['nivel'] }}</span> |
                        <span class="dato-generacion">GEN: {{ $datos1['generacion'] }}</span>
                    </div>
                </div>
            @endif

            @if ($alumno2)
                <div class="bloque-alumno alumno-2">
                    <p class="nombre-alumno">
                        {{ $datos2['nombre'] !== '' ? $datos2['nombre'] : 'ALUMNO' }}
                    </p>

                    <div class="datos-escolares">
                        <span class="dato-nivel">{{ $datos2['nivel'] }}</span> |
                        <span class="dato-generacion">GEN: {{ $datos2['generacion'] }}</span>
                    </div>
                </div>
            @endif
        </div>
    @empty
        <div class="sin-alumnos">
            No hay alumnos registrados para generar las etiquetas.
        </div>
    @endforelse
</body>

</html>
