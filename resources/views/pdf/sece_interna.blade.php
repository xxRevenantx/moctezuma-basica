<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Lista interna SECE</title>

    @php
        $slugNivel = $nivel->slug ?? '';

        // Preescolar y primaria usan colores pastel.
        // Secundaria y bachillerato usan tonos más sobrios.
        $esPastel = in_array($slugNivel, ['preescolar', 'primaria']);

        $mostrarMotivo = $mostrarMotivo ?? false;

        $colorPrincipal = $esPastel ? '#9AC457' : '#94a3b8';
        $colorTexto = '#001f3f';
        $colorBorde = '#1f2937';
        $colorFondoFila = '#ffffff';

        $totalColumnas = $mostrarMotivo ? 5 : 4;

        $totalFilasMinimas = 13;

        $nombreNivel = strtoupper($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL'));

        $nombreGrado = $grado->nombre ?? ($grado->grado ?? '');
        $nombreGrupo = $grupo->nombre ?? '';

        $turnoTexto = $turno ?? 'Matutino';

        $totalAlumnos = $alumnos->count() > 15 ? '25px' : '35px';

    @endphp

    <style>
        @page {
            margin: 18px 28px;
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
            color: {{ $colorTexto }};
            font-size: 12px;
        }

        .pagina {
            position: relative;
            width: 100%;
            min-height: 100%;
        }

        .marca-agua {
            position: fixed;
            top: 155px;
            left: 88px;
            width: 610px;
            opacity: 0.08;
            z-index: 1;
        }

        .contenido {
            position: relative;
            z-index: 2;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            /* margin-bottom: 8px; */
        }

        .encabezado td {
            border: none;
            vertical-align: top;
        }

        .logo-izquierdo {
            width: 120px;
            text-align: left;
        }

        .logo-izquierdo img {
            width: 92px;
            max-height: 92px;
            object-fit: contain;
        }

        .logo-derecho {
            width: 100px;
            text-align: right;
        }

        .logo-derecho img {
            width: 100px;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #4b5563;
            font-size: 16px;
            font-weight: 700;
            letter-spacing: 0.5px;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            padding: 0 10px 2px 10px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }

        .titulo-lista {
            font-size: 20px;
            font-weight: 700;
            margin-top: 2px;
            color: #111827;
            text-transform: uppercase;
        }

        .direccion {
            width: 500px;
            margin: 4px auto 0 auto;
            font-size: 11px;
            line-height: 1.25;
            color: #334155;
            text-align: center;
        }

        .datos-grupo {
            width: 100%;
            text-align: center;
            margin-top: 15px;
            margin-bottom: 54px;
            font-size: 16px;
            color: #020617;
        }

        .datos-grupo span {
            display: inline-block;
            margin: 0 9px;
        }

        .datos-grupo .label {
            font-weight: 400;
        }

        .datos-grupo .valor {
            font-weight: 700;
            text-decoration: underline;
        }

        .tabla-grupo {
            width: 100%;
            margin: -30px auto;
            border-collapse: collapse;

            font-size: 12px;
            color: {{ $colorTexto }};
        }

        .tabla-grupo th,
        .tabla-grupo td {
            border: 1px solid {{ $colorBorde }};
            padding: 0 8px;
            vertical-align: middle;
        }

        .tabla-grupo thead th {

            height: 30px;
            background: {{ $colorPrincipal }};
            color: #000000;
            font-size: 12px;
            font-weight: 400;
            text-align: center;


        }

        .tabla-grupo tbody td {
            font-size: 14px;
            font-weight: 400;
            text-transform: uppercase;
            height: {{ $totalAlumnos }};
        }

        .col-numero {
            width: 5px;
            text-align: center;
        }

        .col-nombre {
            width: 155px;
        }

        .col-apellido {
            width: 108px;
        }

        .col-motivo {
            /* width: 365px; */
        }

        .numero {
            text-align: center;
            width: 5px;
        }

        .alumno {
            text-align: left;
        }

        .motivo {
            text-align: left;
        }

        .sin-alumnos {
            height: 50px;
            text-align: center;
            font-size: 13px;
            color: #475569;
            text-transform: none !important;
        }

        .footer {
            position: fixed;
            left: 28px;
            right: 28px;
            bottom: 8px;
            text-align: center;
            font-size: 8px;
            color: #475569;
            border-top: 1px solid #94a3b8;
            padding-top: 3px;
            z-index: 3;
        }

        .footer p {
            margin: 0;
            line-height: 1.25;
        }

        .firmas {
            width: 100%;
            margin-top: 60px;
            font-size: 14px;
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
                            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                        </div>

                        <div class="titulo-lista">
                            LISTA DE GRUPO<br>
                            C.C.T. {{ $nivel->cct ?? '—' }}
                        </div>

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

            <div class="datos-grupo">
                <span>
                    <span class="label">Nivel:</span>
                    <span class="valor">{{ $nombreNivel }}</span>
                </span>

                <span>
                    <span class="label">Grado:</span>
                    <span class="valor">
                        @if (!empty($nombreGrado))
                            {{ $nombreGrado }}°
                        @else
                            —
                        @endif
                    </span>
                </span>

                @if ($esBachillerato)
                    <span>
                        <span class="label">Semestre:</span>
                        <span class="valor">
                            @if (!empty($semestre))
                                {{ $semestre->numero }}° SEMESTRE
                            @else
                                —
                            @endif
                        </span>
                    </span>
                @endif

                <span>
                    <span class="label">Grupo:</span>
                    <span class="valor">
                        @if (!empty($nombreGrupo))
                            "{{ strtoupper($nombreGrupo) }}"
                        @else
                            —
                        @endif
                    </span>
                </span>

                @if (!$esBachillerato)
                    <span>
                        <span class="label">Turno:</span>
                        <span class="valor">{{ $turnoTexto }}</span>
                    </span>
                @endif
            </div>

            <table class="tabla-grupo">
                <thead>
                    <tr>
                        <th class="col-numero">No.</th>
                        <th class="col-nombre">Nombre completo</th>
                        <th class="col-nombre">CURP</th>
                        <th class="col-nombre">Fecha de nacimiento</th>


                    </tr>
                </thead>

                <tbody>
                    @forelse ($alumnos as $alumno)
                        <tr>
                            <td class="numero">
                                {{ $loop->iteration }}
                            </td>
                            <td class="alumno" style="width: 300px">
                                {{ $alumno->apellido_paterno }}
                                {{ $alumno->apellido_materno }}
                                {{ $alumno->nombre }}
                            </td>
                            <td class="alumno" style="text-align: center">
                                {{ $alumno->curp }}
                            </td>
                            <td class="alumno" style="text-align: center">
                                {{ \Carbon\Carbon::parse($alumno->fecha_nacimiento)->format('d/m/Y') }}
                            </td>


                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $totalColumnas }}" class="sin-alumnos">
                                No hay alumnos activos con los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse

                    @for ($i = $alumnos->count() + 1; $i <= $totalFilasMinimas; $i++)
                        <tr>
                            <td class="numero">
                                {{ $i }}
                            </td>

                            <td class="alumno"></td>
                            <td class="alumno"></td>
                            <td class="alumno"></td>

                            @if ($mostrarMotivo)
                                <td class="motivo"></td>
                            @endif
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>



        @if (!$esBachillerato && !$esSecundaria)
            <table class="firmas">
                <tr>
                    <td style="width: 50%; padding-top: 60px; text-align: center;">
                        <u>{{ mb_strtoupper(trim((optional($docente)->titulo ?? '') . ' ' . (optional($docente)->nombre ?? '') . ' ' . (optional($docente)->apellido_paterno ?? '') . ' ' . (optional($docente)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>
                        @if (optional($docente)->genero === 'M')
                            Firma de la profesora de grupo
                        @else
                            Firma de profesor de grupo
                        @endif


                    </td>

                    <td style="width: 50%; padding-top: 60px; text-align: center;">
                        <u>{{ mb_strtoupper(trim((optional($director->director)->titulo ?? '') . ' ' . (optional($director->director)->nombre ?? '') . ' ' . (optional($director->director)->apellido_paterno ?? '') . ' ' . (optional($director->director)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>
                        @if ($director->director->genero === 'F')
                            Firma de la directora de la escuela
                        @else
                            Firma del director de la escuela
                        @endif
                    </td>
                </tr>
            </table>
        @else
            <table class="firmas">
                <tr>

                    <td style="width: 100%; padding-top: 60px; text-align: center;">
                        <u>{{ mb_strtoupper(trim((optional($director->director)->titulo ?? '') . ' ' . (optional($director->director)->nombre ?? '') . ' ' . (optional($director->director)->apellido_paterno ?? '') . ' ' . (optional($director->director)->apellido_materno ?? '')) ?: '____________________________') }}</u><br>
                        @if ($director->director->genero === 'F')
                            Firma de la directora de la escuela
                        @else
                            Firma del director de la escuela
                        @endif
                    </td>
                </tr>
            </table>

        @endif





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
