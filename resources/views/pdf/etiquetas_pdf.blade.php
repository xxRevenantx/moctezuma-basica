<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Etiquetas de alumnos</title>

    <style>
        @page {
            size: letter portrait;
            margin: 0.55cm 0.65cm;
        }

        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #10233f;
        }

        .pagina-etiquetas {
            width: 100%;
            margin: 0;
            padding: 0;
        }

        .salto-pagina-etiquetas {
            page-break-after: always;
        }

        .etiqueta {
            position: relative;
            width: 19.80cm;
            height: 12.81cm;
            margin: 0 auto 0.32cm auto;
            overflow: hidden;
            border: 0.25mm solid #d6dde5;
            background: #ffffff;
        }

        .etiqueta:last-child {
            margin-bottom: 0;
        }

        .fondo-etiqueta {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        .datos-alumno {
            position: absolute;
            top: 3.15cm;
            right: 0.85cm;
            width: 12.60cm;
            text-align: center;
            text-transform: uppercase;
        }

        .leyenda {
            margin: 0 0 0.18cm 0;
            font-size: 8.5pt;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #6b7788;
        }

        .nombre-alumno {
            margin: 0;
            padding: 0 0.25cm 0.18cm 0.25cm;
            border-bottom: 0.5mm solid #006492;
            font-size: 18pt;
            line-height: 1.16;
            font-weight: 700;
            color: #006492;
        }

        .datos-escolares {
            margin-top: 0.28cm;
            font-size: 11pt;
            line-height: 1.45;
            font-weight: 700;
            color: #26384f;
        }

        .dato-nivel {
            color: #4a9f00;
        }

        .separador {
            display: inline-block;
            margin: 0 0.18cm;
            color: #a4afbb;
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
        $coleccionAlumnos = collect($alumnos ?? [])->values();
        $paginas = $coleccionAlumnos->chunk(2)->values();

        $nombreNivel = mb_strtoupper((string) ($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL')), 'UTF-8');
        $nombreGrado = mb_strtoupper((string) ($grado->nombre ?? ($grado->grado ?? 'SIN GRADO')), 'UTF-8');

        // El controlador la envía en base64 para que Dompdf no falle con rutas locales.
        $fondoPersonalizador =
            $imagenPersonalizador ??
            (file_exists(public_path('imagenes/personalizador.jpg'))
                ? public_path('imagenes/personalizador.jpg')
                : null);
    @endphp

    @forelse ($paginas as $pagina)
        <div class="pagina-etiquetas {{ !$loop->last ? 'salto-pagina-etiquetas' : '' }}">
            {{-- Siempre se preparan dos espacios por hoja. --}}
            @for ($posicion = 0; $posicion < 2; $posicion++)
                @php
                    $alumno = $pagina->get($posicion);
                    $nombreCompleto = $alumno
                        ? trim(
                            (string) ($alumno->apellido_paterno ?? '') .
                                ' ' .
                                (string) ($alumno->apellido_materno ?? '') .
                                ' ' .
                                (string) ($alumno->nombre ?? ''),
                        )
                        : '';
                @endphp

                <div class="etiqueta">
                    @if ($fondoPersonalizador)
                        <img class="fondo-etiqueta" src="{{ $fondoPersonalizador }}" alt="Personalizador">
                    @endif

                    @if ($alumno)
                        <div class="datos-alumno">
                            <p class="leyenda">NOMBRE DEL ALUMNO</p>
                            <p class="nombre-alumno">
                                {{ $nombreCompleto !== '' ? $nombreCompleto : 'ALUMNO' }}
                            </p>

                            <div class="datos-escolares">
                                <span class="dato-nivel">NIVEL: {{ $nombreNivel }}</span>
                                <span class="separador">|</span>
                                <span>GRADO: {{ $nombreGrado }}</span>
                            </div>
                        </div>
                    @endif
                </div>
            @endfor
        </div>
    @empty
        <div class="sin-alumnos">
            No hay alumnos registrados para generar las etiquetas.
        </div>
    @endforelse
</body>

</html>
