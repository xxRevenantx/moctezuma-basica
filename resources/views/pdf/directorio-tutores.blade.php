<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page {
            size: letter portrait;
            margin: 24px 24px 34px 24px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 6.2px;
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
            width: 72px;
            text-align: center;
        }

        .logo img {
            max-width: 66px;
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
            font-size: 9.5px;
            line-height: 1.1;
        }

        .institucion .nivel {
            margin-top: 2px;
            color: #6d8f16;
            font-weight: bold;
            font-size: 8px;
        }

        .institucion .meta {
            margin-top: 2px;
            color: #64748b;
            font-size: 6.4px;
        }

        .filtros {
            margin: 4px 0 5px;
            padding: 5px 7px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            border-radius: 5px;
        }

        .filtros strong {
            color: #006492;
        }

        .metricas {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            margin: 0 0 6px;
        }

        .metricas td {
            padding: 4px;
            text-align: center;
            border: 1px solid #dbe4ee;
            background: #f8fafc;
        }

        .metricas .etiqueta {
            display: block;
            color: #64748b;
            font-size: 6px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .metricas .valor {
            display: block;
            margin-top: 1px;
            font-size: 10px;
            font-weight: bold;
        }

        .grupo-titulo {
            margin: 4px 0 1px;
            color: #006492;
            font-size: 9px;
            font-weight: bold;
        }

        .grupo-meta {
            margin: 0 0 4px;
            color: #64748b;
            font-size: 6.3px;
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
            padding: 3px 2px;
            border: 1px solid #155e75;
            background: #006492;
            color: white;
            font-size: 5.4px;
            text-transform: uppercase;
            text-align: center;
        }

        table.directorio td {
            padding: 2.5px 2px;
            border: 1px solid #94a3b8;
            vertical-align: middle;
            line-height: 1.18;
            overflow-wrap: break-word;
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
            font-size: 5.7px;
            font-weight: bold;
        }

        .firmas {
            width: 100%;
            margin-top: 24px;
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
            font-size: 7px;
            font-weight: bold;
        }

        .cargo {
            color: #64748b;
            font-size: 6.2px;
        }

        .nota {
            margin-top: 9px;
            padding: 5px;
            border: 1px solid #fde68a;
            background: #fffbeb;
            color: #854d0e;
            font-size: 6.2px;
        }

        .pie-fijo {
            position: fixed;
            bottom: -20px;
            left: 0;
            right: 0;
            text-align: center;
            color: #64748b;
            font-size: 5.8px;
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
                            <div class="nivel">{{ $nivel->nombre }} · C.C.T. {{ $nivel->cct ?: 'SIN C.C.T.' }}</div>
                            @if ($direccion_escuela)
                                <div class="meta">{{ $direccion_escuela }}</div>
                            @endif
                            <div class="meta">Fecha de emisión: {{ $fecha_emision }}</div>
                        </td>
                        <td class="logo">
                            @if ($logo_nivel && is_file($logo_nivel))
                                <img src="{{ $logo_nivel }}" alt="Logo del nivel">
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
        'Alumnos' => $metricas['alumnos'],
        'Responsables' => $metricas['responsables'],
        'Filas' => $metricas['filas'],
        'Sin tutor' => $metricas['sin_tutor'],
        'Sin teléfono' => $metricas['sin_telefono'],
        'Sin domicilio' => $metricas['sin_domicilio'],
    ] as $etiqueta => $valor)
                            <td>
                                <span class="etiqueta">{{ $etiqueta }}</span>
                                <span class="valor">{{ $valor }}</span>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif

            <div class="grupo-titulo">{{ $seccion['titulo'] }}</div>
            <div class="grupo-meta">
                Generación: {{ $seccion['generacion'] }} · Ciclo escolar: {{ $seccion['ciclo_escolar'] }}
            </div>

            <table class="directorio">
                <colgroup>
                    <col>
                    <col style="width: 17%">
                    <col style="width: 10%">
                    <col style="width: 13%">
                    <col style="width: 22%">
                    <col style="width: 19%">
                    <col style="width: 15%">
                </colgroup>
                <thead>
                    <tr>
                        <th>N.º</th>
                        <th>Padre o tutor</th>
                        <th>Parentesco</th>
                        <th>Teléfono</th>
                        <th>Domicilio</th>
                        <th>Alumno</th>
                        <th>Nivel / grado / grupo</th>
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
                            </td>
                            <td class="centro">{{ $fila['parentesco'] }}</td>
                            <td class="{{ $fila['sin_telefono'] ? 'faltante' : '' }}">{{ $fila['telefono'] }}</td>
                            <td class="{{ $fila['sin_domicilio'] ? 'faltante' : '' }}">{{ $fila['domicilio'] }}</td>
                            <td>{{ $fila['alumno'] }}</td>
                            <td class="centro">
                                {{ collect([
                                    $fila['nivel'],
                                    collect([$fila['grado'], $fila['semestre']])->filter()->join(' · '),
                                    $fila['grupo'] ? 'Grupo ' . $fila['grupo'] : null,
                                ])->filter()->join(' · ') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endforeach

    <table class="firmas">
        <tr>
            <td>
                <div class="linea">{{ $director }}</div>
                <div class="cargo">Dirección del nivel</div>
            </td>
            <td>
                <div class="linea">{{ $elaborado_por }}</div>
                <div class="cargo">Elaboró</div>
            </td>
        </tr>
    </table>

    @if ($metricas['sin_tutor'] || $metricas['sin_telefono'] || $metricas['sin_domicilio'])
        <div class="nota">
            Los textos “Sin tutor registrado”, “Sin teléfono registrado” y “Sin domicilio registrado” indican
            información pendiente en el expediente del alumno; no impiden la generación del directorio.
        </div>
    @endif

    <div class="pie-fijo">Documento administrativo confidencial · Centro Universitario Moctezuma</div>

    <script type="text/php">
        if (isset($pdf)) {
            $font = $fontMetrics->get_font('DejaVu Sans', 'normal');
            $pdf->page_text(455, 770, 'Página {PAGE_NUM} de {PAGE_COUNT}', $font, 6, [0.39, 0.45, 0.55]);
        }
    </script>
</body>

</html>
