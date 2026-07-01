<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>

    <style>
        @page {
            size: letter portrait;
            margin: 24px 34px 28px 34px;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/ARIAL.TTF') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/ARIALBD.TTF') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            color: #0f172a;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            font-size: 9px;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #88ac2e;
            margin-bottom: 10px;
        }

        .encabezado td {
            vertical-align: middle;
            padding: 0 4px 8px 4px;
        }

        .logo-izquierdo,
        .logo-derecho {
            width: 22%;
        }

        .logo-izquierdo {
            text-align: left;
        }

        .logo-derecho {
            text-align: right;
        }

        .logo-edu {
            width: 115px;
            max-height: 48px;
            object-fit: contain;
        }

        .logo-cum {
            width: 105px;
            max-height: 50px;
            object-fit: contain;
        }

        .datos-institucion {
            width: 56%;
            text-align: center;
            line-height: 1.25;
        }

        .nombre-escuela {
            color: #0f2747;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .cct {
            margin-top: 2px;
            color: #006492;
            font-size: 9px;
            font-weight: bold;
        }

        .direccion {
            margin-top: 2px;
            color: #475569;
            font-size: 7px;
        }

        h1 {
            margin: 8px 0 10px 0;
            text-align: center;
            color: #0f2747;
            font-size: 15px;
            line-height: 1.2;
            letter-spacing: .4px;
        }

        .meta {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 13px;
            table-layout: fixed;
        }

        .meta td {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            padding: 5px 6px;
            text-align: center;
            line-height: 1.25;
        }

        .meta strong {
            color: #334155;
        }

        .grupo {
            margin-top: 12px;
        }

        .grupo + .grupo {
            margin-top: 18px;
        }

        .grupo-titulo {
            padding: 7px 9px;
            border-left: 5px solid #006492;
            background: #eef7fb;
            color: #006492;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .grupo-resumen {
            margin: 4px 0 6px 0;
            color: #475569;
            font-size: 8px;
            font-style: italic;
        }

        .tabla {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .tabla thead {
            display: table-header-group;
        }

        .tabla tr {
            page-break-inside: avoid;
        }

        .tabla th {
            border: 1px solid #475569;
            background: #006492;
            color: white;
            padding: 5px 4px;
            text-align: center;
            font-size: 7.5px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .tabla td {
            border: 1px solid #94a3b8;
            padding: 5px 4px;
            vertical-align: middle;
            line-height: 1.25;
        }

        .tabla tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .col-numero {
            width: 6%;
            text-align: center;
        }

        .col-lugar {
            width: 12%;
            text-align: center;
        }

        .col-alumno {
            width: 34%;
            text-align: left;
            font-weight: bold;
        }

        .col-matricula {
            width: 19%;
            text-align: center;
        }

        .col-grupo {
            width: 11%;
            text-align: center;
        }

        .col-promedio {
            width: 18%;
            text-align: center;
            font-weight: bold;
        }

        .lugar-destacado {
            display: inline-block;
            min-width: 54px;
            padding: 3px 5px;
            border-radius: 8px;
            background: #eef2ff;
            color: #4338ca;
            font-weight: bold;
        }

        .pendiente {
            color: #b45309;
            font-weight: bold;
        }

        .sin-lugar {
            color: #64748b;
        }

        .firmas {
            width: 100%;
            margin-top: 28px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .firmas td {
            width: 50%;
            padding: 0 22px;
            text-align: center;
            vertical-align: bottom;
        }

        .linea {
            width: 82%;
            margin: 0 auto 4px auto;
            border-top: 1px solid #64748b;
        }

        .firma-nombre {
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .firma-cargo {
            margin-top: 2px;
            color: #475569;
            font-size: 7px;
            text-transform: uppercase;
        }

        .nota {
            margin-top: 9px;
            color: #64748b;
            font-size: 7px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php
        $logoEdu = public_path('imagenes/logo-edu.png');
        $logoCum = public_path('imagenes/logo-letra.png');
        $generacionTexto = $generacion
            ? $generacion->anio_ingreso . '-' . $generacion->anio_egreso
            : 'Todas';
        $grupoTexto = $grupo_seleccionado?->asignacionGrupo?->nombre ?? 'Todos los grupos';
        $semestreTexto = $es_bachillerato ? ($semestre?->numero ?? '—') : 'No aplica';
    @endphp

    <table class="encabezado">
        <tr>
            <td class="logo-izquierdo">
                @if (is_file($logoEdu))
                    <img class="logo-edu" src="{{ $logoEdu }}" alt="">
                @endif
            </td>

            <td class="datos-institucion">
                <div class="nombre-escuela">{{ $nombre_escuela }}</div>
                <div class="cct">C.C.T. {{ $nivel->cct ?: 'SIN C.C.T.' }}</div>
                <div class="direccion">{{ $direccion }}</div>
            </td>

            <td class="logo-derecho">
                @if (is_file($logoCum))
                    <img class="logo-cum" src="{{ $logoCum }}" alt="">
                @endif
            </td>
        </tr>
    </table>

    <h1>{{ $titulo }}</h1>

    <table class="meta">
        <tr>
            <td><strong>Ciclo:</strong><br>{{ $ciclo->inicio_anio }}-{{ $ciclo->fin_anio }}</td>
            <td><strong>Nivel:</strong><br>{{ $nivel->nombre }}</td>
            <td><strong>Grado:</strong><br>{{ $grado->nombre }}</td>
            <td><strong>Grupo:</strong><br>{{ $grupoTexto }}</td>
        </tr>
        <tr>
            <td><strong>Generación:</strong><br>{{ $generacionTexto }}</td>
            <td><strong>Semestre:</strong><br>{{ $semestreTexto }}</td>
            <td colspan="2"><strong>Fecha:</strong><br>{{ $fecha }}</td>
        </tr>
    </table>

    @foreach ($grupos as $grupo)
        <section class="grupo">
            <div class="grupo-titulo">{{ $grupo['titulo'] }}</div>

            <div class="grupo-resumen">
                Total de alumnos: {{ $grupo['total'] }}
                @unless ($es_preescolar)
                    · Promedio del grupo: {{ $grupo['promedio_grupo'] }}
                @endunless
            </div>

            <table class="tabla">
                <thead>
                    <tr>
                        <th class="col-numero">N.º</th>
                        <th class="col-lugar">Lugar</th>
                        <th class="col-alumno">Alumno</th>
                        <th class="col-matricula">Matrícula</th>
                        <th class="col-grupo">Grupo</th>
                        <th class="col-promedio">Promedio final</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo['filas'] as $fila)
                        @php
                            $tieneLugar = is_numeric($fila['lugar'] ?? null);
                            $estaCompleto = (bool) ($fila['completo'] ?? false);
                            $textoLugar = $tieneLugar
                                ? ((int) $fila['lugar']) . '° lugar'
                                : ($estaCompleto ? 'Sin lugar' : 'Pendiente');
                            $textoPromedio = $es_preescolar
                                ? 'No aplica'
                                : (!$estaCompleto
                                    ? 'Pendiente'
                                    : (is_numeric($fila['promedio'] ?? null)
                                        ? number_format((float) $fila['promedio'], 1, '.', '')
                                        : '—'));
                        @endphp

                        <tr>
                            <td class="col-numero">{{ $loop->iteration }}</td>
                            <td class="col-lugar">
                                @if ($tieneLugar)
                                    <span class="lugar-destacado">{{ $textoLugar }}</span>
                                @elseif (!$estaCompleto)
                                    <span class="pendiente">{{ $textoLugar }}</span>
                                @else
                                    <span class="sin-lugar">{{ $textoLugar }}</span>
                                @endif
                            </td>
                            <td class="col-alumno">{{ mb_strtoupper($fila['alumno'] ?? '') }}</td>
                            <td class="col-matricula">{{ $fila['matricula'] ?? '' }}</td>
                            <td class="col-grupo">{{ $fila['grupo'] ?? '' }}</td>
                            <td class="col-promedio">{{ $textoPromedio }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <table class="firmas">
                <tr>
                    <td>
                        <div class="linea"></div>
                        <div class="firma-nombre">{{ $grupo['docente'] }}</div>
                        <div class="firma-cargo">{{ $es_preescolar ? 'Educadora titular' : 'Docente titular' }}</div>
                    </td>
                    <td>
                        <div class="linea"></div>
                        <div class="firma-nombre">{{ $director }}</div>
                        <div class="firma-cargo">{{ $cargo_director }}</div>
                    </td>
                </tr>
            </table>
        </section>
    @endforeach

    <div class="nota">
        Los lugares se asignan por grupo con numeración consecutiva en caso de empate: 1.º, 1.º, 2.º, 3.º.
        @if ($es_preescolar)
            En preescolar se respetan los lugares asignados manualmente y el promedio no aplica.
        @else
            Los alumnos con información incompleta aparecen al final como pendientes.
        @endif
    </div>
</body>
</html>
