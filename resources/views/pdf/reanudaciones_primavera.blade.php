<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>OFICIOS DE REANUDACIÓN DE RECESO DE CLASES
    </title>
</head>
<style>
    @page {
        margin: 0px 0px 0px 0px;
    }


    .page-break {
        page-break-after: always;
    }

    body {
        color: #000;
    }

    @font-face {
        font-family: 'roboto';
        font-style: normal;
        src: url('{{ public_path('fonts/Roboto-Light.ttf') }}') format('truetype');
    }

    @font-face {
        font-family: 'times';
        font-style: normal;
        src: url('{{ public_path('fonts/times_new_roman.ttf') }}') format('truetype');
    }


    .fila-dato {
        display: flex;
        align-items: baseline;
        gap: 10px;
        margin: 10px 0;
    }

    .fila-dato .label {
        white-space: nowrap;
        font-weight: 400;
    }

    .fila-dato .value {
        flex: 1;
        /* ocupa todo el ancho restante */
        border-bottom: 1px solid #000;
        padding-bottom: 2px;
        /* separa texto de la línea */
        font-weight: 700;
        /* negrita como en la imagen */
        text-transform: uppercase;
        line-height: 1.1;
    }

    .contenedor_preescolar {
        font-size: 14px;
    }

    /* PRIMARIA */
    .contenedor_primaria {
        font-size: 14px;
        margin-top: 20px;
    }

    .contenedor_secundaria {
        font-size: 14px;
        margin-top: 20px;
    }

    .fondo_primaria {

        background-size: cover;
        background-repeat: no-repeat;

        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        position: absolute;
        top: 0;
        left: 0;
    }

    .fondo_secundaria {

        background-size: cover;
        background-repeat: no-repeat;

        width: 100%;
        height: 100%;
        margin: 0;
        padding: 0;
        position: absolute;
        top: 0;
        left: 0;
    }
</style>

<body>

    @php
        $nombreDirectorPreescolar = mb_strtoupper(
            $directoraPreescolar->titulo .
                ' ' .
                $directoraPreescolar->nombre .
                ' ' .
                $directoraPreescolar->apellido_paterno .
                ' ' .
                $directoraPreescolar->apellido_materno,
        );

        $nombreDirectorPSNombre = mb_strtoupper(
            $directoraPS->titulo .
                ' ' .
                $directoraPS->nombre .
                ' ' .
                $directoraPS->apellido_paterno .
                ' ' .
                $directoraPS->apellido_materno,
        );

        $supervisorPreescolarNombre = mb_strtoupper(
            $supervisorPreescolar->titulo .
                ' ' .
                $supervisorPreescolar->nombre .
                ' ' .
                $supervisorPreescolar->apellido_paterno .
                ' ' .
                $supervisorPreescolar->apellido_materno,
        );

        $supervisorPrimariaNombre = mb_strtoupper(
            $supervisorPrimaria->titulo .
                ' ' .
                $supervisorPrimaria->nombre .
                ' ' .
                $supervisorPrimaria->apellido_paterno .
                ' ' .
                $supervisorPrimaria->apellido_materno,
        );

        $supervisorSecundariaNombre = mb_strtoupper(
            $supervisorSecundaria->titulo .
                ' ' .
                $supervisorSecundaria->nombre .
                ' ' .
                $supervisorSecundaria->apellido_paterno .
                ' ' .
                $supervisorSecundaria->apellido_materno,
        );

        $directorAdministracionNombre = mb_strtoupper(
            $directorAdministracion->titulo .
                ' ' .
                $directorAdministracion->nombre .
                ' ' .
                $directorAdministracion->apellido_paterno .
                ' ' .
                $directorAdministracion->apellido_materno,
        );
        $directorMagisterioNombre = mb_strtoupper(
            $directorMagisterio->titulo .
                ' ' .
                $directorMagisterio->nombre .
                ' ' .
                $directorMagisterio->apellido_paterno .
                ' ' .
                $directorMagisterio->apellido_materno,
        );
    @endphp

    @if ($nivel->slug == 'preescolar')
        @foreach ($asignacionesNivel as $personal)
            @php
                $fechaDocente = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
                \Carbon\Carbon::setLocale('es');
                $fechaDirector = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');

            @endphp

            <img src="{{ public_path('storage/reanudacion_preescolar.jpg') }}" alt="fondo" style="width: 100%;">
            <div class="contenedor_preescolar" style="padding: 10px 60px 0">
                <p style="text-align: right; font-family:Arial, Helvetica, sans-serif;">ASUNTO: <b> REANUDACIÓN DE
                        LABORES</b></p>
                @foreach ($personal->persona->personaRoles as $rolePersona)
                    @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                        <p style="text-align: right; margin-right:100px; text-transform: uppercase;">CIUDAD ALTAMIRANO,
                            GRO. A
                            {{ $fechaDirector }}.</p>
                    @else
                        <p style="text-align: right; margin-right:100px; text-transform: uppercase;">CIUDAD ALTAMIRANO,
                            GRO. A {{ $fechaDocente }}.
                        </p>
                    @endif
                @endforeach
                <p style="text-align: right"><b>{{ $escuela->lema }}</b></p>

                <div class="delegado" style="margin-top: 10px; text-transform: uppercase">
                    <p style="width: 400px;"><b> {{ $delegado->titulo }} {{ $delegado->nombre }}
                            {{ $delegado->apellido_paterno }}
                            {{ $delegado->apellido_materno }} <br></b>

                    </p>
                    <p style="margin-top:-15px; width:320px"> {{ $delegado->cargo }}
                    </p>
                    <p><b>P R E S E N T E</b></p>

                </div>

                <div>

                    @foreach ($personal->persona->personaRoles as $rolePersona)
                        @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                            <p style="text-transform: uppercase; text-align: justify; line-height: 20px;">
                                EL (A) QUE SUSCRIBE C. <b><u>{{ $rolePersona->persona->titulo }}
                                        {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                                LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                            </p>
                        @else
                            <p style="text-transform: uppercase; text-align: justify; line-height: 20px;">
                                EL (A) QUE SUSCRIBE C.

                                <b><u>{{ $rolePersona->persona->titulo }}
                                        {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                                LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                            </p>
                        @endif
                    @endforeach




                </div>

                <p>PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:</p>



                @php
                    $nombreCompleto = trim(
                        $personal->persona->nombre .
                            ' ' .
                            $personal->persona->apellido_paterno .
                            ' ' .
                            $personal->persona->apellido_materno,
                    );

                    $datos = [
                        'NOMBRE COMPLETO' => mb_strtoupper($nombreCompleto),
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE (S) PRESUPUESTAL (ES)' => 'S/C',
                        'CARGO QUE DESEMPEÑA' => (function () use ($personal) {
                            $roles = [];

                            foreach ($personal->persona->personaRoles as $rolePersona) {
                                $roles[] =
                                    $rolePersona->rolePersona->nombre ?? ($rolePersona->rolePersona->slug ?? null);
                            }

                            $roles = array_values(array_filter($roles));

                            return mb_strtoupper(!empty($roles) ? implode(', ', $roles) : '---------');
                        })(),
                        'FECHA DE INGRESO A LA SEP' => !empty($personal->ingreso_sep)
                            ? date('d-m-Y', strtotime($personal->ingreso_sep))
                            : '---------',
                        'FECHA DE INGRESO AL CENTRO DE TRABAJO' => !empty($personal->ingreso_ct)
                            ? date('d-m-Y', strtotime($personal->ingreso_ct))
                            : '---------',
                        'NOMBRE DEL C.T.' => mb_strtoupper($escuela->nombre ?? '---------'),
                        'C.C.T.' => mb_strtoupper($nivel->cct ?? '---------'),
                        'UBICACIÓN' => 'FRACISCO I. MADERO OTE. 800 COL. ESQUIPULAS. CD ALTAMIRANO, GRO.',
                    ];
                @endphp

                <div>
                    @foreach ($datos as $key => $dato)
                        @php
                            $contar = strlen($dato) + strlen($key);
                            $espacioBlanco = '';

                            for ($i = $contar; $i < 60; $i++) {
                                $espacioBlanco .= '&nbsp;&nbsp;';
                            }
                        @endphp
                        <div class="fila-dato">
                            <div class="label">{{ $key }}:
                                <b><u>{{ $dato }}{!! $espacioBlanco !!}</u></b>
                            </div>
                        </div>
                    @endforeach
                </div>


                <p style="text-align: justify">SIN OTRO PARTICULAR, APROVECHO LA OCASIÓN PARA ENVIARLE UN AFECTUOSO
                    SALUDO.</p>

                <div class="firmas">


                    <table style="width: 100%; margin:auto; text-align: center; text-transform: uppercase;">
                        <tr>
                            <td>
                                ATENTAMENTE<br>
                                @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                                    DIRECTORA
                                @else
                                    {{ $rolePersona->rolePersona->nombre ?? '' }}
                                @endif
                                <br><br><br><br>
                                ___________________________________<br>
                                {{ $rolePersona->persona->titulo }} {{ $nombreCompleto }}

                            </td>


                            <td>
                                @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                                    Vo.Bo. <br>JEFE INMEDIATO <br> <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorPreescolarNombre }}
                                @else
                                    Vo.Bo. <br>DIRECTORA <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorPreescolar }}
                                @endif
                            <td>


                            </td>


                        </tr>
                    </table>


                </div>

                @php
                    $titulos = [
                        'Prof.',
                        'Profr.',
                        'Profa.',
                        'Mtro.',
                        'Mtra.',
                        'Dr.',
                        'Dra.',
                        'Lic.',
                        'L.A.E.',
                        'Ing.',
                        'Arq.',
                        'Q.B.P.',
                        'Q.F.B.',
                        'C.P.',
                        'Téc.',
                        'Tec.',
                        'DCE.',
                        'D.C.E.',
                    ];

                    $texto = e($copias);

                    // Inserta <br> antes de cada título desde el 2do en adelante
                    $patron = '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                    $count = 0;
                    $copiasFormateado = preg_replace_callback(
                        $patron,
                        function ($m) use (&$count) {
                            $count++;
                            if ($count === 1) {
                                return ' ' . $m[1];
                            }
                            return '<br>' . $m[1];
                        },
                        $texto,
                    );

                    // ✅ Indentar desde la 2da línea (después del <br>)
                    $lineas = array_values(array_filter(array_map('trim', preg_split('/<br>/', $copiasFormateado))));

                    $copiasIndentado = '';
                    foreach ($lineas as $i => $linea) {
                        if ($i === 0) {
                            $copiasIndentado .= $linea;
                        } else {
                            $copiasIndentado .= '<br><span class="ccp-indent">' . $linea . '</span>';
                        }
                    }
                @endphp

                <style>
                    .ccp-indent {
                        display: inline-block;
                        padding-left: 25px;
                        /* ajusta este valor hasta que quede como en la imagen */
                    }
                </style>


                <div class="ccp" style="margin-top: 8px;">
                    <p style="font-size: 9px; margin:0;">
                        <b>{!! $copiasIndentado !!}</b>
                    </p>
                </div>



            </div>
            <div class="page-break"></div>
        @endforeach

        {{-- REANUDACIONES PRIMARIA --}}
    @elseif ($nivel->slug == 'primaria')
        @foreach ($asignacionesNivel as $personal)
            @php
                $fechaDocente = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
                \Carbon\Carbon::setLocale('es');
                $fechaDirector = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
            @endphp
            <img class="fondo_primaria" src="{{ public_path('storage/membrete_reanudacion_primaria.png') }}"
                alt="fondo" style="width: 100%; ">

            <div class="contenedor_primaria" style="padding: 100px 60px 0">

                <p style="text-align: right; font-size: 17px; line-height: 25px; "><b>ASUNTO: AVISO DE
                        REANUDACIÓN
                        DE
                        LABORES</b> <br>
                    @foreach ($personal->persona->personaRoles as $rolePersona)
                        @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                            Cd. Altamirano,
                            Gro.,
                            {{ $fechaDirector }}.
                        @else
                            Cd.Altamirano, Gro., {{ $fechaDocente }}.
                        @endif
                    @endforeach
                    <br> "{{ $escuela->lema }}"
                </p>

                <div class="autoridades" style="margin-top: -10px; text-transform: uppercase">
                    <table>
                        <tr>
                            <td style="width:350px">
                                <b>{{ $directorAdministracionNombre }}</b><br>{{ $directorAdministracion->cargo }}
                            </td>
                        </tr>

                        <tr>
                            <td></td>
                            <td style="width: 300px">
                                <b>AT'N. {{ $directorMagisterioNombre }}</b><br>{{ $directorMagisterio->cargo }}
                            </td>
                        </tr>
                    </table>

                </div>

                <div>

                    @foreach ($personal->persona->personaRoles as $rolePersona)
                        @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                            <p
                                style="text-transform: uppercase; text-align: justify; font-size: 14.5px; font-family: Verdana, Geneva, Tahoma, sans-serif; text-indent: 35px; line-height: 19px;">
                                EL (A) QUE SUSCRIBE C. <b><u>{{ $rolePersona->persona->titulo }}
                                        {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                ME PERMITO INFORMAR QUE A PARTIR DE ESTA FECHA, ME PRESENTÉ A REANUDAR
                                MIS LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                                <br>
                                PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                            </p>
                        @else
                            <p
                                style="text-transform: uppercase; text-align: justify; font-size: 14.5px; font-family: Verdana, Geneva, Tahoma, sans-serif; text-indent: 35px; line-height: 19px;">
                                EL (A) QUE SUSCRIBE C.

                                <b><u>{{ $rolePersona->persona->titulo }} {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                ME PERMITO INFORMAR QUE A PARTIR DE ESTA FECHA, ME PRESENTÉ A REANUDAR
                                MIS LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                                <br>
                                PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                            </p>
                        @endif
                    @endforeach
                </div>

                @php
                    $nombreCompleto = trim(
                        $personal->persona->nombre .
                            ' ' .
                            $personal->persona->apellido_paterno .
                            ' ' .
                            $personal->persona->apellido_materno,
                    );
                    $datos = [
                        'NOMBRE' => mb_strtoupper($nombreCompleto),
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE PRESUPUESTAL' => 'S/C',
                        'FECHA DE INGRESO A LA SEG' => !empty($personal->ingreso_seg)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_seg)
                                    ->locale('es')
                                    ->isoFormat('D [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',
                        'FECHA DE INGRESO AL C.T.' => !empty($personal->ingreso_ct)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_ct)
                                    ->locale('es')
                                    ->isoFormat('D [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',
                        'CATEGORÍA' => (function () use ($personal) {
                            $roles = [];

                            foreach ($personal->persona->personaRoles as $rolePersona) {
                                $roles[] =
                                    $rolePersona->rolePersona->nombre ?? ($rolePersona->rolePersona->slug ?? null);
                            }

                            $roles = array_values(array_filter($roles));

                            return mb_strtoupper(!empty($roles) ? implode(', ', $roles) : '---------');
                        })(),

                        'NOMBRE DEL CENTRO DE TRABAJO' => mb_strtoupper($escuela->nombre ?? '---------'),
                        'C.C.T.' => mb_strtoupper($nivel->cct ?? '---------'),
                        'LOCALIDAD' => 'CD. ' . mb_strtoupper($escuela->ciudad ?? '---------'),
                        'MUNICIPIO' => mb_strtoupper($escuela->municipio ?? '---------'),
                        'FUNCIÓN QUE DESEMPEÑA' => (function () use ($personal) {
                            $roles = [];

                            foreach ($personal->persona->personaRoles as $rolePersona) {
                                $roles[] =
                                    $rolePersona->rolePersona->nombre ?? ($rolePersona->rolePersona->slug ?? null);
                            }

                            $roles = array_values(array_filter($roles));

                            return mb_strtoupper(!empty($roles) ? implode(', ', $roles) : '---------');
                        })(),
                        'REGIÓN' => 'TIERRA CALIENTE',
                        'ZONA ESCOLAR' => $supervisorPrimaria->zona_escolar ?? '---------',
                    ];
                @endphp

                <div>
                    @foreach ($datos as $key => $dato)
                        <table
                            style="width: 100%; font-family: Verdana, Geneva, Tahoma, sans-serif; line-height: 13px;">
                            <tr>
                                <td style="width: 300px;">
                                    <b>{{ $key }}:</b>
                                </td>
                                <td style="border-bottom: 1px solid #000;">
                                    {{ $dato }}
                                </td>
                            </tr>
                        </table>
                    @endforeach
                </div>


                <p style="text-align: justify; font-family: Verdana, Geneva, Tahoma, sans-serif;">SIN OTRO ASUNTO QUE
                    TRATAR APROVECHO LA OCASIÓN PARA ENVIARLE UN CORDIAL
                    SALUDO.</p>

                <div class="firmas">
                    <table
                        style="width: 100%; margin:auto; font-size: 12px; text-align: center; font-family: Verdana, Geneva, Tahoma, sans-serif; text-transform: uppercase; font-weight: bold;">
                        <tr>
                            <td>


                                ATENTAMENTE<br><br><br><br><br>
                                ___________________________________<br>
                                {{ $rolePersona->persona->titulo }} {{ $nombreCompleto }} <br> RFC:
                                {{ $rolePersona->persona->rfc }} <br> CURP:
                                {{ $rolePersona->persona->curp }}


                            </td>

                            <td>
                                @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                                    Vo.Bo. <br>JEFE INMEDIATO <br>{{ $supervisorPrimaria->cargo }} <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorPrimariaNombre }}<br> RFC:
                                    {{ $supervisorPrimaria->rfc }} <br> CURP: {{ $supervisorPrimaria->curp }}
                                @else
                                    Vo.Bo. <br>DIRECTORA <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorPSNombre }}<br> RFC:
                                    {{ $directoraPS->rfc }} <br> CURP: {{ $directoraPS->curp }}
                                @endif



                            </td>
                        </tr>
                    </table>


                </div>
                @php
                    $titulos = [
                        'Prof.',
                        'Profr.',
                        'Profa.',
                        'Mtro.',
                        'Mtra.',
                        'Dr.',
                        'Dra.',
                        'Lic.',
                        'L.A.E.',
                        'Ing.',
                        'Arq.',
                        'Q.B.P.',
                        'Q.F.B.',
                        'C.P.',
                        'Téc.',
                        'Tec.',
                    ];

                    $texto = e($copias);

                    // CCP: todas las combinaciones comunes
                    $patronCcp = '/\s*(\(?\s*(?:c\s*\.?\s*c\s*\.?\s*p|ccp)\s*\.?\s*\)?)/iu';
                    $texto = preg_replace($patronCcp, '<br>$1', $texto);
                    $texto = preg_replace('/^(<br>\s*)+/u', '', $texto); // sin salto al inicio

                    // Resto de títulos: NO romper el primero
                    $patronTitulos = '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                    $count = 0;
                    $copiasFormateado = preg_replace_callback(
                        $patronTitulos,
                        function ($m) use (&$count) {
                            $count++;
                            return $count === 1 ? ' ' . $m[1] : '<br>' . $m[1];
                        },
                        $texto,
                    );
                @endphp

                <div class="ccp" style="margin-top: 0px; font-family: Verdana, Geneva, Tahoma, sans-serif;">
                    <p style="font-size: 8px">{!! $copiasFormateado !!}</p>
                </div>


            </div>
            <div class="page-break"></div>
        @endforeach
    @elseif ($nivel->slug == 'secundaria')
        @foreach ($asignacionesNivel as $personal)
            @php
                $fechaDocente = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
                \Carbon\Carbon::setLocale('es');
                $fechaDirector = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
            @endphp
            <img class="fondo_secundaria" src="{{ public_path('storage/membrete_reanudacion_secundaria.png') }}"
                alt="fondo" style="width: 100%;">

            <div class="contenedor_secundaria" style="padding: 100px 60px 0">

                <p style="text-align: right; font-size: 17px; line-height: 25px; "><b>ASUNTO:
                        REANUDACIÓN
                        DE
                        LABORES</b> <br>
                    @foreach ($personal->persona->personaRoles as $rolePersona)
                        @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                            Cd. Altamirano,
                            Gro.,
                            {{ $fechaDirector }}.
                        @else
                            Cd.Altamirano, Gro., {{ $fechaDocente }}.
                        @endif
                    @endforeach
                    <br> "{{ $escuela->lema }}"
                </p>

                <div class="delegado" style="margin-top: 10px; text-transform: uppercase; font-size: 15px">
                    <p style="width: 400px;"><b> {{ $delegado->titulo }} {{ $delegado->nombre }}
                            {{ $delegado->apellido_paterno }}
                            {{ $delegado->apellido_materno }} <br></b>

                    </p>
                    <p style="margin-top:-15px; width:320px"> {{ $delegado->cargo }}
                    </p>
                    <p><b>P R E S E N T E</b></p>

                </div>

                <div>

                    @foreach ($personal->persona->personaRoles as $rolePersona)
                        @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                            <p
                                style="text-transform: uppercase; text-align: justify; text-indent: 30px; line-height:20px;">
                                EL (A) QUE SUSCRIBE C. <b><u>{{ $rolePersona->persona->titulo }}
                                        {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                                LABORES, DESPUÉS DE HABER DISFRUTADO LAS <b><u>VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                                PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                            </p>
                        @else
                            <p
                                style="text-transform: uppercase; text-align: justify;  text-indent: 30px; line-height:20px;">
                                EL (A) QUE SUSCRIBE C.
                                <b><u>{{ $rolePersona->persona->titulo }} {{ $rolePersona->persona->nombre }}
                                        {{ $rolePersona->persona->apellido_paterno }}
                                        {{ $rolePersona->persona->apellido_materno }}</u></b>,
                                SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                                LABORES, DESPUÉS DE HABER DISFRUTADO LAS <b><u>VACACIONES DE PRIMAVERA</u></b>,
                                CORRESPONDIENTE AL CICLO ESCOLAR
                                <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                                PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                            </p>
                        @endif
                    @endforeach
                </div>

                @php
                    $nombreCompleto = trim(
                        $personal->persona->nombre .
                            ' ' .
                            $personal->persona->apellido_paterno .
                            ' ' .
                            $personal->persona->apellido_materno,
                    );
                    $datos = [
                        'NOMBRE' => mb_strtoupper($nombreCompleto),
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE (S) PRESUPUESTAL (S)' => 'S/C',
                        'CARGO QUE DESEMPEÑA' => (function () use ($personal) {
                            $roles = [];

                            foreach ($personal->persona->personaRoles as $rolePersona) {
                                $roles[] =
                                    $rolePersona->rolePersona->nombre ?? ($rolePersona->rolePersona->slug ?? null);
                            }

                            $roles = array_values(array_filter($roles));

                            return mb_strtoupper(!empty($roles) ? implode(', ', $roles) : '---------');
                        })(),
                        'FECHA DE INGRESO A LA SEP' => !empty($personal->ingreso_sep)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_sep)
                                    ->locale('es')
                                    ->isoFormat('D [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',
                        'FECHA DE INGRESO AL C.T.' => !empty($personal->ingreso_ct)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_ct)
                                    ->locale('es')
                                    ->isoFormat('D [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',

                        'NOMBRE DEL C.T.' => mb_strtoupper($escuela->nombre ?? '---------'),
                        'C.C.T.' => mb_strtoupper($nivel->cct ?? '---------'),
                        'UBICACIÓN' => 'FRACISCO I. MADERO OTE. 800 COL. ESQUIPULAS. CD ALTAMIRANO, GRO.',
                    ];
                @endphp

                <div>
                    @foreach ($datos as $key => $dato)
                        <table style="width: 100%; line-height: 15px;">
                            <tr>
                                <td style="width: 250px;">
                                    <b>{{ $key }}:</b>
                                </td>
                                <td style="border-bottom: 1px solid #000;">
                                    {{ $dato }}
                                </td>
                            </tr>
                        </table>
                    @endforeach
                </div>


                <p style="text-align: justify;">SIN OTRO PARTICULAR, APROVECHO LA OCASIÓN PARA ENVIARLE UN AFECTUOSO
                    SALUDO.
                    .</p>

                <div class="firmas">



                    <table
                        style="width: 100%; margin:auto; font-size: 13px; text-align: center;  text-transform: uppercase; font-weight: bold;">
                        <tr>
                            <td>
                                ATENTAMENTE<br>
                                @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                                    DIRECTORA
                                @else
                                    {{ $rolePersona->rolePersona->nombre ?? '' }}
                                @endif

                                <br><br><br><br>
                                ___________________________________<br>
                                {{ $rolePersona->persona->titulo }} {{ $nombreCompleto }}


                            </td>

                            <td>
                                @if ($rolePersona->rolePersona->slug == 'director_sin_grupo')
                                    Vo.Bo. <br>JEFE INMEDIATO <br>{{ $supervisorSecundaria->cargo }} <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorSecundariaNombre }}
                                @else
                                    Vo.Bo. <br>DIRECTORA <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorPSNombre }}
                                @endif



                            </td>
                        </tr>
                    </table>


                </div>
                @php
                    $titulos = [
                        'Prof.',
                        'Profr.',
                        'Profa.',
                        'Mtro.',
                        'Mtra.',
                        'Dr.',
                        'Dra.',
                        'Lic.',
                        'L.A.E.',
                        'Ing.',
                        'Arq.',
                        'Q.B.P.',
                        'Q.F.B.',
                        'C.P.',
                        'Téc.',
                        'Tec.',
                    ];

                    $texto = e($copias);

                    // Inserta <br> antes de cada título desde el 2do en adelante
                    $patron = '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                    $count = 0;
                    $copiasFormateado = preg_replace_callback(
                        $patron,
                        function ($m) use (&$count) {
                            $count++;
                            if ($count === 1) {
                                return ' ' . $m[1];
                            }
                            return '<br>' . $m[1];
                        },
                        $texto,
                    );

                    // ✅ Indentar desde la 2da línea (después del <br>)
                    $lineas = array_values(array_filter(array_map('trim', preg_split('/<br>/', $copiasFormateado))));

                    $copiasIndentado = '';
                    foreach ($lineas as $i => $linea) {
                        if ($i === 0) {
                            $copiasIndentado .= $linea;
                        } else {
                            $copiasIndentado .= '<br><span class="ccp-indent">' . $linea . '</span>';
                        }
                    }
                @endphp

                <style>
                    .ccp-indent {
                        display: inline-block;
                        padding-left: 29px;
                        /* ajusta este valor hasta que quede como en la imagen */
                    }
                </style>


                <div class="ccp" style="margin-top: 13px; font-family: Verdana, Geneva, Tahoma, sans-serif;">
                    <p style="font-size: 9px; margin:0;">
                        <b>{!! $copiasIndentado !!}</b>
                    </p>
                </div>

            </div>
            <div class="page-break"></div>
        @endforeach

    @endif
    {{-- {{ $asignacionesNivel }} --}}


</body>

</html>
