<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>{{ $constancia->folio }}</title>

    <style>
        @page {
            margin: 35px 45px;
        }


        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            src: url('{{ storage_path('fonts/calibri-regular.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/calibri-bold.ttf') }}') format('truetype');
        }

        body {
            font-family: 'ARIAL', 'calibri', sans-serif;
            font-size: 13px;
            color: #111827;
            line-height: 1.6;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 25px;
            border-bottom: 2px solid #111827;
            padding-bottom: 10px;
        }

        .institucion {
            text-align: center;
        }

        .institucion-nombre {
            font-size: 16px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .institucion-datos {
            font-size: 10px;
            color: #374151;
            margin-top: 3px;
        }

        .folio {
            text-align: right;
            font-size: 10px;
            margin-top: 8px;
            color: #374151;
        }

        .titulo {
            text-align: center;
            font-size: 18px;
            font-weight: bold;
            text-transform: uppercase;
            margin-top: 35px;
            margin-bottom: 35px;
        }

        .fecha {
            text-align: right;
            margin-bottom: 30px;
        }

        .dirigido {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 25px;
        }

        .contenido {
            text-align: justify;
            min-height: 260px;
            margin-top: 20px;
            font-size: 13px;
            line-height: 1.6;
        }

        /* Permite que el contenido de TinyMCE conserve sus estilos */
        .contenido * {
            box-sizing: border-box;
        }

        /* Párrafos de TinyMCE */
        .contenido p {
            margin-top: 0;
            margin-bottom: 12px;
        }

        /* Negritas, cursivas, subrayado y tachado */
        .contenido strong,
        .contenido b {
            font-weight: bold;
        }

        .contenido em,
        .contenido i {
            font-style: italic;
        }

        .contenido u {
            text-decoration: underline;
        }

        .contenido s,
        .contenido strike {
            text-decoration: line-through;
        }

        /* Alineaciones de TinyMCE */
        .contenido [style*="text-align: center"] {
            text-align: center;
        }

        .contenido [style*="text-align: right"] {
            text-align: right;
        }

        .contenido [style*="text-align: justify"] {
            text-align: justify;
        }

        .contenido [style*="text-align: left"] {
            text-align: left;
        }

        /* Listas de TinyMCE */
        .contenido ul,
        .contenido ol {
            margin-top: 8px;
            margin-bottom: 8px;
            padding-left: 28px;
        }

        .contenido li {
            margin-bottom: 4px;
        }

        /* Tablas creadas desde TinyMCE */
        .contenido table {
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }

        .contenido table td,
        .contenido table th {
            border: 1px solid #111827;
            padding: 5px 7px;
            vertical-align: top;
        }

        .contenido table th {
            font-weight: bold;
            background: #f3f4f6;
        }

        /* Imágenes si después decides agregarlas en TinyMCE */
        .contenido img {
            max-width: 100%;
            height: auto;
        }

        .tabla-periodos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 25px;
            font-size: 11px;
        }

        .tabla-periodos th,
        .tabla-periodos td {
            border: 1px solid #111827;
            padding: 6px;
            text-align: center;
        }

        .tabla-periodos th {
            background: #f3f4f6;
            font-weight: bold;
        }

        .firma {
            margin-top: 85px;
            text-align: center;
        }

        .linea-firma {
            border-top: 1px solid #111827;
            width: 280px;
            margin: 0 auto 8px auto;
        }

        .texto-firma {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .pie {
            position: fixed;
            bottom: -12px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 9px;
            color: #6b7280;
        }
    </style>
</head>

<body>
    @php
        $nivel = $alumno?->nivel;
        $grado = $alumno?->grado;
        $grupo = $alumno?->grupo?->asignacionGrupo;
        $generacion = $alumno?->generacion;
        $ciclo = $alumno?->ciclo;

        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $periodosSeleccionados = collect($constancia->periodos_calificaciones ?? [])
            ->filter()
            ->keys();
    @endphp

    <div class="encabezado">
        <div class="institucion">
            <div class="institucion-nombre">
                Centro Universitario Moctezuma A.C.
            </div>

            <div class="institucion-datos">
                @if ($nivel?->nombre)
                    Nivel: {{ $nivel->nombre }}
                @endif

                @if ($nivel?->cct)
                    &nbsp; | &nbsp; C.C.T.: {{ $nivel->cct }}
                @endif

                <br>

                Francisco I. Madero Oriente No. 800, Col. Esquipulas,
                Cd. Altamirano, Guerrero.
            </div>
        </div>

        <div class="folio">
            Folio: {{ $constancia->folio }}
        </div>
    </div>

    <div class="titulo">
        {{ $plantilla?->titulo ?? 'CONSTANCIA' }}
    </div>

    <div class="fecha">
        Ciudad Altamirano, Guerrero, a
        {{ $constancia->fecha_expedicion?->translatedFormat('d \d\e F \d\e Y') }}
    </div>

    <div class="dirigido">
        {{ $constancia->dirigido_a ?: 'A QUIEN CORRESPONDA' }}
    </div>

    <div class="contenido">
        {!! $constancia->contenido_generado_html !!}
    </div>

    @if ($periodosSeleccionados->isNotEmpty())
        <table class="tabla-periodos">
            <thead>
                <tr>
                    <th>Periodo</th>
                    <th>Observación</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($periodosSeleccionados as $periodo)
                    <tr>
                        <td>
                            {{ str_replace('_', ' ', \Illuminate\Support\Str::headline($periodo)) }}
                        </td>

                        <td>
                            Periodo seleccionado para constancia.
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="firma">
        <div class="linea-firma"></div>

        <div class="texto-firma">
            Dirección Escolar
        </div>
    </div>

    <div class="pie">
        Documento generado por el sistema escolar.
        @if ($nombreAlumno)
            Alumno(a): {{ $nombreAlumno }}
        @endif

        @if ($grado?->nombre)
            | Grado: {{ $grado->nombre }}
        @endif

        @if ($grupo?->nombre)
            | Grupo: {{ $grupo->nombre }}
        @endif

        @if ($ciclo?->ciclo)
            | Ciclo escolar: {{ $ciclo->ciclo }}
        @endif
    </div>
</body>

</html>
