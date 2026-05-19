<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Lista de asistencia</title>

    @php
        $slugNivel = $nivel->slug ?? '';

        // Preescolar y primaria usan colores pastel.
        // Secundaria y bachillerato usan tonos grises.
        $esPastel = in_array($slugNivel, ['preescolar', 'primaria']);

        $colorBordePagina = $esPastel ? '#f472b6' : '#64748b';
        $colorFondoPrincipal = $esPastel ? '#f9a8d4' : '#cbd5e1';
        $colorFondoAlumno = $esPastel ? '#fbcfe8' : '#e2e8f0';

        $colorSemana1 = $esPastel ? '#fdba74' : '#d1d5db';
        $colorSemana2 = $esPastel ? '#7dd3fc' : '#cbd5e1';
        $colorSemana3 = $esPastel ? '#bef264' : '#e5e7eb';
        $colorSemana4 = $esPastel ? '#fde68a' : '#d1d5db';

        $colorAsistencia = $esPastel ? '#38bdf8' : '#cbd5e1';
        $colorInasistencia = $esPastel ? '#fde047' : '#e5e7eb';
        $colorJustificante = $esPastel ? '#f9a8d4' : '#f1f5f9';
        $colorRetardo = $esPastel ? '#fdba74' : '#e2e8f0';
        $colorPermiso = $esPastel ? '#c4b5fd' : '#f8fafc';
        $colorObservacion = $esPastel ? '#fbcfe8' : '#ffffff';

        $diasSemana = ['L', 'M', 'M', 'J', 'V'];

        $totalAlumnos = $alumnos->count() < 15 ? '10px' : '13px';

        $totalDias = 20;

        /*
         * Ahora se agregan 6 columnas finales:
         * Asistencia, Inasistencias, Retardos, Justificantes, Permisos y Observaciones.
         */
        $totalColumnasResumen = 6;
        $totalColumnas = 2 + $totalDias + $totalColumnasResumen;

        /*
         * Texto del periodo para mostrarlo sin romper si viene de diferentes módulos.
         */
        $textoPeriodoAsistencia =
            $nombrePeriodo ??
            ($periodoTexto ?? ($periodo?->periodoBasica?->periodo ?? ($periodo?->periodoBasica?->descripcion ?? '—')));

        /*
         * Fechas del periodo, si existen.
         */
        $fechaInicioAsistencia = $periodo?->fecha_inicio
            ? \Carbon\Carbon::parse($periodo->fecha_inicio)->locale('es')->translatedFormat('j \\de F Y')
            : '';

        $fechaFinAsistencia = $periodo?->fecha_fin
            ? \Carbon\Carbon::parse($periodo->fecha_fin)->locale('es')->translatedFormat('j \\de F Y')
            : '';

        /*
         * Nombre del mes. Si no viene desde el controlador, se deja línea para llenarlo manualmente.
         */
        $nombreMesAsistencia = $mesNombre ?? ($mes ?? '');

        /*
         * Folio simple para control interno.
         */
        $folioLista =
            'LA-' .
            strtoupper($nivel->slug ?? 'NIVEL') .
            '-' .
            str_pad($grado->id ?? 0, 2, '0', STR_PAD_LEFT) .
            '-' .
            str_pad($grupo->id ?? 0, 2, '0', STR_PAD_LEFT) .
            '-' .
            now()->format('Ymd-His');

        /*
         * Nombre de la materia si la lista se genera por materia.
         * Si no existe, se muestra GENERAL.
         */
        $nombreMateriaAsistencia = $nombreMateria ?? ($materia->materia ?? 'GENERAL');

        /*
         * Nombre del grupo compatible con grupo->asignacionGrupo o grupo->nombre.
         */
        $nombreGrupo = $grupo->asignacionGrupo->nombre ?? ($grupo->nombre ?? '');
    @endphp

    <style>
        @page {
            size: letter portrait;
            margin: 30px 20px;
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

        /*
        @font-face {
            font-family: 'Claphappy';
            font-style: normal;
            src: url('{{ storage_path('fonts/Claphappy.ttf') }}') format('truetype');
        } */

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            color: #111827;
            font-size: 10px;
        }

        .pagina {
            position: relative;
        }

        .contenido {
            position: relative;
            z-index: 2;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .titulo {
            text-align: center;
            font-size: 23px;
            line-height: 1;
            margin-top: -4px;
            margin-bottom: 5px;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 12px;
        }

        .datos td {
            border: none;
            /* padding: 0 4px 4px 4px; */
            vertical-align: middle;
        }

        .campo {
            display: inline-block;
            height: 22px;
            min-width: 150px;
            padding: 2px 4px;
            border: 1px solid #374151;
            border-radius: 5px;
            background: {{ $esPastel ? '#f5b4e7' : '#e5e7eb' }};
            text-transform: uppercase;
            line-height: 17px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .campo-largo {
            /* min-width: 300px; */
        }

        .campo-materia {
            min-width: 250px;
        }

        .campo-corto {
            min-width: 100px;
            text-align: center;
        }

        .campo-ciclo {
            text-align: center;
        }

        .campo-periodo {
            min-width: 150px;
            text-align: center;
        }

        .campo-mes {
            min-width: 160px;
            text-align: center;
        }

        .folio {
            display: inline-block;
            margin-top: 3px;
            padding: 2px 10px;
            border: 1px solid #374151;
            border-radius: 12px;
            background: {{ $esPastel ? '#fce7f3' : '#e5e7eb' }};
            color: #111827;
            font-size: 9px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
            font-weight: 700;
        }

        .fechas {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 11px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .fechas td {
            border: none;
            padding: 0 4px 4px 4px;
        }

        .leyenda {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
            font-size: 9px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .leyenda td {
            border: 1px solid #111827;
            padding: 3px 5px;
            text-align: center;
        }

        .leyenda-titulo {
            background: {{ $colorFondoPrincipal }};
            font-weight: bold;
            text-transform: uppercase;
        }

        .clave {
            display: inline-block;
            min-width: 18px;
            padding: 1px 4px;
            border-radius: 4px;
            font-weight: bold;
            margin-right: 3px;
        }

        .clave-a {
            background: #bbf7d0;
            color: #166534;
        }

        .clave-f {
            background: #fecaca;
            color: #991b1b;
        }

        .clave-r {
            background: #fde68a;
            color: #92400e;
        }

        .clave-j {
            background: #bfdbfe;
            color: #1e40af;
        }

        .clave-p {
            background: #ddd6fe;
            color: #5b21b6;
        }

        .tabla-asistencia {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .tabla-asistencia th,
        .tabla-asistencia td {
            border: 1px solid #111827;
            padding: 0;
            font-family: 'Claphappy', DejaVu Sans, sans-serif;
        }

        .th-numero {
            font-size: 15px;
            text-align: center;
            width: 20px;
            background: {{ $colorFondoPrincipal }};
        }

        .th-alumno {
            text-align: center;
            width: 190px;
            font-family: Claphappy, DejaVu Sans, sans-serif;
            font-size: 13px;
            background: {{ $colorFondoAlumno }};
        }

        .mes {
            background: {{ $colorFondoPrincipal }};
            font-size: 18px;
            text-align: center;
        }

        .semana {
            height: 28px;
            font-size: 12px;
            width: 5px;
            text-align: center;
        }

        .semana-1 {
            background: {{ $colorSemana1 }};
        }

        .semana-2 {
            background: {{ $colorSemana2 }};
        }

        .semana-3 {
            background: {{ $colorSemana3 }};
        }

        .semana-4 {
            background: {{ $colorSemana4 }};
        }

        .dia {
            height: 22px;
            background: #f8fafc;
            font-size: 9px;
            text-align: center;
        }

        .dia-1 {
            background: {{ $esPastel ? '#ffedd5' : '#f8fafc' }};
        }

        .dia-2 {
            background: {{ $esPastel ? '#e0f2fe' : '#f8fafc' }};
        }

        .dia-3 {
            background: {{ $esPastel ? '#ecfccb' : '#f8fafc' }};
        }

        .dia-4 {
            background: {{ $esPastel ? '#fef3c7' : '#f8fafc' }};
        }

        .col-dia {
            width: 2px;
        }

        .fecha-dia {
            height: 17px;
            font-size: 7px;
            background: #ffffff;
            text-align: center;
        }

        .resumen {
            width: 0px;
            height: 0px;
            text-align: center;
            vertical-align: middle;
        }

        .resumen-asistencia {
            background: {{ $colorAsistencia }};
        }

        .resumen-inasistencia {
            background: {{ $colorInasistencia }};
        }

        .resumen-retardo {
            background: {{ $colorRetardo }};
        }

        .resumen-justificante {
            background: {{ $colorJustificante }};
        }

        .resumen-permiso {
            background: {{ $colorPermiso }};
        }

        .resumen-observacion {
            background: {{ $colorObservacion }};
        }

        .texto-vertical {
            writing-mode: vertical-rl;
            transform: rotate(-90deg);
            display: inline-block;
            font-size: 10px;
            width: 5px;
        }

        .fila-alumno td {
            width: {{ $totalAlumnos }};
            height: 19px;
        }

        .numero {
            text-align: center;
            font-size: 10px;
        }

        .alumno {
            padding-left: 5px !important;
            font-size: 10px;
            text-transform: uppercase;
        }

        .celda {
            text-align: center;
        }

        .celda-resumen {
            text-align: center;
            background: #ffffff;
        }

        .celda-observacion {
            background: {{ $esPastel ? '#fdf2f8' : '#ffffff' }};
        }

        .sin-alumnos {
            text-align: center;
            font-size: 12px;
            padding: 12px;
        }

        .resumen-grupal {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .resumen-grupal th,
        .resumen-grupal td {
            border: 1px solid #111827;
            padding: 3px 5px;
            text-align: center;
        }

        .resumen-grupal th {
            background: {{ $colorFondoPrincipal }};
            font-weight: bold;
        }

        .resumen-grupal td {
            height: 19px;
        }

        .incidencias {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 9px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .incidencias td {
            border: 1px solid #111827;
            padding: 4px 6px;
        }

        .incidencias-titulo {
            width: 150px;
            text-align: center;
            background: {{ $colorFondoPrincipal }};
            font-weight: bold;
            text-transform: uppercase;
        }

        .observaciones-generales {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
            font-size: 9px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .observaciones-generales td {
            border: 1px solid #111827;
            padding: 4px 6px;
        }

        .observaciones-titulo {
            width: 150px;
            text-align: center;
            background: {{ $colorFondoAlumno }};
            font-weight: bold;
            text-transform: uppercase;
        }

        .linea-observacion {
            display: block;
            height: 14px;
            border-bottom: 1px solid #9ca3af;
            margin-bottom: 2px;
        }

        .nota {
            margin-top: 5px;
            border: 1px solid #111827;
            background: {{ $esPastel ? '#fdf2f8' : '#f8fafc' }};
            padding: 4px 6px;
            font-size: 8.5px;
            line-height: 1.25;
            text-align: justify;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 100px;
            font-family: ARIAL, DejaVu Sans, sans-serif;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            font-size: 9px;
            color: #111827;
            padding: 0 70px;
        }

        .linea-firma {
            display: block;
            border-top: 1px solid #111827;
            padding-top: 4px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            left: 18px;
            right: 18px;
            bottom: 5px;
            text-align: center;
            font-size: 8px;
            color: #475569;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
        }

        .footer p {
            margin: 0;
            line-height: 1.2;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .encabezado td {
            vertical-align: top;
            border: none;
        }

        .logo-izquierdo {
            text-align: left;
        }

        .logo-izquierdo img {
            width: 100px;
            object-fit: contain;
        }

        .logo-derecho {
            width: 130px;
            text-align: right;
        }

        .logo-derecho img {
            width: 100px;
            object-fit: contain;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #5b6470;
            font-size: 21px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            padding: 0 10px 1px 10px;
            margin-bottom: 3px;
        }

        .titulo-lista {
            color: #001333;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .direccion {
            color: #001333;
            line-height: 1.25;
        }

        .marca-agua {
            position: absolute;
            top: 50px;
            left: 220px;
            width: 560px;
            opacity: 0.07;
            z-index: 0;
        }
    </style>
</head>

<body>
    <div class="pagina">
        @if ($marcaAgua)
            <img src="{{ $marcaAgua }}" class="marca-agua" alt="">
        @endif

        <div class="contenido">

            <table class="encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if ($logoIzquierdo)
                            <img src="{{ $logoIzquierdo }}" alt="">
                        @endif
                    </td>

                    <td class="titulo-centro">
                        <div class="nombre-escuela" style="font-family: ARIAL">
                            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                        </div>

                        <div class="titulo-lista" style="font-size: 20px">
                            LISTA DE ASISTENCIA
                        </div>



                        <div class="direccion" style="font-size: 12px; width:500px; margin: auto;">
                            {{ $escuela->calle ?? 'Francisco I. Madero Ote.' }}

                            @if (!empty($escuela->no_exterior))
                                #{{ $escuela->no_exterior }},
                            @endif

                            @if (!empty($escuela->colonia))
                                Col. {{ $escuela->colonia }},
                            @endif

                            @if (!empty($escuela->ciudad))
                                {{ $escuela->ciudad }},
                            @endif

                            @if (!empty($escuela->municipio))
                                {{ $escuela->municipio }},
                            @endif

                            @if (!empty($escuela->estado))
                                {{ $escuela->estado }}.
                            @endif

                            @if (!empty($escuela->telefono))
                                Tel. {{ $escuela->telefono }}
                            @endif
                        </div>
                    </td>

                    <td class="logo-derecho">
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="datos">
                <tr>
                    <td style="width: 90px;">
                        <span class="label">Maestro(a):</span>
                    </td>

                    <td>
                        <span class="campo campo-largo">
                            {{ strtoupper($nombreDocente ?? 'DOCENTE') }}
                        </span>
                    </td>

                    <td style="width: 95px; text-align: right;">
                        <span class="label">Ciclo Escolar:</span>
                    </td>

                    <td style="width: 135px;">
                        <span class="campo campo-ciclo">
                            {{ $cicloEscolar->inicio_anio ?? '—' }} - {{ $cicloEscolar->fin_anio ?? '—' }}
                        </span>
                    </td>

                    <td style="width: 60px; text-align: right;">
                        <span class="label">Grado:</span>
                    </td>

                    <td style="width: 120px;">
                        <span class="campo campo-corto">
                            {{ $grado->nombre ?? '—' }} ° "{{ $nombreGrupo }}"
                        </span>
                    </td>
                </tr>

                <tr>
                    <td style="width: 90px;">
                        <span class="label">Materia:</span>
                    </td>

                    <td>
                        <span class="campo campo-materia">
                            {{ strtoupper($nombreMateriaAsistencia) }}
                        </span>
                    </td>

                    <td style="width: 95px; text-align: right;">
                        <span class="label">Periodo:</span>
                    </td>

                    <td style="width: 135px;">
                        <span class="campo campo-periodo">
                            {{ $textoPeriodoAsistencia }}
                        </span>
                    </td>

                    <td style="width: 60px; text-align: right;">
                        <span class="label">Turno:</span>
                    </td>

                    <td style="width: 120px;">
                        <span class="campo campo-corto">
                            {{ $turno ?? 'Matutino' }}
                        </span>
                    </td>
                </tr>
            </table>

            <table class="fechas">
                <tr>
                    <td style="text-align: center;">
                        Periodo que comprende del
                        <u>{{ $fechaInicioAsistencia ?: '________________' }}</u>
                        al
                        <u>{{ $fechaFinAsistencia ?: '________________' }}</u>
                    </td>


                </tr>
            </table>



            <table class="tabla-asistencia" style="font-family: ARIAL">
                <thead>
                    <tr>
                        <th rowspan="4" class="th-numero">
                            #
                        </th>

                        <th rowspan="4" class="th-alumno">
                            Nombre y Apellidos
                        </th>

                        <th colspan="20" class="mes">
                            Mes:
                            @if (!empty($nombreMesAsistencia))
                                {{ strtoupper($nombreMesAsistencia) }}
                            @else
                                ______________________________
                            @endif
                        </th>

                        <th rowspan="4" class="resumen resumen-asistencia">
                            <span class="texto-vertical">Asistencia</span>
                        </th>

                        <th rowspan="4" class="resumen resumen-inasistencia">
                            <span class="texto-vertical">Inasistencias</span>
                        </th>

                        <th rowspan="4" class="resumen resumen-retardo">
                            <span class="texto-vertical">Retardos</span>
                        </th>

                        <th rowspan="4" class="resumen resumen-justificante">
                            <span class="texto-vertical">Justificantes</span>
                        </th>

                        <th rowspan="4" class="resumen resumen-permiso">
                            <span class="texto-vertical">Permisos</span>
                        </th>

                        <th rowspan="4" class="resumen resumen-observacion">
                            <span class="texto-vertical">Observaciones</span>
                        </th>
                    </tr>

                    <tr>
                        <th colspan="5" class="semana semana-1">Semana 1</th>
                        <th colspan="5" class="semana semana-2">Semana 2</th>
                        <th colspan="5" class="semana semana-3">Semana 3</th>
                        <th colspan="5" class="semana semana-4">Semana 4</th>
                    </tr>

                    <tr>
                        @for ($semana = 1; $semana <= 4; $semana++)
                            @foreach ($diasSemana as $dia)
                                <th class="dia dia-{{ $semana }} col-dia">
                                    {{ $dia }}
                                </th>
                            @endforeach
                        @endfor
                    </tr>

                    <tr>
                        @for ($dia = 1; $dia <= $totalDias; $dia++)
                            <th class="fecha-dia">
                                {{ $dia }}
                            </th>
                        @endfor
                    </tr>
                </thead>

                <tbody>
                    @forelse ($alumnos as $alumno)
                        <tr class="fila-alumno">
                            <td class="numero">
                                {{ $loop->iteration }}
                            </td>

                            <td class="alumno">
                                {{ $alumno->apellido_paterno }}
                                {{ $alumno->apellido_materno }}
                                {{ $alumno->nombre }}
                            </td>

                            @for ($i = 1; $i <= 20; $i++)
                                <td class="celda"></td>
                            @endfor

                            <td class="celda-resumen"></td>
                            <td class="celda-resumen"></td>
                            <td class="celda-resumen"></td>
                            <td class="celda-resumen"></td>
                            <td class="celda-resumen"></td>
                            <td class="celda-observacion"></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $totalColumnas }}" class="sin-alumnos">
                                No hay alumnos activos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <table class="resumen-grupal">
                <thead>
                    <tr>
                        <th colspan="6">
                            Resumen grupal del mes
                        </th>
                    </tr>

                    <tr>
                        <th>Total alumnos</th>
                        <th>Asistencias</th>
                        <th>Inasistencias</th>
                        <th>Retardos</th>
                        <th>Justificantes</th>
                        <th>Permisos</th>
                    </tr>
                </thead>

                <tbody>
                    <tr>
                        <td>{{ $alumnos->count() }}</td>
                        <td>__________</td>
                        <td>__________</td>
                        <td>__________</td>
                        <td>__________</td>
                        <td>__________</td>
                    </tr>
                </tbody>
            </table>



            <table class="observaciones-generales">
                <tr>
                    <td class="observaciones-titulo">
                        Observaciones generales
                    </td>

                    <td>
                        <span class="linea-observacion"></span>
                        <span class="linea-observacion"></span>
                    </td>
                </tr>
            </table>

            <div class="nota">
                Nota: Registrar A para asistencia, F para falta, R para retardo, J para justificante y P para permiso.
                El resumen puede llenarse al finalizar el mes para dar seguimiento a la asistencia del grupo.
            </div>

            <table class="firmas">
                <tr>
                    <td>
                        <span class="linea-firma">
                            Nombre y firma del docente
                        </span>
                    </td>

                    <td>
                        <span class="linea-firma">
                            Vo. Bo. Dirección / Coordinación
                        </span>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="footer">
        <p>
            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
            · C.C.T. {{ $nivel->cct ?? '—' }}
        </p>

        <p>
            C.
            {{ $escuela->calle ?? '' }}
            No.
            {{ $escuela->no_exterior ?? '' }},
            Col.
            {{ $escuela->colonia ?? '' }},
            C.P.
            {{ $escuela->codigo_postal ?? '' }},
            Cd.
            {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}.
        </p>

        <p>
            Fecha de expedición:
            {{ now()->translatedFormat('d \\d\\e F \\d\\e\\l Y \\a \\l\\a\\s H:i') }}
        </p>
    </div>
</body>

</html>
