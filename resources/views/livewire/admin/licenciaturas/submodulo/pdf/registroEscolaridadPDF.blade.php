<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>Registro de Escolaridad</title>
</head>
<style>
    @page {
        margin: 10px 45px 20px 45px;
    }

    .page-break {
        page-break-after: always;
    }

    @font-face {
        font-family: 'calibri';
        font-style: normal;
        src: url('{{ storage_path('fonts/calibri/calibri.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'calibri';
        font-style: bold;
        font-weight: 700;
        src: url('{{ storage_path('fonts/calibri/calibri-bold.ttf') }}') format('truetype');
    }

    body {
        font-family: 'calibri';
        margin: auto;
    }

    table.datos-escuela td {
        line-height: 15px;
    }


    table.tblPrincipal {
        width: 100%;
        border-collapse: collapse;
        font-size: 11px;
        margin-top: 10px;
    }


    table.tblPrincipal tr td {
        font-family: 'calibri';
        font-size: 14px;
        text-transform: uppercase;
        text-align: left;
        padding-left: 6px;
        border: 1px solid #000;
        line-height: 9px;
        /* padding: 0 */
    }


    .rotate {
        /* writing-mode: vertical-rl; */
        transform: rotate(-90deg);

    }

    /* La caja reserva espacio. Sus líneas se miden antes de girarlas. */
    table.tblPrincipal th.materia-celda {
        width: 37.5pt;
        height: 75pt;
        padding: 0;
        border: 1px solid #000;
        vertical-align: middle;
        font-weight: normal;
    }

    .materia-marco {
        position: relative;
        width: 37.5pt;
        height: 75pt;
        margin: 0 auto;
    }

    .materia-texto {
        position: absolute;
        width: 67.5pt;
        left: 18.75pt;
        top: 37.5pt;
        margin-left: -33.75pt;
        padding: 0;
        font-weight: normal;
        font-style: normal;
        text-align: center;
        transform: rotate(-90deg);
        transform-origin: 50% 50%;
    }

    .materia-linea {
        display: block;
        width: 67.5pt;
        margin: 0;
        padding: 0;
        border: none;
        white-space: nowrap;
        text-align: center;
        letter-spacing: 0;
        word-spacing: 0;
    }

    table.inscripcion td {
        padding: 0 30px 0 0;
        font-size: 12px;
    }

    table.table-directivos {
        font-size: 12px;
        line-height: 12px;
    }
</style>

<body>

    @foreach ($periodos as $periodo)
        @php
            // Materias del cuatrimestre y licenciatura, sin importar modalidad
            $asignaciones = \App\Models\AsignacionMateria::where('licenciatura_id', $licenciatura->id)
                ->where('cuatrimestre_id', $periodo->cuatrimestre_id)
                ->get()
                ->sortBy(fn($asignacion) => optional($asignacion->materia)->clave)
                ->values();
            // Agrupar por materia (evitar duplicados si existe una materia en ambas modalidades)
            $materiasUnicas = $asignaciones->pluck('materia')->unique('id')->values();

            $hombres = 0;
            $mujeres = 0;
            foreach ($alumnos as $alumno) {
                if (strtoupper($alumno->sexo) === 'H') {
                    $hombres++;
                } elseif (strtoupper($alumno->sexo) === 'M') {
                    $mujeres++;
                }
            }

            $grupo = \App\Models\Grupo::where('licenciatura_id', $licenciatura->id)
                ->where('cuatrimestre_id', $periodo->cuatrimestre_id)
                ->first();

        @endphp






        <div style="width:100%; margin-bottom:20px; margin-top:20px">
            <table width="100%" style="border-collapse: collapse;">
                <tr>
                    <!-- Logo SEP -->
                    <td style="width: 100px; text-align: left;">
                        <img src="{{ public_path('storage/sep.jpg') }}" alt="SEP" style="height: 70px;">
                    </td>

                    <!-- Texto centrado -->
                    <td style="text-align: center;">
                        <p style="color: #000000; font-weight: bold; font-size: 15px; margin: 0; line-height:14px">
                            GOBIERNO DEL ESTADO LIBRE Y SOBERANO DE GUERRERO <br>
                            SECRETARIA DE EDUCACIÓN GUERRERO<br>
                            REGISTRO DE ESCOLARIDAD
                        </p>

                    </td>

                    <!-- Logo Moctezuma -->
                    <td style="width: 200px; text-align: right;">
                        <img src="{{ public_path('storage/moctezuma.png') }}" alt="moctezuma" style="height: 60px;">
                    </td>
                </tr>
            </table>
        </div>

        <!-- Estructura para DomPDF -->
        <table class="datos-escuela" width="100%"
            style="border-collapse: collapse; margin-bottom: 10px; text-transform: uppercase;">
            <tr>
                <!-- Información de Plantel -->
                <td style="vertical-align: top;">
                    <div style="margin-bottom: 5px;">
                        <span style="font-size: 16px;">NOMBRE DEL PLANTEL:</span>
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->nombre }}
                        </span>
                        &nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="font-size: 16px;">CLAVE DEL CENTRO DE TRABAJO:</span>
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->CCT }}
                        </span>
                    </div>

                    <div>

                        <span style="font-size: 16px;">DOMICILIO:</span>
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->calle }} No. {{ $escuela->no_exterior }}
                        </span>

                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->colonia }}
                        </span>

                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->municipio }}
                        </span>

                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                        <span style="font-weight: bold; text-decoration: underline; font-size: 16px;">
                            {{ $escuela->estado }}
                        </span>

                    </div>

                    <div>
                        <span style="font-size: 12px; margin-left:155px">CALLE Y NÚMERO</span>

                        <span style="font-size: 12px; margin-left:210px">COLONIA</span>
                        <span style="font-size: 12px; margin-left:140px">MUNICIPIO</span>
                        <span style="font-size: 12px; margin-left:120px">ENTIDAD FEDERATIVA</span>

                    </div>
                </td>

                <!-- Tabla Estadística -->
                <td style="vertical-align: top; width: 240px; font-family: calibri">
                    <table border="1" cellpadding="3"
                        style="border-collapse: collapse; font-size: 13px; width: 100%; margin-left: 0px;">
                        <tr>
                            <th colspan="4" style="text-align: center;">DATOS ESTADÍSTICOS</th>
                        </tr>
                        <tr>
                            <th style="text-align: center;">CONCEPTO</th>
                            <th style="text-align: center;">H</th>
                            <th style="text-align: center;">M</th>
                            <th style="text-align: center;">TOTAL</th>
                        </tr>
                        <tr>
                            <td style="text-align: center;">INSCRITOS</td>
                            <td style="text-align: center;">{{ $hombres }}</td>
                            <td style="text-align: center;">{{ $mujeres }}</td>
                            <td style="text-align: center;">{{ $hombres + $mujeres }}</td>
                        </tr>

                    </table>
                </td>
            </tr>
        </table>

        <!-- Segunda fila de datos (números de acuerdo, ciclo escolar, etc.) -->
        <table width="100%" style="border-collapse: collapse;">
            <tr>
                <td style="font-size: 16px;">
                    NUMERO DE ACUERDO: <span
                        style="text-decoration: underline; font-weight: bold; ">{{ $licenciatura->RVOE }}</span>
                    &nbsp;&nbsp;
                    FECHA DE ACUERDO: <span
                        style="text-decoration: underline; font-weight: bold; text-transform: uppercase;">{{ $licenciatura->fecha_acuerdo_formateada ?? 'SIN FECHA' }}</span>
                    &nbsp;&nbsp;
                    CICLO ESCOLAR: <span style="text-decoration: underline; font-weight: bold; ">
                        {{ $periodo->ciclo_escolar }}</span>
                    &nbsp;&nbsp;
                    GENERACIÓN: <span style="text-decoration: underline; font-weight: bold; ">
                        {{ $periodo->generacion->generacion }}</span>
                </td>
            </tr>
            <tr>
                <td style="font-size: 15px; padding-top: 8px; text-transform: uppercase;">
                    LICENCIATURA EN: <span style="text-decoration: underline; font-weight: bold; ">
                        {{ $licenciatura->nombre }}</span>
                    &nbsp;&nbsp;
                    MODALIDAD: <span style="font-weight: bold; text-decoration: underline;">ESCOLARIZADA</span>
                    &nbsp;&nbsp;
                    CUATRIMESTRE: <span
                        style="text-decoration: underline; font-weight: bold; ">{{ $periodo->cuatrimestre_id }}°</span>
                    &nbsp;&nbsp;
                    GRUPO: <span style="text-decoration: underline; font-weight: bold; ">{{ $grupo->grupo }}</span>
                    &nbsp;&nbsp;
                    TURNO: <span style="text-decoration: underline; font-weight: bold; ">MATUTINO</span>
                </td>
            </tr>
        </table>


        <table class="tblPrincipal">
            <tr>


                <td rowspan="2"
                    style="font-size:10px; width:30px; text-align:center;font-weight:normal;  padding:0px; margin:0px;">
                    <div style="white-space:nowrap;  width:30px;" class="rotate"><b>NÚMERO DE REGISTRO</b></div>
                </td>


                <td colspan="2"><b>ANTECEDENTES</b></td>

                <td rowspan="2"
                    style="font-size:11px; width:80px; font-weight:normal;padding:0px; margin:0px; height:0px; ">
                    <div style="text-align:center; width:80px;" class="rotate"><b>NÚMERO DE MATRÍCULA</b></div>
                </td>


                <td colspan="3" style=" text-align:center;"><b>NOMBRE DEL ALUMNO</b></td>

                <td colspan="1" rowspan="2"
                    style="font-size:11px; width:20px; font-weight:normal;padding:0px; margin:0px; height:0px; border-left:2px double #000;  border-right:2px double #000">
                    <div style="width:25px; text-align:center; white-space: nowrap;" class="rotate"><b>SEXO: H o M</b>
                    </div>
                </td>

                <td colspan="{{ count($materiasUnicas) }}" style="text-align: center;"><b>RESULTADO FINAL</b></td>
                <td colspan="4" style="text-align:center; font-size:11px; padding:0; margin:0">
                    <b>REGULARIZACIONES</b></td>
                <td colspan="3" style=" text-align:center;"><b>CUATRIMESTRE ACTUAL</b></td>
            </tr>
            <tr>
                <th style="width:50px; height:100px; border:1px solid #000">
                    <div style=" width:50px; text-align:center" class="rotate"><b>ASIGNATURAS NO ACREDITADAS</b></div>
                </th>
                <th style="width:50px">
                    <div style=" width:50px;" class="rotate"><b>SITUACIÓN ESCOLAR</b></div>
                </th>
                <th style="width:100px;;"><b>NOMBRE(S)</b></th>
                <th style="width:100px;"><b>PRIMER APELLIDO</b></th>
                <th style="width:100px;"><b>SEGUNDO APELLIDO</b></th>

                @foreach ($materiasUnicas as $materia)
                    @php($encabezado = $registroHeader->fit((string) $materia->nombre))
                    <th class="materia-celda">
                        <div class="materia-marco">
                            <div class="materia-texto" style="font-family: '{{ $encabezado['family'] }}'; font-size: {{ $encabezado['size'] }}pt; height: {{ $encabezado['height'] }}pt; margin-top: -{{ $encabezado['height'] / 2 }}pt;">
                                @foreach ($encabezado['lines'] as $linea)
                                    <div class="materia-linea" style="height: {{ $encabezado['line_height'] }}pt; line-height: {{ $encabezado['line_height'] }}pt;">{{ $linea }}</div>
                                @endforeach
                            </div>
                        </div>
                    </th>
                @endforeach


                <th style="border: 1px solid #000"></th>
                <th style="border: 1px solid #000"></th>
                <th style="border: 1px solid #000"></th>
                <th style="border: 1px solid #000"></th>

                <th style="width:50px; height:100px; border:1px solid #000">
                    <div style=" width:50px; text-align:center" class="rotate"><b>ASIGNATURAS ACREDITADAS</b></div>
                </th>
                <th style="width:50px; height:100px; border:1px solid #000">
                    <div style=" width:50px; text-align:center" class="rotate"><b>ASIGNATURAS NO ACREDITADAS</b></div>
                </th>
                <th style="width:50px; height:100px; border:1px solid #000">
                    <div style=" width:50px; text-align:center" class="rotate"><b>SITUACIÓN ESCOLAR</b></div>
                </th>
            </tr>


            @foreach ($alumnos as $alumno)
                <tr>
                    <td style="text-align:center; font-size:11px">
                        {{ str_pad($loop->iteration, 2, '0', STR_PAD_LEFT) }}</td>
                    <td style="font-size:11px; text-align:center;">0</td>
                    <td style="font-size:11px; text-align:center;">R</td>

                    <td style="font-size:11px; text-align:center">{{ $alumno->matricula }}</td>
                    <td style="font-size:11px;">{{ $alumno->nombre }}</td>
                    <td style="font-size:11px;">{{ $alumno->apellido_paterno }}</td>
                    <td style="font-size:11px;">{{ $alumno->apellido_materno }}</td>


                    <td
                        style="font-size:11px; text-align:center;  border-left:2px double #000;  border-right:2px double #000">
                        {{ $alumno->sexo }}</td>

                    @foreach ($materiasUnicas as $materia)
                        @php
                            $asignacionMateria = \App\Models\AsignacionMateria::where('materia_id', $materia->id)
                                ->where('licenciatura_id', $licenciatura->id)
                                ->where('cuatrimestre_id', $periodo->cuatrimestre_id)
                                ->where('modalidad_id', $alumno->modalidad_id)
                                ->first();

                            $calificacion = null;
                            if ($asignacionMateria) {
                                $calificacion = $alumno
                                    ->calificaciones()
                                    ->where('asignacion_materia_id', $asignacionMateria->id)
                                    ->value('calificacion');
                            }
                        @endphp
                        <td style="text-align: center; font-size:11px">{{ $calificacion ?? '' }}</td>
                    @endforeach



                    <td style="width:15px"></td>
                    <td style="width:15px"></td>
                    <td style="width:15px"></td>
                    <td style="width:15px"></td>

                    <td style="width:15px; font-size:11px; text-align:center">{{ count($materiasUnicas) }}</td>
                    <td style="width:15px; font-size:11px; text-align:center">0</td>
                    <td style="width:15px; font-size:11px; text-align:center">R</td>
                </tr>
            @endforeach


        </table>
        @php
            $fecha1 = \Carbon\Carbon::parse($periodo->inicio_periodo);
            $dia1 = $fecha1->format('d');
            $mes1 = strtoupper($fecha1->locale('es')->translatedFormat('F')); // mes en texto y en mayúsculas
            $año1 = $fecha1->format('Y');

            $fecha2 = \Carbon\Carbon::parse($periodo->termino_periodo);
            $dia2 = $fecha2->format('d');
            $mes2 = strtoupper($fecha2->locale('es')->translatedFormat('F')); // mes en texto y en mayúsculas
            $año2 = $fecha2->format('Y');

        @endphp

        <table class="inscripcion">
            <tr>
                <td>INSCRIPCIÓN / REINSCRIPCIÓN</td>
                <td>ACREDITACIÓN / CERTIFICACIÓN</td>
                <td>REGULARIZACIÓN</td>
            </tr>
            <tr>
                <td>{{ $dia1 }} DE {{ $mes1 }} DE 20{{ $fecha1->format('y') }}</td>
                <td>{{ $dia2 }} DE {{ $mes2 }} DE 20{{ $fecha2->format('y') }}</td>


                <td>FECHA: _________</td>
            </tr>
        </table>

        {{-- CODIDO DE AUTORIDADES AQUI --}}
        <table class="table-directivos"
            style="width: 50%; border: 1px solid #000; border-collapse: collapse; margin-top: 10px;">
            <tr>
                <td style="width: 50%; text-align: center; padding: 70px 10px 0; border-right: 1px solid #000;">
                    <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; margin-bottom: 5px;"></div>
                    <span style="font-weight: bold; text-transform:uppercase">{{ $rector->nombre }}
                        {{ $rector->apellido_paterno }} {{ $rector->apellido_materno }}</span><br>
                    <span style="font-weight: bold;">RECTOR(A) DEL PLANTEL</span>
                </td>
                <td style="width: 50%; text-align: center; padding: 70px 10px 0;">
                    <div style="border-top: 1px solid #000; width: 80%; margin: 0 auto; margin-bottom: 5px;"></div>
                    <span style="font-weight: bold; text-transform:uppercase">{{ $jefe->nombre }}
                        {{ $jefe->apellido_paterno }} {{ $jefe->apellido_materno }}</span><br>
                    <span style="font-weight: bold;">JEFA DEL DEPARTAMENTO DE REGISTRO<br>Y CERTIFICACIÓN</span>
                </td>
            </tr>
        </table>




        <div class="page-break"></div>
    @endforeach



</body>

</html>
