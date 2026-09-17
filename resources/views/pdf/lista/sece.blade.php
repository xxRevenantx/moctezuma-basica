<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Lista SECE</title>

    @php
        $sece = $sece ?? [
            'filas' => collect(),
            'resumen' => [
                'total' => 0,
                'hombres' => 0,
                'mujeres' => 0,
                'sin_sexo' => 0,
                'edad_promedio' => null,
                'edad_minima' => null,
                'edad_maxima' => null,
            ],
            'por_edad' => collect(),
            'situaciones' => collect(),
            'es_bachillerato' => false,
            'fecha_calculo' => now(),
        ];

        $filasSece = $sece['filas'] ?? collect();
        $resumenSece = $sece['resumen'] ?? [];
        $porEdadSece = $sece['por_edad'] ?? collect();
        $situacionesSece = $sece['situaciones'] ?? collect();
        $fechaCalculo = $sece['fecha_calculo'] ?? now();

        $nombreNivel = mb_strtoupper($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL'), 'UTF-8');
        $nombreGrado = $grado->nombre ?? ($grado->grado ?? '—');
        $nombreGrupo = optional($grupo->asignacionGrupo)->nombre ?? '—';
        $nombreGeneracion = $generacion->etiqueta ?? ($generacion->nombre ?? '—');
        $nombreCiclo =
            $cicloEscolar->nombre ?? ($cicloEscolar->inicio_anio ?? '') . '-' . ($cicloEscolar->fin_anio ?? '');
        $turnoTexto = $turno ?? 'Matutino';

        $totalAlumnos = (int) ($resumenSece['total'] ?? 0);
        $fuenteFila = $totalAlumnos > 30 ? '7.2px' : ($totalAlumnos > 22 ? '7.8px' : '8.5px');
        $altoFila = $totalAlumnos > 30 ? '16px' : ($totalAlumnos > 22 ? '18px' : '20px');

        $directorPersona = optional($director)->director;
        $nombreDirector = trim(
            implode(
                ' ',
                array_filter([
                    optional($directorPersona)->titulo,
                    optional($directorPersona)->nombre,
                    optional($directorPersona)->apellido_paterno,
                    optional($directorPersona)->apellido_materno,
                ]),
            ),
        );

        $nombreDocenteFirma = trim(
            implode(
                ' ',
                array_filter([
                    optional($docente)->titulo,
                    optional($docente)->nombre,
                    optional($docente)->apellido_paterno,
                    optional($docente)->apellido_materno,
                ]),
            ),
        );
    @endphp

    <style>
        @page {
            margin: 16px 22px 28px 22px;
            size: letter landscape;
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

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            color: #0f2942;
            font-size: 9px;
        }

        .pagina {
            position: relative;
            width: 100%;
        }

        .marca-agua {
            position: fixed;
            top: 105px;
            left: 180px;
            width: 420px;
            opacity: 0.055;
            z-index: 0;
        }

        .contenido {
            position: relative;
            z-index: 2;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }

        .encabezado td {
            border: none;
            vertical-align: middle;
        }

        .logo-izquierdo,
        .logo-derecho {
            width: 105px;
        }

        .logo-izquierdo {
            text-align: left;
        }

        .logo-derecho {
            text-align: right;
        }

        .logo-izquierdo img {
            width: 78px;
            max-height: 78px;
            object-fit: contain;
        }

        .logo-derecho img {
            width: 90px;
            max-height: 78px;
            object-fit: contain;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #334155;
            font-size: 14px;
            font-weight: 700;
            letter-spacing: .45px;
            border-top: 1px solid #94a3b8;
            border-bottom: 1px solid #94a3b8;
            padding: 1px 12px 2px;
            text-transform: uppercase;
        }

        .titulo-lista {
            margin-top: 4px;
            color: #006492;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: .7px;
            text-transform: uppercase;
        }

        .subtitulo-lista {
            margin-top: 1px;
            color: #111827;
            font-size: 11px;
            font-weight: 700;
        }

        .direccion {
            margin-top: 3px;
            font-size: 8px;
            line-height: 1.2;
            color: #475569;
        }

        .contexto {
            width: 100%;
            border-collapse: collapse;
            margin: 5px 0 7px 0;
            border: 1px solid #9fb3c8;
        }

        .contexto td {
            padding: 4px 6px;
            border: 1px solid #d3dce5;
            vertical-align: middle;
        }

        .contexto .etiqueta {
            color: #475569;
            font-size: 7px;
            text-transform: uppercase;
        }

        .contexto .valor {
            margin-top: 1px;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tabla-alumnos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 2px;
        }

        .tabla-alumnos thead {
            display: table-header-group;
        }

        .tabla-alumnos tr {
            page-break-inside: avoid;
        }

        .tabla-alumnos th,
        .tabla-alumnos td {
            border: 1px solid #334155;
            vertical-align: middle;
        }

        .tabla-alumnos th {
            padding: 5px 3px;
            background: #88AC2E;
            color: #0b1725;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-alumnos td {
            min-height: {{ $altoFila }};
            padding: 1px 4px;
            background: rgba(255, 255, 255, .92);
            color: #0f2942;
            font-size: {{ $fuenteFila }};
            line-height: 1.12;
            text-transform: uppercase;
        }

        .tabla-alumnos tbody tr:nth-child(even) td {
            background: rgba(245, 249, 252, .94);
        }

        .centrado {
            text-align: center;
        }

        .nombre-alumno {
            font-weight: 700;
        }

        .entidad-no-reconocida {
            color: #991b1b;
            font-weight: 700;
        }

        .nota-curp {
            margin: 5px 1px 0;
            color: #475569;
            font-size: 7px;
            line-height: 1.3;
        }

        .seccion-estadistica {
            margin-top: 9px;
            page-break-inside: avoid;
        }

        .titulo-seccion {
            padding: 4px 7px;
            background: #006492;
            color: #ffffff;
            font-size: 9px;
            font-weight: 700;
            letter-spacing: .4px;
            text-transform: uppercase;
        }

        .resumen {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .resumen td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            text-align: center;
            background: #f8fafc;
        }

        .resumen .numero-resumen {
            display: block;
            color: #006492;
            font-size: 13px;
            font-weight: 700;
        }

        .resumen .label-resumen {
            display: block;
            margin-top: 1px;
            color: #475569;
            font-size: 6.8px;
            text-transform: uppercase;
        }

        .estadisticas-doble {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .estadisticas-doble>tbody>tr>td {
            width: 50%;
            border: none;
            vertical-align: top;
        }

        .estadisticas-doble>tbody>tr>td:first-child {
            padding-right: 4px;
        }

        .estadisticas-doble>tbody>tr>td:last-child {
            padding-left: 4px;
        }

        .mini-titulo {
            padding: 4px 5px;
            background: #e8f1f6;
            border: 1px solid #a9bfd0;
            border-bottom: none;
            color: #0f2942;
            font-size: 8px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tabla-estadistica {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-estadistica th,
        .tabla-estadistica td {
            border: 1px solid #a9bfd0;
            padding: 0px 4px;
            font-size: 7.4px;
        }

        .tabla-estadistica th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
            text-align: center;
        }

        .tabla-estadistica td {
            background: rgba(255, 255, 255, .96);
        }

        .tabla-estadistica .total-estadistica td {
            background: #eef6e4;
            font-weight: 700;
        }

        .firmas {
            width: 100%;
            margin-top: 22px;
            border-collapse: collapse;
            page-break-inside: avoid;
        }

        .firmas td {
            border: none;
            padding-top: 20px;
            text-align: center;
            font-size: 8px;
            vertical-align: bottom;
        }

        .linea-firma {
            display: inline-block;
            min-width: 260px;
            padding: 0 10px 2px;
            border-bottom: 1px solid #334155;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
        }

        .cargo-firma {
            margin-top: 3px;
            color: #475569;
            font-size: 7px;
        }

        .footer {
            position: fixed;
            left: 22px;
            right: 22px;
            bottom: 7px;
            z-index: 5;
            padding-top: 3px;
            border-top: 1px solid #a9bfd0;
            color: #64748b;
            font-size: 6.5px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="pagina">
        @if (!empty($marcaAgua))
            <img src="{{ $marcaAgua }}" class="marca-agua" alt="">
        @endif

        <div class="contenido">
            <table class="encabezado">
                <tr>
                    <td class="logo-izquierdo">
                        @if (!empty($logoIzquierdo))
                            <img src="{{ $logoIzquierdo }}" alt="">
                        @endif
                    </td>

                    <td class="titulo-centro">
                        <div class="nombre-escuela">
                            {{ mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.', 'UTF-8') }}
                        </div>

                        <div class="titulo-lista">LISTA SECE</div>
                        <div class="subtitulo-lista">C.C.T. {{ $nivel->cct ?? '—' }}</div>

                        <div class="direccion">
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
                        @if (!empty($logoDerecho))
                            <img src="{{ $logoDerecho }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="contexto">
                <tr>
                    <td>
                        <div class="etiqueta">Ciclo escolar</div>
                        <div class="valor">{{ $nombreCiclo ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Generación</div>
                        <div class="valor">{{ $nombreGeneracion ?: '—' }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Nivel</div>
                        <div class="valor">{{ $nombreNivel }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Grado</div>
                        <div class="valor">{{ $nombreGrado !== '' ? $nombreGrado . '°' : '—' }}</div>
                    </td>
                    <td>
                        <div class="etiqueta">Grupo</div>
                        <div class="valor">
                            {{ $nombreGrupo !== '—' ? '"' . mb_strtoupper($nombreGrupo, 'UTF-8') . '"' : '—' }}</div>
                    </td>
                    @if ($esBachillerato)
                        <td>
                            <div class="etiqueta">Semestre</div>
                            <div class="valor">
                                {{ optional($semestre)->numero ? optional($semestre)->numero . '°' : '—' }}</div>
                        </td>
                    @else
                        <td>
                            <div class="etiqueta">Turno</div>
                            <div class="valor">{{ $turnoTexto }}</div>
                        </td>
                    @endif
                </tr>
            </table>

            <table class="tabla-alumnos">
                <colgroup>
                    <col style="width: 4%;">
                    <col style="width: 12%;">
                    <col style="width: 24%;">
                    <col style="width: 18%;">
                    <col style="width: 6%;">
                    <col style="width: 10%;">
                    <col style="width: 6%;">
                    <col style="width: 20%;">
                </colgroup>
                <thead>
                    <tr>
                        <th>No.</th>
                        <th>Matrícula</th>
                        <th>Nombre completo</th>
                        <th>CURP</th>
                        <th>Sexo</th>
                        <th>Fecha de nacimiento</th>
                        <th>Edad actual</th>
                        <th>Entidad de nacimiento (CURP)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($filasSece as $fila)
                        <tr>
                            <td class="centrado">{{ $loop->iteration }}</td>
                            <td class="centrado">{{ $fila['matricula'] ?: '—' }}</td>
                            <td class="nombre-alumno">{{ $fila['nombre_completo'] ?: '—' }}</td>
                            <td class="centrado">{{ $fila['curp'] ?: '—' }}</td>
                            <td class="centrado">{{ $fila['sexo'] }}</td>
                            <td class="centrado">
                                {{ !empty($fila['fecha_nacimiento']) ? \Carbon\Carbon::parse($fila['fecha_nacimiento'])->format('d/m/Y') : '—' }}
                            </td>
                            <td class="centrado">
                                {{ is_int($fila['edad_actual']) ? $fila['edad_actual'] . ' años' : '—' }}
                            </td>
                            <td class="centrado {{ !$fila['entidad_reconocida'] ? 'entidad-no-reconocida' : '' }}">
                                {{ $fila['entidad_etiqueta'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="centrado" style="padding: 15px; text-transform: none;">
                                No hay alumnos activos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="nota-curp">
                <strong>Referencia:</strong> la entidad de nacimiento se obtiene directamente de las posiciones 12–13 de
                la CURP y se interpreta con el catálogo de entidades de RENAPO. La edad mostrada es la edad actual al
                {{ \Carbon\Carbon::parse($fechaCalculo)->format('d/m/Y') }}. La situación de edad escolar conserva el
                criterio institucional del ciclo seleccionado.
            </div>

            <div class="seccion-estadistica">
                <div class="titulo-seccion">Estadística del grupo</div>

                <table class="resumen">
                    <tr>
                        <td>
                            <span class="numero-resumen">{{ $resumenSece['hombres'] ?? 0 }}</span>
                            <span class="label-resumen">Hombres</span>
                        </td>
                        <td>
                            <span class="numero-resumen">{{ $resumenSece['mujeres'] ?? 0 }}</span>
                            <span class="label-resumen">Mujeres</span>
                        </td>
                        <td>
                            <span class="numero-resumen">{{ $resumenSece['total'] ?? 0 }}</span>
                            <span class="label-resumen">Total</span>
                        </td>
                        <td>
                            <span
                                class="numero-resumen">{{ $resumenSece['edad_promedio'] !== null ? number_format((float) $resumenSece['edad_promedio'], 1) : '—' }}</span>
                            <span class="label-resumen">Edad promedio</span>
                        </td>
                        <td>
                            <span class="numero-resumen">{{ $resumenSece['edad_minima'] ?? '—' }}</span>
                            <span class="label-resumen">Edad mínima</span>
                        </td>
                        <td>
                            <span class="numero-resumen">{{ $resumenSece['edad_maxima'] ?? '—' }}</span>
                            <span class="label-resumen">Edad máxima</span>
                        </td>
                    </tr>
                </table>

                <table class="estadisticas-doble">
                    <tr>
                        <td>
                            <div class="mini-titulo">Distribución por edad actual</div>
                            <table class="tabla-estadistica">
                                <thead>
                                    <tr>
                                        <th>Edad</th>
                                        <th>Hombres</th>
                                        <th>Mujeres</th>
                                        <th>Total</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($porEdadSece as $filaEdad)
                                        <tr>
                                            <td class="centrado">{{ $filaEdad['edad'] }} años</td>
                                            <td class="centrado">{{ $filaEdad['hombres'] }}</td>
                                            <td class="centrado">{{ $filaEdad['mujeres'] }}</td>
                                            <td class="centrado">{{ $filaEdad['total'] }}</td>
                                            <td class="centrado">
                                                {{ number_format((float) $filaEdad['porcentaje'], 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="centrado">Sin datos de edad.</td>
                                        </tr>
                                    @endforelse
                                    <tr class="total-estadistica">
                                        <td class="centrado">TOTAL</td>
                                        <td class="centrado">{{ $resumenSece['hombres'] ?? 0 }}</td>
                                        <td class="centrado">{{ $resumenSece['mujeres'] ?? 0 }}</td>
                                        <td class="centrado">{{ $resumenSece['total'] ?? 0 }}</td>
                                        <td class="centrado">100.0%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>

                        <td>
                            <div class="mini-titulo">
                                {{ !empty($sece['es_bachillerato']) ? 'Situación respecto a la edad típica' : 'Situación de edad escolar' }}
                            </div>
                            <table class="tabla-estadistica">
                                <thead>
                                    <tr>
                                        <th>Situación</th>
                                        <th>Alumnos</th>
                                        <th>%</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($situacionesSece as $situacion)
                                        <tr>
                                            <td>{{ $situacion['etiqueta'] }}</td>
                                            <td class="centrado">{{ $situacion['cantidad'] }}</td>
                                            <td class="centrado">
                                                {{ number_format((float) $situacion['porcentaje'], 1) }}%</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="3" class="centrado">Sin datos.</td>
                                        </tr>
                                    @endforelse
                                    <tr class="total-estadistica">
                                        <td>TOTAL</td>
                                        <td class="centrado">{{ $resumenSece['total'] ?? 0 }}</td>
                                        <td class="centrado">100.0%</td>
                                    </tr>
                                </tbody>
                            </table>
                        </td>
                    </tr>
                </table>
            </div>

            @if (!$esBachillerato && !$esSecundaria)
                <table class="firmas">
                    <tr>
                        <td style="width: 50%;">
                            <span
                                class="linea-firma">{{ mb_strtoupper($nombreDocenteFirma ?: '____________________________', 'UTF-8') }}</span>
                            <div class="cargo-firma">
                                {{ optional($docente)->genero === 'M' ? 'Firma de la profesora de grupo' : 'Firma del profesor de grupo' }}
                            </div>
                        </td>
                        <td style="width: 50%;">
                            <span
                                class="linea-firma">{{ mb_strtoupper($nombreDirector ?: '____________________________', 'UTF-8') }}</span>
                            <div class="cargo-firma">
                                {{ optional($directorPersona)->genero === 'F' ? 'Firma de la directora de la escuela' : 'Firma del director de la escuela' }}
                            </div>
                        </td>
                    </tr>
                </table>
            @else
                <table class="firmas">
                    <tr>
                        <td style="width: 100%;">
                            <span
                                class="linea-firma">{{ mb_strtoupper($nombreDirector ?: '____________________________', 'UTF-8') }}</span>
                            <div class="cargo-firma">
                                {{ optional($directorPersona)->genero === 'F' ? 'Firma de la directora de la escuela' : 'Firma del director de la escuela' }}
                            </div>
                        </td>
                    </tr>
                </table>
            @endif
        </div>
    </div>

    <div class="footer">
        {{ mb_strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA A.C.', 'UTF-8') }} · C.C.T.
        {{ $nivel->cct ?? '—' }} · SECE · Generado {{ now()->format('d/m/Y H:i') }}
    </div>
</body>

</html>
