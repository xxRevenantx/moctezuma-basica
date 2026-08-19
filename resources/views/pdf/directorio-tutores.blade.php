<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            size: letter landscape;
            margin: 22px 22px 32px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 6px;
            color: #0f172a;
        }

        .seccion {
            width: 100%;
        }

        .salto {
            page-break-before: always;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .encabezado td {
            vertical-align: middle;
            border: 0;
        }

        .logo {
            width: 82px;
            text-align: center;
        }

        .logo img {
            max-width: 72px;
            max-height: 43px;
        }

        .institucion {
            text-align: center;
        }

        .institucion h1 {
            margin: 0;
            color: #006492;
            font-size: 13px;
            line-height: 1.1;
        }

        .institucion h2 {
            margin: 2px 0 0;
            font-size: 9.4px;
            line-height: 1.1;
        }

        .institucion .subtitulo {
            margin-top: 1px;
            color: #475569;
            font-size: 6.8px;
            font-weight: bold;
        }

        .institucion .nivel {
            margin-top: 2px;
            color: #6d8f16;
            font-weight: bold;
            font-size: 7.5px;
        }

        .institucion .meta {
            margin-top: 1px;
            color: #64748b;
            font-size: 5.9px;
        }

        .filtros {
            margin: 4px 0 5px;
            padding: 4px 6px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
        }

        .filtros strong {
            color: #006492;
        }

        .metricas {
            width: 100%;
            border-collapse: separate;
            border-spacing: 2px;
            margin: 0 0 5px;
        }

        .metricas td {
            padding: 3px;
            text-align: center;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
        }

        .metricas .etiqueta {
            display: block;
            color: #64748b;
            font-size: 5.2px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metricas .valor {
            display: block;
            margin-top: 1px;
            font-size: 8.5px;
            font-weight: bold;
        }

        .grupo-titulo {
            margin: 4px 0 1px;
            color: #006492;
            font-size: 8.2px;
            font-weight: bold;
        }

        .grupo-meta {
            margin: 0 0 3px;
            color: #64748b;
            font-size: 5.7px;
        }

        table.directorio {
            width: 100%;
            border-collapse: collapse;
        }

        table.directorio thead {
            display: table-header-group;
        }

        table.directorio tr {
            page-break-inside: avoid;
        }

        table.directorio th {
            padding: 3px 1.5px;
            border: 1px solid #155e75;
            background: #006492;
            color: white;
            font-size: 5px;
            text-transform: uppercase;
            text-align: center;
        }

        table.directorio td {
            padding: 2.3px 1.8px;
            border: 1px solid #94a3b8;
            vertical-align: middle;
            line-height: 1.15;
            word-wrap: break-word;
        }

        table.directorio tbody tr:nth-child(even) td {
            background: #f8fafc;
        }

        .centro {
            text-align: center;
        }

        .faltante {
            color: #9a3412;
            font-style: italic;
        }

        .principal {
            display: inline-block;
            margin-left: 2px;
            color: #166534;
            font-size: 5px;
            font-weight: bold;
        }

        .badge {
            display: inline-block;
            padding: 1px 3px;
            font-size: 4.8px;
            font-weight: bold;
        }

        .multinivel {
            color: #6d28d9;
        }

        .duplicado {
            color: #be123c;
        }

        .firmas {
            width: 100%;
            margin-top: 18px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 35px;
        }

        .linea {
            border-top: 1px solid #334155;
            padding-top: 3px;
            font-size: 6.5px;
            font-weight: bold;
        }

        .cargo {
            color: #64748b;
            font-size: 5.8px;
        }

        .nota {
            margin-top: 7px;
            padding: 4px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #854d0e;
            font-size: 5.6px;
        }

        .pie-fijo {
            position: fixed;
            bottom: -19px;
            left: 0;
            right: 0;
            text-align: center;
            color: #64748b;
            font-size: 5.3px;
        }
    </style>
</head>

<body>
    @foreach ($secciones as $seccion)
        <div class="seccion {{ !$loop->first && $salto_grupo ? 'salto' : '' }}">
            @if ($loop->first || $salto_grupo)
                <table class="encabezado">
                    <tr>
                        <td class="logo">
                            @if ($logo_institucional && is_file($logo_institucional))
                                <img src="{{ $logo_institucional }}" alt="Logo institucional">
                            @endif
                        </td>
                        <td class="institucion">
                            <h1>{{ $escuela?->nombre ?: 'Centro Universitario Moctezuma' }}</h1>
                            <h2>{{ $titulo }}</h2>
                            <div class="subtitulo">{{ $subtitulo }}</div>
                            <div class="nivel">
                                {{ $nivel_nombre }}
                                @if ($nivel)
                                    · C.C.T. {{ $cct }}
                                @else
                                    · {{ $cct }}
                                @endif
                            </div>
                            @if ($direccion_escuela)
                                <div class="meta">{{ $direccion_escuela }}</div>
                            @endif
                            <div class="meta">Fecha de emisión: {{ $fecha_emision }}</div>
                        </td>
                        <td class="logo">
                            @if ($logo_nivel && is_file($logo_nivel))
                                <img src="{{ $logo_nivel }}" alt="Logo">
                            @endif
                        </td>
                    </tr>
                </table>
            @endif

            @if ($loop->first)
                <div class="filtros">
                    @foreach ($resumen_filtros as $etiqueta => $valor)
                        <strong>{{ $etiqueta }}:</strong> {{ $valor }}@if (!$loop->last)
                            &nbsp;·&nbsp;
                        @endif
                    @endforeach
                </div>

                <table class="metricas">
                    <tr>
                        @foreach ([
        'Alumnos' => $metricas['alumnos'] ?? 0,
        'Familias' => $metricas['familias'] ?? 0,
        'Responsables' => $metricas['responsables'] ?? 0,
        'Varios hijos' => $metricas['varios_hijos'] ?? 0,
        'Multinivel' => $metricas['multinivel'] ?? 0,
        'Duplicados' => $metricas['duplicados'] ?? 0,
    ] as $etiqueta => $valor)
                            <td><span class="etiqueta">{{ $etiqueta }}</span><span
                                    class="valor">{{ $valor }}</span></td>
                        @endforeach
                    </tr>
                </table>
            @endif

            <div class="grupo-titulo">{{ $seccion['titulo'] }}</div>
            <div class="grupo-meta">Generación: {{ $seccion['generacion'] }} · Ciclo escolar:
                {{ $seccion['ciclo_escolar'] ?: 'Según filtros' }}</div>

            @if ($vista === 'familias')
                <table class="directorio">
                    <colgroup>
                        <col style="width: 3%">
                        <col style="width: 15%">
                        <col style="width: 8%">
                        <col style="width: 11%">
                        <col style="width: 9%">
                        <col style="width: 19%">
                        <col style="width: 27%">
                        <col style="width: 8%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Padre o tutor</th>
                            <th>Parentesco</th>
                            <th>Teléfono</th>
                            <th>INE</th>
                            <th>Domicilio</th>
                            <th>Alumnos relacionados</th>
                            <th>Niveles</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seccion['filas'] as $fila)
                            <tr>
                                <td class="centro">{{ $fila['numero'] }}</td>
                                <td class="{{ $fila['sin_tutor'] ? 'faltante' : '' }}">
                                    {{ $fila['responsable'] }}
                                    @if ($fila['multinivel'])
                                        <span class="badge multinivel">MULTINIVEL</span>
                                    @endif
                                    @if ($fila['posible_duplicado'])
                                        <span class="badge duplicado">POSIBLE DUPLICADO</span>
                                    @endif
                                </td>
                                <td class="centro">{{ $fila['parentesco'] }}</td>
                                <td class="{{ $fila['sin_telefono'] ? 'faltante' : '' }}">
                                    {{ $fila['telefono'] ?: 'Sin teléfono registrado' }}</td>
                                <td class="centro">{{ $fila['ine'] ?? '' }}</td>
                                <td class="{{ $fila['sin_domicilio'] ? 'faltante' : '' }}">
                                    {{ $fila['domicilio'] ?: 'Sin domicilio registrado' }}</td>
                                <td>
                                    @foreach ($fila['alumnos'] as $alumno)
                                        {{ $alumno['nombre'] }} —
                                        {{ collect([$alumno['nivel'], $alumno['grado'], $alumno['semestre'], $alumno['grupo'] ? 'Grupo ' . $alumno['grupo'] : null])->filter()->join(' · ') }}
                                        @if (!$loop->last)
                                            ;
                                        @endif
                                    @endforeach
                                </td>
                                <td class="centro">{{ $fila['niveles_texto'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <table class="directorio">
                    <colgroup>
                        <col style="width: 3%">
                        <col style="width: 13%">
                        <col style="width: 7%">
                        <col style="width: 10%">
                        <col style="width: 8%">
                        <col style="width: 18%">
                        <col style="width: 15%">
                        <col style="width: 8%">
                        <col style="width: 11%">
                        <col style="width: 7%">
                    </colgroup>
                    <thead>
                        <tr>
                            <th>N.º</th>
                            <th>Padre o tutor</th>
                            <th>Parentesco</th>
                            <th>Teléfono</th>
                            <th>INE</th>
                            <th>Domicilio</th>
                            <th>Alumno</th>
                            <th>Nivel</th>
                            <th>Grado / semestre</th>
                            <th>Grupo</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($seccion['filas'] as $fila)
                            <tr>
                                <td class="centro">{{ $fila['numero'] }}</td>
                                <td class="{{ $fila['sin_tutor'] ? 'faltante' : '' }}">
                                    {{ $fila['responsable'] }}
                                    @if ($fila['es_principal'] && !$fila['sin_tutor'])
                                        <span class="principal">PRINCIPAL</span>
                                    @endif
                                    @if ($fila['posible_duplicado'])
                                        <span class="badge duplicado">POSIBLE DUPLICADO</span>
                                    @endif
                                </td>
                                <td class="centro">{{ $fila['parentesco'] }}</td>
                                <td class="{{ $fila['sin_telefono'] ? 'faltante' : '' }}">{{ $fila['telefono'] }}</td>
                                <td class="centro">{{ $fila['ine'] ?? '' }}</td>
                                <td class="{{ $fila['sin_domicilio'] ? 'faltante' : '' }}">{{ $fila['domicilio'] }}
                                </td>
                                <td>{{ $fila['alumno'] }}</td>
                                <td class="centro">{{ $fila['nivel'] }}</td>
                                <td class="centro">
                                    {{ collect([$fila['grado'], $fila['semestre']])->filter()->join(' · ') }}</td>
                                <td class="centro">{{ $fila['grupo'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">{{ $director }}</div>
                <div class="cargo">{{ $cargo_director }}</div>
            </td>
            <td>
                <div class="linea">{{ $elaborado_por }}</div>
                <div class="cargo">Elaboró</div>
            </td>
        </tr>
    </table>

    @if (
        ($metricas['sin_tutor'] ?? 0) ||
            ($metricas['sin_telefono'] ?? 0) ||
            ($metricas['sin_domicilio'] ?? 0) ||
            ($metricas['sin_curp'] ?? 0))
        <div class="nota">
            Los datos faltantes se conservan para facilitar su corrección posterior. La columna INE permanece vacía
            cuando no existe un folio registrado para el documento “INE del responsable”.
        </div>
    @endif

    <div class="pie-fijo">Documento administrativo confidencial · Centro Universitario Moctezuma</div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
            $pdf->page_text(700, 585, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 6, [0.39, 0.45, 0.55]);
        }
    </script>
</body>

</html>
