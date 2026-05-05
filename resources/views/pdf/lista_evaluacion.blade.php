<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Lista de evaluación</title>

    @php
        $totalAlumnos = $alumnos->count() > 15 ? '1px' : '2px';

        /*
         * Se obtiene el texto del periodo para mostrarlo en el encabezado.
         */
        $textoPeriodoEvaluacion =
            $nombrePeriodo ??
            ($periodoTexto ?? ($periodo?->periodoBasica?->periodo ?? ($periodo?->periodoBasica?->descripcion ?? '—')));

        $fechaInicioEvaluacion = $periodo?->fecha_inicio
            ? \Carbon\Carbon::parse($periodo->fecha_inicio)->locale('es')->translatedFormat('j \\de F Y')
            : '';

        $fechaFinEvaluacion = $periodo?->fecha_fin
            ? \Carbon\Carbon::parse($periodo->fecha_fin)->locale('es')->translatedFormat('j \\de F Y')
            : '';

        $esListaPrimariaEvaluacion = ($esPrimaria ?? false) && ($tipo_descarga ?? '') === 'evaluacion';

        $materiasPromediables = $materiasPromediables ?? collect();
        $materiasCualitativas = $materiasCualitativas ?? collect();

        /*
         * En primaria se muestran primero las materias que se promedian
         * y después las materias cualitativas.
         */
        $materiasMostrar = $esListaPrimariaEvaluacion ? $materiasPromediables->merge($materiasCualitativas) : $materias;

        /*
         * Solo estas materias pueden usar AC / ED / RA.
         */
        $slugsMateriasCualitativas = ['calculo-mental', 'caligrafia', 'lectura'];
    @endphp

    <style>
        @page {
            margin: 15px 26px 0;
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
            font-family: ARIAL, DejaVu Sans, sans-serif;
            color: #001333;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .pagina {
            position: relative;
            width: 100%;
            min-height: 100%;
        }

        .marca-agua {
            position: absolute;
            top: 150px;
            left: 100px;
            width: 560px;
            opacity: 0.07;
            z-index: 0;
        }

        .contenido {
            position: relative;
            z-index: 2;
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
            font-size: 18px;
            font-weight: normal;
            color: #001333;
            margin-top: 2px;
            line-height: 1.1;
        }

        .ciclo {
            font-size: 17px;
            font-weight: bold;
            color: #001333;
            margin-top: 1px;
            line-height: 1.1;
        }

        .direccion {
            font-size: 10px;
            color: #001333;
            line-height: 1.25;
            margin-top: 2px;
        }

        .datos {
            margin-top: 8px;
            font-size: 13px;
            color: #001333;
        }

        .datos p {
            margin: 0 0 8px 0;
        }

        .subrayado {
            text-decoration: underline;
            font-weight: bold;
        }

        .docente {
            text-decoration: underline;
            text-transform: uppercase;
        }

        .linea-centro {
            text-align: center;
            font-size: 13px;
            margin-top: 12px;
        }

        .linea-centro span {
            margin: 0 8px;
        }

        .fechas {
            text-align: center;
            font-size: 13px;
            margin-top: 12px;
            margin-bottom: 10px;
        }

        .tabla-evaluacion {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
        }

        .tabla-evaluacion th,
        .tabla-evaluacion td {
            border: 1px solid #333;
        }

        .tabla-evaluacion thead th {
            background: #c7c7c7;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .col-numero {
            text-align: center;
            width: 18px;
        }

        .col-alumno {
            text-align: center;
            width: 120px;
            font-size: 10px;
        }

        .col-materia {
            text-align: center;
        }

        .col-promedio {
            text-align: center;
            width: 50px;
        }

        .tbody-numero {
            text-align: center;
            width: 5px;
            font-size: 12px;
        }

        .tbody-alumno {
            font-size: 9.8px;
            width: 100px;
            line-height: 9px;
            padding: {{ $totalAlumnos }} 2px;
            overflow: hidden;
        }

        .fila-alumno td {
            height: 19px;
        }

        .celda-calificacion {
            height: 19px;
        }

        .sin-materias {
            text-align: center;
            font-size: 12px;
            padding: 14px;
        }

        .encabezado-promediable {
            background: #dbeafe !important;
            color: #1e3a8a;
        }

        .encabezado-cualitativa {
            background: #fef3c7 !important;
            color: #92400e;
        }

        .badge-materia {
            display: block;
            margin-top: 2px;
            font-size: 6px;
            font-weight: bold;
            letter-spacing: 0.2px;
        }

        .leyenda-promedio {
            margin-top: 6px;
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #93c5fd;
        }

        .leyenda-promedio td {
            border: 1px solid #93c5fd;
            padding: 6px 8px;
            text-align: center;
            background: #eff6ff;
            color: #1e3a8a;
            font-weight: bold;
        }

        .leyenda-cualitativa {
            margin-top: 6px;
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
            border: 1px solid #facc15;
        }

        .leyenda-cualitativa td {
            border: 1px solid #facc15;
            padding: 6px 8px;
            text-align: center;
        }

        .leyenda-titulo {
            background: #dbeafe;
            font-weight: bold;
            text-transform: uppercase;
            color: #1e3a8a;
            letter-spacing: 0.4px;
        }

        .leyenda-clave {
            display: inline-block;
            min-width: 22px;
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: bold;
            color: #001333;
        }

        .leyenda-ac {
            background: #bbf7d0;
            color: #166534;
        }

        .leyenda-ed {
            background: #fde68a;
            color: #92400e;
        }

        .leyenda-ra {
            background: #fecaca;
            color: #991b1b;
        }

        .nota-leyenda {
            font-size: 9px;
            font-weight: normal;
            color: #334155;
            line-height: 1.25;
        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 12px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }

        footer p {
            margin: 0;
            line-height: 1.25;
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
                        <div class="nombre-escuela">
                            {{ strtoupper($escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA') }}
                        </div>

                        <div class="titulo-lista">
                            LISTA DE EVALUACIÓN
                        </div>

                        <div class="ciclo">
                            CICLO ESCOLAR:
                            {{ $cicloEscolar->inicio_anio ?? '—' }} - {{ $cicloEscolar->fin_anio ?? '—' }}
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
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" alt="">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="datos">
                <p>
                    Nivel:
                    <span class="subrayado">{{ strtoupper($nivel->nombre ?? '—') }}</span>
                </p>

                <p>
                    Nombre del Docente:
                    <span class="docente">{{ strtoupper($nombreDocente ?? 'DOCENTE') }}</span>
                </p>
            </div>

            <div class="linea-centro">
                <span>
                    Periodo No:
                    <span class="subrayado">{{ $textoPeriodoEvaluacion }}</span>
                </span>

                <span>
                    Grado:
                    <span class="subrayado">{{ $grado->nombre ?? '—' }}</span>
                </span>

                <span>
                    Grupo:
                    <span class="subrayado">"{{ $grupo->nombre ?? '—' }}"</span>
                </span>

                <span>
                    Turno:
                    <span class="subrayado">{{ $turno ?? 'Matutino' }}</span>
                </span>
            </div>

            <div class="fechas">
                que comprende las fechas
                <u>{{ $fechaInicioEvaluacion }}</u>
                al
                <u>{{ $fechaFinEvaluacion }}</u>
            </div>

            @if ($materiasMostrar->isEmpty())
                <div class="sin-materias">
                    No hay materias calificables asignadas para este grupo.
                </div>
            @else
                <table class="tabla-evaluacion">
                    <thead>
                        <tr>
                            <th class="col-numero">
                                No.
                            </th>

                            <th class="col-alumno">
                                NOMBRE DEL ALUMNO
                            </th>

                            @foreach ($materiasMostrar as $materia)
                                @php
                                    $esMateriaCualitativa =
                                        $esListaPrimariaEvaluacion &&
                                        in_array($materia->slug, $slugsMateriasCualitativas, true) &&
                                        (int) ($materia->calificable ?? 0) === 1 &&
                                        (int) ($materia->extra ?? 0) === 1;

                                    $esMateriaPromediable =
                                        $esListaPrimariaEvaluacion &&
                                        !$esMateriaCualitativa &&
                                        (int) ($materia->calificable ?? 0) === 1;
                                @endphp

                                <th
                                    class="col-materia {{ $esMateriaCualitativa ? 'encabezado-cualitativa' : '' }} {{ $esMateriaPromediable ? 'encabezado-promediable' : '' }}">
                                    <div style="text-align:center; text-transform:uppercase">
                                        {{ $materia->materia }}

                                        @if ($esMateriaCualitativa)
                                            <span class="badge-materia">
                                                AC / ED / RA
                                            </span>
                                        @endif


                                    </div>
                                </th>
                            @endforeach

                            <th class="col-promedio">
                                PROMEDIO
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($alumnos as $alumno)
                            <tr class="fila-alumno">
                                <td class="tbody-numero">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="tbody-alumno" style="text-transform: uppercase">
                                    {{ $alumno->apellido_paterno }}
                                    {{ $alumno->apellido_materno }}
                                    {{ $alumno->nombre }}
                                </td>

                                @foreach ($materiasMostrar as $materia)
                                    <td class="celda-calificacion"></td>
                                @endforeach

                                <td></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $materiasMostrar->count() + 3 }}" class="sin-materias">
                                    No hay alumnos activos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>



                @if ($esListaPrimariaEvaluacion && $materiasCualitativas->isNotEmpty())
                    <table class="leyenda-cualitativa">
                        <tr>
                            <td colspan="3" class="leyenda-titulo">
                                Leyenda de evaluación cualitativa
                                <br>
                                <span class="nota-leyenda">
                                    Solo Cálculo mental, Caligrafía y Lectura deberán evaluarse con AC, ED o RA.
                                </span>
                            </td>
                        </tr>

                        <tr>
                            <td style="background: #f0fdf4;">
                                <span class="leyenda-clave leyenda-ac">AC</span>
                                Acreditado
                            </td>

                            <td style="background: #fffbeb;">
                                <span class="leyenda-clave leyenda-ed">ED</span>
                                En desarrollo
                            </td>

                            <td style="background: #fef2f2;">
                                <span class="leyenda-clave leyenda-ra">RA</span>
                                Requiere apoyo
                            </td>
                        </tr>
                    </table>
                @endif

                @if (!$esListaPrimariaEvaluacion)
                    <table class="leyenda-cualitativa">
                        <tr>
                            <td colspan="3" class="leyenda-titulo">
                                Leyenda de evaluación
                            </td>
                        </tr>

                        <tr>
                            <td style="background: #f0fdf4;">
                                <span class="leyenda-clave leyenda-ac">AC</span>
                                Acreditado
                            </td>

                            <td style="background: #fffbeb;">
                                <span class="leyenda-clave leyenda-ed">ED</span>
                                En desarrollo
                            </td>

                            <td style="background: #fef2f2;">
                                <span class="leyenda-clave leyenda-ra">RA</span>
                                Requiere apoyo
                            </td>
                        </tr>
                    </table>
                @endif
            @endif

        </div>
    </div>

    <footer>
        <p class="uppercase fw-700">
            {{ $escuela->nombre ?? 'CENTRO UNIVERSITARIO MOCTEZUMA' }}
            · C.C.T. {{ $nivel->cct ?? '—' }}
        </p>

        <p>
            C. {{ $escuela->calle ?? '' }}
            No. {{ $escuela->no_exterior ?? '' }},
            Col. {{ $escuela->colonia ?? '' }},
            C.P. {{ $escuela->codigo_postal ?? '' }},
            Cd. {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}.
        </p>

        <p>
            Fecha de expedición:
            {{ now()->translatedFormat('d \\d\\e F \\d\\e\\l Y \\a \\l\\a\\s H:i') }}
        </p>
    </footer>
</body>

</html>
