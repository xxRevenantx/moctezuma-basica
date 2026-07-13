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

        /* UNA SOLA IMAGEN DE FONDO POR TODA LA HOJA */
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
            /* right: 0.85cm; */
            left: 0.85cm;
            width: 90%;
            margin: 0 auto;
            text-align: center;
            text-transform: uppercase;
            z-index: 2;
        }

        /* Ajusta estas posiciones según tu plantilla */
        .bloque-alumno.alumno-1 {
            top: 3.15cm;
        }

        .bloque-alumno.alumno-2 {
            top: 17.10cm;
        }

        .leyenda {
            margin: 0 0 0.18cm 0;
            font-size: 60px;
            font-weight: 700;
            letter-spacing: 1.5px;
            color: #6b7788;
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
        $coleccionAlumnos = collect($alumnos ?? [])->values();

        $paginas = $coleccionAlumnos->chunk(2)->map(fn($pagina) => $pagina->values())->values();

        $nombreNivel = mb_strtoupper((string) ($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL')), 'UTF-8');
        $nombreGrado = mb_strtoupper((string) ($grado->nombre ?? ($grado->grado ?? 'SIN GRADO')), 'UTF-8');
        $nombreGeneracion = mb_strtoupper(
            (string) ($generacion->anio_ingreso . ' ' . $generacion->anio_egreso ??
                ($generacion->anio_ingreso ?? 'SIN GENERACION')),
            'UTF-8',
        );

        $fondoPersonalizador =
            $imagenPersonalizador ??
            (file_exists(public_path('imagenes/personalizador.jpg'))
                ? public_path('imagenes/personalizador.jpg')
                : null);
    @endphp

    @forelse ($paginas as $pagina)
        @php
            $alumno1 = $pagina->get(0);
            $alumno2 = $pagina->get(1);

            $nombreAlumno1 = $alumno1
                ? trim(
                    (string) ($alumno1->nombre ?? '') .
                        ' ' .
                        (string) ($alumno1->apellido_paterno ?? '') .
                        ' ' .
                        (string) ($alumno1->apellido_materno ?? ''),
                )
                : '';

            $nombreAlumno2 = $alumno2
                ? trim(
                    (string) ($alumno2->nombre ?? '') .
                        ' ' .
                        (string) ($alumno2->apellido_paterno ?? '') .
                        ' ' .
                        (string) ($alumno2->apellido_materno ?? ''),
                )
                : '';
        @endphp

        <div class="pagina-etiquetas {{ !$loop->last ? 'salto-pagina-etiquetas' : '' }}">
            @if ($fondoPersonalizador)
                <img class="fondo-hoja" src="{{ $fondoPersonalizador }}" alt="Personalizador">
            @endif

            @if ($alumno1)
                <div class="bloque-alumno alumno-1">
                    <p class="nombre-alumno">
                        {{ $nombreAlumno1 !== '' ? $nombreAlumno1 : 'ALUMNO' }}
                    </p>

                    <div class="datos-escolares">
                        <span class="dato-nivel">{{ $nombreNivel }}</span> |
                        <span class="dato-generacion">GEN: {{ $nombreGeneracion }}</span>
                    </div>
                </div>
            @endif

            @if ($alumno2)
                <div class="bloque-alumno alumno-2">
                    <p class="nombre-alumno">
                        {{ $nombreAlumno2 !== '' ? $nombreAlumno2 : 'ALUMNO' }}
                    </p>

                    <div class="datos-escolares">
                        <span class="dato-nivel">{{ $nombreNivel }}</span> |
                        <span class="dato-generacion">GEN: {{ $nombreGeneracion }}</span>
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
