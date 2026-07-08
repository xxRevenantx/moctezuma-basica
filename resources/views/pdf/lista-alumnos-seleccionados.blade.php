<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 22px 24px 28px; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, sans-serif;
            color: #0f172a;
            font-size: 7.5px;
        }
        .grupo-pagina { page-break-after: always; }
        .grupo-pagina:last-child { page-break-after: auto; }
        .encabezado {
            width: 100%;
            border-collapse: collapse;
            border-bottom: 3px solid #006492;
            margin-bottom: 7px;
        }
        .encabezado td { vertical-align: middle; padding: 2px 7px 7px; }
        .logo-celda { width: 16%; text-align: center; }
        .logo { max-width: 112px; max-height: 58px; }
        .titulo-celda { text-align: center; }
        .escuela { color: #006492; font-size: 15px; font-weight: 900; }
        .titulo { margin-top: 3px; font-size: 11px; font-weight: 900; }
        .grupo { margin-top: 3px; color: #88AC2E; font-size: 8.5px; font-weight: 900; }
        .meta { margin-top: 3px; color: #64748b; font-size: 7px; }
        .linea-verde { height: 3px; background: #88AC2E; margin: -7px 0 7px; }
        .resumen { width: 100%; border-collapse: separate; border-spacing: 3px 0; margin: 0 -3px 8px; }
        .resumen td { padding: 5px 3px; text-align: center; border: 1px solid #cbd5e1; background: #f8fafc; }
        .resumen .etiqueta { color: #64748b; font-size: 6px; font-weight: 700; text-transform: uppercase; }
        .resumen .valor { margin-top: 1px; color: #0f172a; font-size: 11px; font-weight: 900; }
        .datos-grupo { width: 100%; border-collapse: collapse; margin-bottom: 7px; }
        .datos-grupo td { border: 1px solid #cbd5e1; background: #f8fafc; padding: 4px 6px; }
        .datos-grupo small { display: block; color: #64748b; font-size: 5.8px; font-weight: 700; text-transform: uppercase; }
        .datos-grupo strong { display: block; margin-top: 1px; font-size: 7.3px; }
        table.alumnos { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .alumnos thead { display: table-header-group; }
        .alumnos tr { page-break-inside: avoid; }
        .alumnos th {
            border: 1px solid #075985;
            background: #006492;
            color: #ffffff;
            padding: 4px 2px;
            font-size: 6.4px;
            font-weight: 900;
            text-align: center;
            text-transform: uppercase;
        }
        .alumnos td {
            border: 1px solid #94a3b8;
            padding: 4px 3px;
            vertical-align: middle;
            line-height: 1.2;
            overflow-wrap: break-word;
        }
        .alumnos tbody tr:nth-child(even) td { background: #f8fafc; }
        .centro { text-align: center; }
        .izquierda { text-align: left; }
        .foto { width: 28px; height: 34px; object-fit: cover; border: 1px solid #cbd5e1; }
        .espacio-firma { height: 24px; }
        .firma-responsable { margin-top: 26px; text-align: center; page-break-inside: avoid; }
        .firma-responsable .linea { width: 260px; margin: 0 auto 3px; border-top: 1px solid #334155; }
        .firma-responsable strong { font-size: 8px; }
        .firma-responsable span { display: block; color: #64748b; font-size: 7px; }
        .pie {
            position: fixed;
            bottom: -17px;
            left: 0;
            right: 0;
            color: #64748b;
            font-size: 6px;
            text-align: center;
        }
    </style>
</head>
<body>
    @php($consecutivo = 1)

    @foreach ($grupos as $grupo)
        <section class="grupo-pagina">
            <table class="encabezado">
                <tr>
                    <td class="logo-celda">
                        @if ($logo && is_file($logo))
                            <img class="logo" src="{{ $logo }}" alt="Logo institucional">
                        @endif
                    </td>
                    <td class="titulo-celda">
                        <div class="escuela">{{ $escuela?->nombre ?: 'Centro Universitario Moctezuma' }}</div>
                        <div class="titulo">{{ $titulo }}</div>
                        <div class="grupo">{{ $grupo['titulo'] }}</div>
                        @if ($grupo['cct'])
                            <div class="meta"><strong>C.C.T. {{ $grupo['cct'] }}</strong></div>
                        @endif
                        @if ($direccion_escuela !== '')
                            <div class="meta">{{ $direccion_escuela }}</div>
                        @endif
                        <div class="meta">Fecha de emisión: {{ $fecha_emision }}</div>
                    </td>
                    <td class="logo-celda"></td>
                </tr>
            </table>
            <div class="linea-verde"></div>

            @if ($mostrar_estadisticas)
                <table class="resumen">
                    <tr>
                        @foreach ([
                            'Total' => $estadisticas['total'],
                            'Hombres' => $estadisticas['hombres'],
                            'Mujeres' => $estadisticas['mujeres'],
                            'Activos' => $estadisticas['activos'],
                            'Bajas' => $estadisticas['bajas'],
                            'Egresados' => $estadisticas['egresados'],
                        ] as $etiqueta => $valor)
                            <td>
                                <div class="etiqueta">{{ $etiqueta }}</div>
                                <div class="valor">{{ $valor }}</div>
                            </td>
                        @endforeach
                    </tr>
                </table>
            @endif

            @if ($grupo['clave'] !== 'general')
                <table class="datos-grupo">
                    <tr>
                        <td><small>Nivel</small><strong>{{ $grupo['nivel'] }}</strong></td>
                        <td><small>Grado / semestre</small><strong>{{ $grupo['grado'] }}</strong></td>
                        <td><small>Grupo</small><strong>{{ $grupo['grupo'] }}</strong></td>
                        <td><small>Generación</small><strong>{{ $grupo['generacion'] }}</strong></td>
                        <td><small>Ciclo escolar</small><strong>{{ $grupo['ciclo'] }}</strong></td>
                    </tr>
                </table>
            @endif

            @php
                $pesoTotal = collect($columnas)->sum(fn ($columna) => $config_columnas[$columna]['peso']);
            @endphp

            <table class="alumnos">
                <colgroup>
                    @foreach ($columnas as $columna)
                        <col style="width: {{ number_format(($config_columnas[$columna]['peso'] / max(1, $pesoTotal)) * 100, 2, '.', '') }}%">
                    @endforeach
                </colgroup>
                <thead>
                    <tr>
                        @foreach ($columnas as $columna)
                            <th>{{ $config_columnas[$columna]['label'] }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($grupo['alumnos'] as $alumno)
                        <tr>
                            @foreach ($columnas as $columna)
                                <td class="{{ $config_columnas[$columna]['align'] === 'center' ? 'centro' : 'izquierda' }} {{ in_array($columna, ['firma', 'observaciones'], true) ? 'espacio-firma' : '' }}">
                                    @switch($columna)
                                        @case('numero')
                                            {{ $consecutivo }}
                                            @break
                                        @case('foto')
                                            @if ($alumno->foto_data_uri)
                                                <img class="foto" src="{{ $alumno->foto_data_uri }}" alt="">
                                            @endif
                                            @break
                                        @case('matricula')
                                            {{ $alumno->matricula ?: '—' }}
                                            @break
                                        @case('folio')
                                            {{ $alumno->folio ?: '—' }}
                                            @break
                                        @case('curp')
                                            {{ $alumno->curp ?: '—' }}
                                            @break
                                        @case('nombre')
                                            {{ mb_strtoupper(trim(($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? '') . ' ' . ($alumno->nombre ?? ''))) }}
                                            @break
                                        @case('sexo')
                                            {{ $alumno->genero === 'H' ? 'Hombre' : ($alumno->genero === 'M' ? 'Mujer' : '—') }}
                                            @break
                                        @case('nivel')
                                            {{ $alumno->nivel?->nombre ?? '—' }}
                                            @break
                                        @case('grado')
                                            {{ $alumno->semestre ? $alumno->semestre->numero . '° semestre' : ($alumno->grado?->nombre ?? '—') }}
                                            @break
                                        @case('grupo')
                                            {{ $alumno->grupo?->asignacionGrupo?->nombre ?? '—' }}
                                            @break
                                        @case('generacion')
                                            {{ $alumno->generacion ? $alumno->generacion->anio_ingreso . '-' . $alumno->generacion->anio_egreso : '—' }}
                                            @break
                                        @case('ciclo')
                                            {{ $alumno->ciclo?->ciclo ?? '—' }}
                                            @break
                                        @case('estatus')
                                            @php($estatusTexto = \Illuminate\Support\Str::contains(\Illuminate\Support\Str::lower((string) $alumno->estatus), 'egres') ? 'Egresado' : (filled($alumno->estatus) ? \Illuminate\Support\Str::headline((string) $alumno->estatus) : ($alumno->activo ? 'Activo' : 'Baja')))
                                            {{ $estatusTexto }}
                                            @break
                                        @case('firma')
                                            &nbsp;
                                            @break
                                        @case('observaciones')
                                            &nbsp;
                                            @break
                                    @endswitch
                                </td>
                            @endforeach
                        </tr>
                        @php($consecutivo++)
                    @endforeach
                </tbody>
            </table>

            @if ($responsable !== '')
                <div class="firma-responsable">
                    <div class="linea"></div>
                    <strong>{{ $responsable }}</strong>
                    <span>Responsable</span>
                </div>
            @endif
        </section>
    @endforeach

    <div class="pie">Documento generado por el Sistema Escolar del Centro Universitario Moctezuma</div>
</body>
</html>
