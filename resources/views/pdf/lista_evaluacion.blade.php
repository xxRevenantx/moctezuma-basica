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

        /*
         * Folio simple para control interno.
         */
        $folioLista =
            'LE-' .
            strtoupper($nivel->slug ?? 'NIVEL') .
            '-' .
            str_pad($grado->id ?? 0, 2, '0', STR_PAD_LEFT) .
            '-' .
            str_pad($grupo->id ?? 0, 2, '0', STR_PAD_LEFT) .
            '-' .
            now()->format('Ymd-His');

        /*
         * Columnas extras agregadas al final de la tabla.
         */
        $columnasExtras = 3;

        /*
         * Total de columnas para mensajes cuando no hay alumnos.
         */
        $totalColumnasTabla = $materiasMostrar->count() + 3 + $columnasExtras;
    @endphp

    <style>
        @page {
            size: letter landscape;
            margin: 14px 22px 20px;
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
            font-size: 9.5px;
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
            top: 125px;
            left: 240px;
            width: 540px;
            opacity: 0.06;
            z-index: 0;
        }

        .contenido {
            position: relative;
            z-index: 2;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .encabezado td {
            vertical-align: top;
            border: none;
        }

        .logo-izquierdo {
            width: 125px;
            text-align: left;
        }

        .logo-izquierdo img {
            width: 95px;
            object-fit: contain;
        }

        .logo-derecho {
            width: 125px;
            text-align: right;
        }

        .logo-derecho img {
            width: 95px;
            object-fit: contain;
        }

        .titulo-centro {
            text-align: center;
        }

        .nombre-escuela {
            display: inline-block;
            color: #5b6470;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-top: 1px solid #9ca3af;
            border-bottom: 1px solid #9ca3af;
            padding: 0 10px 1px;
            margin-bottom: 3px;
        }

        .titulo-lista {
            font-size: 18px;
            font-weight: bold;
            color: #001333;
            margin-top: 2px;
            line-height: 1.1;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .ciclo {
            font-size: 15px;
            font-weight: bold;
            color: #001333;
            margin-top: 1px;
            line-height: 1.1;
        }

        .folio {
            display: inline-block;
            margin-top: 4px;
            padding: 3px 10px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e40af;
            font-size: 9px;
            font-weight: bold;
            border-radius: 12px;
        }

        .direccion {
            font-size: 9px;
            color: #001333;
            line-height: 1.25;
            margin-top: 2px;
        }

        .datos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            margin-bottom: 5px;
        }

        .datos td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            font-size: 10px;
            vertical-align: middle;
        }

        .datos .label {
            width: 75px;
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
        }

        .datos .valor {
            font-weight: bold;
            text-transform: uppercase;
        }

        .info-periodo {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .info-periodo td {
            border: 1px solid #cbd5e1;
            padding: 4px 5px;
            font-size: 10px;
            text-align: center;
        }

        .info-periodo .label {
            background: #f8fafc;
            color: #475569;
            font-weight: bold;
        }

        .info-periodo .valor {
            background: #ffffff;
            color: #001333;
            font-weight: bold;
        }

        .fechas {
            text-align: center;
            font-size: 10.5px;
            margin-top: 4px;
            margin-bottom: 5px;
        }

        .leyenda-general {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
            font-size: 9.5px;
        }

        .leyenda-general td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
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

        .leyenda-ap {
            background: #dcfce7;
            color: #14532d;
        }

        .leyenda-na {
            background: #fee2e2;
            color: #991b1b;
        }

        .leyenda-rec {
            background: #fef3c7;
            color: #92400e;
        }

        .nota-leyenda {
            font-size: 8.5px;
            font-weight: normal;
            color: #334155;
            line-height: 1.25;
        }

        .tabla-evaluacion {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.3px;
        }

        .tabla-evaluacion th,
        .tabla-evaluacion td {
            border: 1px solid #334155;
        }

        .tabla-evaluacion thead th {
            background: #d9d9d9;
            text-align: center;
            vertical-align: middle;
            font-weight: bold;
        }

        .tabla-evaluacion .head-principal {
            background: #dbeafe;
            color: #1e3a8a;
        }

        .col-numero {
            text-align: center;
            width: 18px;
        }

        .col-alumno {
            text-align: center;
            width: 145px;
            font-size: 8px;
        }

        .col-materia {
            text-align: center;
        }

        .col-promedio {
            text-align: center;
            width: 42px;
            background: #dcfce7 !important;
            color: #14532d;
        }

        .col-estatus {
            text-align: center;
            width: 56px;
            background: #fef3c7 !important;
            color: #92400e;
        }

        .col-observacion {
            text-align: center;
            width: 105px;
            background: #f8fafc !important;
            color: #334155;
        }

        .tbody-numero {
            text-align: center;
            width: 18px;
            font-size: 9px;
        }

        .tbody-alumno {
            font-size: 8.4px;
            width: 145px;
            line-height: 8.8px;
            padding: {{ $totalAlumnos }} 2px;
            overflow: hidden;
            text-transform: uppercase;
        }

        .fila-alumno td {
            height: 18px;
        }

        .celda-calificacion {
            height: 18px;
            text-align: center;
        }

        .celda-promedio {
            background: #f0fdf4;
            text-align: center;
        }

        .celda-estatus {
            background: #fffbeb;
            text-align: center;
        }

        .celda-observacion {
            background: #ffffff;
        }

        .sin-materias {
            text-align: center;
            font-size: 11px;
            padding: 12px;
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
            font-size: 5.8px;
            font-weight: bold;
            letter-spacing: 0.2px;
        }

        .resumen-grupal {
            width: 100%;
            border-collapse: collapse;
            margin-top: 7px;
            font-size: 9.3px;
        }

        .resumen-grupal th,
        .resumen-grupal td {
            border: 1px solid #cbd5e1;
            padding: 4px 6px;
            text-align: center;
        }

        .resumen-grupal th {
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
        }

        .resumen-grupal td {
            height: 21px;
        }

        .seguimiento {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9.3px;
        }

        .seguimiento td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
        }

        .seguimiento .titulo-seguimiento {
            width: 150px;
            background: #fef3c7;
            color: #92400e;
            font-weight: bold;
            text-align: center;
        }

        .observaciones-generales {
            width: 100%;
            border-collapse: collapse;
            margin-top: 6px;
            font-size: 9.3px;
        }

        .observaciones-generales td {
            border: 1px solid #cbd5e1;
            padding: 5px 7px;
        }

        .observaciones-generales .titulo-observaciones {
            width: 150px;
            background: #f1f5f9;
            color: #334155;
            font-weight: bold;
            text-align: center;
        }

        .linea-observacion {
            display: block;
            height: 14px;
            border-bottom: 1px solid #cbd5e1;
            margin-bottom: 2px;
        }

        .nota {
            margin-top: 6px;
            border: 1px solid #bfdbfe;
            background: #eff6ff;
            color: #1e3a8a;
            padding: 5px 7px;
            font-size: 8.8px;
            line-height: 1.25;
            text-align: justify;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 24px;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            font-size: 10px;
            color: #001333;
            padding: 0 70px;
        }

        .linea-firma {
            display: block;
            border-top: 1px solid #001333;
            padding-top: 4px;
            font-weight: bold;
        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 8px;
            text-align: center;
            font-size: 9px;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 4px;
        }

        footer p {
            margin: 0;
            line-height: 1.2;
        }

        .uppercase {
            text-transform: uppercase;
        }

        .fw-700 {
            font-weight: bold;
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

            <table class="datos">
                <tr>
                    <td class="label">Nivel</td>
                    <td class="valor">{{ strtoupper($nivel->nombre ?? '—') }}</td>

                    <td class="label">Docente</td>
                    <td class="valor">{{ strtoupper($nombreDocente ?? 'DOCENTE') }}</td>
                </tr>

                <tr>
                    <td class="label">Periodo</td>
                    <td class="valor">{{ $textoPeriodoEvaluacion }}</td>

                    <td class="label">Total alumnos</td>
                    <td class="valor">{{ $alumnos->count() }}</td>
                </tr>
            </table>

            <table class="info-periodo">
                <tr>
                    <td class="label">Grado</td>
                    <td class="label">Grupo</td>
                    <td class="label">Turno</td>
                    <td class="label">Fecha inicial</td>
                    <td class="label">Fecha final</td>
                </tr>

                <tr>
                    <td class="valor">{{ $grado->nombre ?? '—' }}</td>
                    <td class="valor">"{{ $grupo->asignacionGrupo->nombre ?? '—' }}"</td>
                    <td class="valor">{{ $turno ?? 'Matutino' }}</td>
                    <td class="valor">{{ $fechaInicioEvaluacion ?: '—' }}</td>
                    <td class="valor">{{ $fechaFinEvaluacion ?: '—' }}</td>
                </tr>
            </table>


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

                            <th class="col-estatus">
                                ESTATUS
                            </th>

                            <th class="col-observacion">
                                OBSERVACIONES
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($alumnos as $alumno)
                            <tr class="fila-alumno">
                                <td class="tbody-numero">
                                    {{ $loop->iteration }}
                                </td>

                                <td class="tbody-alumno">
                                    {{ $alumno->apellido_paterno }}
                                    {{ $alumno->apellido_materno }}
                                    {{ $alumno->nombre }}
                                </td>

                                @foreach ($materiasMostrar as $materia)
                                    <td class="celda-calificacion"></td>
                                @endforeach

                                <td class="celda-promedio"></td>
                                <td class="celda-estatus"></td>
                                <td class="celda-observacion"></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $totalColumnasTabla }}" class="sin-materias">
                                    No hay alumnos activos con los filtros seleccionados.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>

                @if ($esListaPrimariaEvaluacion && $materiasCualitativas->isNotEmpty())
                    <table class="leyenda-general">
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
                    <table class="leyenda-general">
                        <tr>
                            <td colspan="3" class="leyenda-titulo">
                                Leyenda de evaluación cualitativa opcional
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



                <table class="observaciones-generales">
                    <tr>
                        <td class="titulo-observaciones">
                            Observaciones generales
                        </td>

                        <td>
                            <span class="linea-observacion"></span>
                            <span class="linea-observacion"></span>
                        </td>
                    </tr>
                </table>

                <div class="nota">
                    Nota: Registrar las calificaciones conforme a los criterios establecidos por el docente y la
                    institución.
                    En primaria, las materias cualitativas indicadas deberán evaluarse con AC, ED o RA.
                    El promedio deberá considerar únicamente las materias promediables, evitando incluir materias
                    marcadas como extra o cualitativas.
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
                                Vo. Bo. Dirección / Coordinación Académica
                            </span>
                        </td>
                    </tr>
                </table>
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
