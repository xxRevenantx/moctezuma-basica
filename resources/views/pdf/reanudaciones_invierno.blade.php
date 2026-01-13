<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta charset="utf-8">
    <meta name="viewport"
        content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">

    <title>OFICIOS DE REANUDACIÓN DE INVIERNO</title>
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
        border-bottom: 1px solid #000;
        padding-bottom: 2px;
        font-weight: 700;
        text-transform: uppercase;
        line-height: 1.1;
    }

    .contenedor_preescolar {
        font-size: 14px;
    }

    .contenedor_primaria {
        font-size: 14px;
        margin-top: 20px;
    }

    .contenedor_secundaria {
        font-size: 14px;
        margin-top: 20px;
    }

    .fondo_primaria,
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

    .ccp-indent {
        display: inline-block;
        padding-left: 25px;
    }

    .ccp-indent-sec {
        display: inline-block;
        padding-left: 29px;
    }
</style>

<body>

    @php
        // ✅ Asegurar el orden para el PDF (por persona_nivel.orden)
        $asignacionesNivel = collect($asignacionesNivel ?? [])
            ->sortBy(fn($p) => [(int) ($p->orden ?? 999999), (int) ($p->id ?? 0)])
            ->values();

        $formatearNombreCompleto = function ($persona) {
            if (!$persona) {
                return '---------';
            }

            $partes = array_filter(
                [
                    $persona->titulo ?? null,
                    $persona->nombre ?? null,
                    $persona->apellido_paterno ?? null,
                    $persona->apellido_materno ?? null,
                ],
                fn($v) => !empty($v),
            );

            $nombre = trim(implode(' ', $partes));
            return $nombre !== '' ? mb_strtoupper($nombre) : '---------';
        };

        $nombreDirectorPreescolar = $formatearNombreCompleto($nivel->director ?? null);
        $nombreDirectorPrimariaNombre = $formatearNombreCompleto($nivel->director ?? null);
        $nombreDirectorSecundariaNombre = $formatearNombreCompleto($nivel->director ?? null);

        $supervisorPreescolarNombre = $formatearNombreCompleto($nivel->supervisor ?? null);
        $supervisorPrimariaNombre = $formatearNombreCompleto($nivel->supervisor ?? null);
        $supervisorSecundariaNombre = $formatearNombreCompleto($nivel->supervisor ?? null);

        $directorAdministracionNombre = $formatearNombreCompleto($directorAdministracion ?? null);
        $directorMagisterioNombre = $formatearNombreCompleto($directorMagisterio ?? null);

        // ✅ Helper: roles SOLO desde detalles (persona_nivel_detalles). Fallback a personaRoles si no hay detalles.
        $resolverCargosDesdeDetalles = function ($personal, bool $conSaltos = true) {
            $detallesOrdenados = collect($personal->detalles ?? [])
                ->sortBy(fn($d) => [(int) ($d->orden ?? 999999), (int) ($d->id ?? 0)])
                ->values();

            $cargosDetalles = $detallesOrdenados
                ->map(fn($d) => mb_strtoupper(optional(optional($d->PersonaRole)->rolePersona)->nombre ?? ''))
                ->filter()
                ->unique()
                ->values()
                ->all();

            if (empty($cargosDetalles)) {
                $cargosDetalles = collect($personal->persona->personaRoles ?? [])
                    ->map(fn($r) => mb_strtoupper(optional(optional($r)->rolePersona)->nombre ?? ''))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            }

            if (empty($cargosDetalles)) {
                return '---------';
            }

            return $conSaltos ? implode('<br />', $cargosDetalles) : implode(', ', $cargosDetalles);
        };

        // ✅ Helper: rol principal SOLO desde detalles (para fecha/firma). Fallback a personaRoles si no hay detalles.
        $resolverRolPrincipal = function ($personal) {
            $detallesOrdenados = collect($personal->detalles ?? [])
                ->sortBy(fn($d) => [(int) ($d->orden ?? 999999), (int) ($d->id ?? 0)])
                ->values();

            $detallePrincipal =
                $detallesOrdenados->first(
                    fn($d) => optional(optional($d->PersonaRole)->rolePersona)->slug === 'director_sin_grupo',
                ) ?? $detallesOrdenados->first();

            $slug = optional(optional(optional($detallePrincipal)->PersonaRole)->rolePersona)->slug;
            $nombre = optional(optional(optional($detallePrincipal)->PersonaRole)->rolePersona)->nombre;

            if (!$slug && !$nombre) {
                $rol =
                    collect($personal->persona->personaRoles ?? [])->first(
                        fn($r) => optional($r->rolePersona)->slug === 'director_sin_grupo',
                    ) ?? collect($personal->persona->personaRoles ?? [])->first();

                $slug = optional(optional($rol)->rolePersona)->slug;
                $nombre = optional(optional($rol)->rolePersona)->nombre;
            }

            return [$slug, $nombre];
        };
    @endphp

    {{-- =========================
        PREESCOLAR
    ========================= --}}
    @if ($nivel->slug == 'preescolar')

        @foreach ($asignacionesNivel as $personal)
            @php
                // ✅ Rol principal desde DETALLES
                [$slugRolPrincipal, $nombreRolPrincipal] = $resolverRolPrincipal($personal);

                \Carbon\Carbon::setLocale('es');
                $fechaDocente = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                    ->locale('es')
                    ->isoFormat('DD [de] MMMM [de] YYYY');
                $fechaDirector = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                    ->locale('es')
                    ->isoFormat('DD [de] MMMM [de] YYYY');
                $fechaCarta = $slugRolPrincipal === 'director_sin_grupo' ? $fechaDirector : $fechaDocente;

                $nombreCompletoRaw = trim(
                    ($personal->persona->nombre ?? '') .
                        ' ' .
                        ($personal->persona->apellido_paterno ?? '') .
                        ' ' .
                        ($personal->persona->apellido_materno ?? ''),
                );
                $nombreCompleto = $nombreCompletoRaw !== '' ? mb_strtoupper($nombreCompletoRaw) : '---------';

                // ✅ CARGO QUE DESEMPEÑA: SOLO desde detalles (con saltos)
                $cargosHtml = $resolverCargosDesdeDetalles($personal, true);
            @endphp

            <img class="fondo_preescolar" src="{{ public_path('storage/reanudacion_preescolar.jpg') }}" alt="fondo"
                style="width: 100%;">

            <div class="contenedor_preescolar" style="padding: 30px 120px 0">


                <p style="text-align: right; text-transform: uppercase; font-size:16px">
                    CIUDAD ALTAMIRANO, GRO. A {{ $fechaCarta }}.
                </p>


                <p
                    style="text-align: right; margin-right: 60px; font-size: 18px; margin-top:-15px; text-transform: uppercase;">
                    @if (empty(trim($escuela->lema ?? '')))
                    @else
                        "{{ $escuela->lema }}"
                    @endif
                </p>

                <p style="text-align: right; margin-right: 50px; font-size: 16px;">
                    ASUNTO: REANUDACIÓN DE LABORES
                </p>



                <div class="delegado" style="margin-top: 10px; text-transform: uppercase; font-size: 15px;">
                    <p style="width: 400px;">
                        <b>
                            {{ $delegado->titulo }} {{ $delegado->nombre }} {{ $delegado->apellido_paterno }}
                            {{ $delegado->apellido_materno }} <br>
                        </b>
                    </p>
                    <p style="margin-top:-15px; width:320px">
                        {{ $delegado->cargo }}
                    </p>
                </div>

                <div>
                    <p style="text-transform: uppercase; text-align: justify;">
                        EL (A) QUE SUSCRIBE C.<b><u>
                                @if ($personal->persona->titulo !== 'C.')
                                    {{ $personal->persona->titulo }}
                                @endif{{ $nombreCompleto }}
                            </u></b>,
                        SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                        LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE INVIERNO</u></b>,
                        CORRESPONDIENTE AL CICLO ESCOLAR
                        <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                        <br>PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                    </p>
                </div>


                @php
                    $datos = [
                        'NOMBRE COMPLETO' => $nombreCompleto,
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE (S) PRESUPUESTAL (ES)' => 'S/C',
                        'CARGO QUE DESEMPEÑA' => $cargosHtml, // ✅ desde detalles
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

                <div style="line-height: 10px">
                    @foreach ($datos as $key => $dato)
                        @php
                            $contar = strlen(strip_tags($dato)) + strlen($key);
                            $espacioBlanco = '';
                            for ($i = $contar; $i < 60; $i++) {
                                $espacioBlanco .= '&nbsp;&nbsp;';
                            }
                        @endphp
                        <div class="fila-dato">
                            <div class="label">
                                {{ $key }}:
                                @if ($key === 'CARGO QUE DESEMPEÑA')
                                    <b><u>{!! $dato !!}{!! $espacioBlanco !!}</u></b>
                                @else
                                    <b><u>{{ $dato }}{!! $espacioBlanco !!}</u></b>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                <p style="text-align: justify">
                    SIN OTRO PARTICULAR, APROVECHO LA OCASIÓN PARA ENVIARLE UN AFECTUOSO SALUDO.
                </p>

                <div class="firmas">
                    <table style="width: 100%; margin:auto; text-align: center; text-transform: uppercase;">
                        <tr>
                            <td>
                                ATENTAMENTE<br>
                                @if ($slugRolPrincipal === 'director_sin_grupo')
                                    DIRECTORA
                                @else
                                    {{ $nombreRolPrincipal ?? 'MAESTRO(A)' }}
                                @endif
                                <br><br><br><br>
                                ___________________________________<br>
                                {{ $personal->persona->titulo }} {{ $nombreCompleto }}
                            </td>

                            <td>
                                @if ($slugRolPrincipal === 'director_sin_grupo')
                                    Vo.Bo. <br>JEFE INMEDIATO <br> <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorPreescolarNombre }}
                                @else
                                    Vo.Bo. <br>DIRECTORA <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorPreescolar }}
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
                        'DCE.',
                        'D.C.E.',
                    ];

                    $texto = e($copias);
                    $patron = '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                    $count = 0;
                    $copiasFormateado = preg_replace_callback(
                        $patron,
                        function ($m) use (&$count) {
                            $count++;
                            return $count === 1 ? ' ' . $m[1] : '<br>' . $m[1];
                        },
                        $texto,
                    );

                    $lineas = array_values(array_filter(array_map('trim', preg_split('/<br>/', $copiasFormateado))));

                    $copiasIndentado = '';
                    foreach ($lineas as $i => $linea) {
                        $copiasIndentado .= $i === 0 ? $linea : '<br><span class="ccp-indent">' . $linea . '</span>';
                    }
                @endphp

                <div class="ccp" style="margin-top: 8px;">
                    <p style="font-size: 9px; margin:0;">
                        <b>{!! $copiasIndentado !!}</b>
                    </p>
                </div>

            </div>

            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach

        {{-- =========================
        PRIMARIA
    ========================= --}}
    @elseif ($nivel->slug == 'primaria')
        @foreach ($asignacionesNivel as $personal)
            @php
                // ✅ Rol principal desde DETALLES
                [$slugRolPrincipal, $nombreRolPrincipal] = $resolverRolPrincipal($personal);

                \Carbon\Carbon::setLocale('es');
                $fechaDocente = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
                $fechaDirector = \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                    ->locale('es')
                    ->isoFormat('D [de] MMMM [de] YYYY');
                $fechaCarta = $slugRolPrincipal === 'director_sin_grupo' ? $fechaDirector : $fechaDocente;

                $nombreCompletoRaw = trim(
                    ($personal->persona->nombre ?? '') .
                        ' ' .
                        ($personal->persona->apellido_paterno ?? '') .
                        ' ' .
                        ($personal->persona->apellido_materno ?? ''),
                );
                $nombreCompleto = $nombreCompletoRaw !== '' ? mb_strtoupper($nombreCompletoRaw) : '---------';

                // ✅ CATEGORÍA / FUNCIÓN: SOLO desde detalles (en una sola línea con comas)
                $cargosHtml = $resolverCargosDesdeDetalles($personal, false);
            @endphp

            <img class="fondo_primaria" src="{{ public_path('storage/membrete_reanudacion_primaria.png') }}"
                alt="fondo" style="width: 100%; ">

            <div class="contenedor_primaria" style="padding: 100px 60px 0">

                <p style="text-align: right; font-size: 17px; line-height: 25px;">
                    <b>ASUNTO: AVISO DE REANUDACIÓN DE LABORES</b> <br>
                    Cd. Altamirano, Gro., {{ $fechaCarta }}.
                    <br>
                <p style="text-align: right; font-size: 17px; margin-top: -10px ">
                    @if (empty(trim($escuela->lema ?? '')))
                    @else
                        "{{ $escuela->lema }}"
                    @endif
                </p>
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
                    <p
                        style="text-transform: uppercase; text-align: justify; font-size: 14.4px; font-family: Verdana, Geneva, Tahoma, sans-serif; text-indent: 50px; line-height: 19px;">
                        EL (A) QUE SUSCRIBE C. <b><u>
                                @if ($personal->persona->titulo !== 'C.')
                                    {{ $personal->persona->titulo }}
                                @endif{{ $nombreCompleto }}
                            </u></b>,
                        ME PERMITO INFORMAR QUE A PARTIR DE ESTA FECHA, ME PRESENTÉ A REANUDAR
                        MIS LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE INVIERNO</u></b>,
                        CORRESPONDIENTE AL CICLO ESCOLAR
                        <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}.</b>
                    </p>
                </div>

                <p
                    style="text-transform: uppercase; text-align: justify; font-family: Verdana, Geneva, Tahoma, sans-serif; margin-top: -10px;">
                    PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                </p>

                @php
                    $datos = [
                        'NOMBRE' => $nombreCompleto,
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE PRESUPUESTAL' => 'S/C',
                        'FECHA DE INGRESO A LA SEG' => !empty($personal->ingreso_seg)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_seg)
                                    ->locale('es')
                                    ->isoFormat('DD [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',
                        'FECHA DE INGRESO AL C.T.' => !empty($personal->ingreso_ct)
                            ? mb_strtoupper(
                                \Carbon\Carbon::parse($personal->ingreso_ct)
                                    ->locale('es')
                                    ->isoFormat('DD [DE] MMMM [DEL] YYYY'),
                            )
                            : '---------',
                        'CATEGORÍA' => $cargosHtml, // ✅ desde detalles
                        'NOMBRE DEL CENTRO DE TRABAJO' => mb_strtoupper($escuela->nombre ?? '---------'),
                        'C.C.T.' => mb_strtoupper($nivel->cct ?? '---------'),
                        'LOCALIDAD' => 'CD. ' . mb_strtoupper($escuela->ciudad ?? '---------'),
                        'MUNICIPIO' => mb_strtoupper($escuela->municipio ?? '---------'),
                        'FUNCIÓN QUE DESEMPEÑA' => $cargosHtml, // ✅ desde detalles
                        'REGIÓN' => 'TIERRA CALIENTE',
                        'ZONA ESCOLAR' => $nivel->supervisor->zona_escolar ?? '---------',
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

                <p style="text-align: justify; font-family: Verdana, Geneva, Tahoma, sans-serif;">
                    SIN OTRO ASUNTO QUE TRATAR APROVECHO LA OCASIÓN PARA ENVIARLE UN CORDIAL SALUDO.
                </p>

                <div class="firmas">
                    <table
                        style="width: 100%; margin:auto; font-size: 12px; text-align: center; font-family: Verdana, Geneva, Tahoma, sans-serif; text-transform: uppercase; font-weight: bold;">
                        <tr>
                            <td>
                                ATENTAMENTE<br><br><br><br><br>
                                ___________________________________<br>
                                {{ $personal->persona->titulo }} {{ $nombreCompleto }} <br>
                                RFC: {{ $personal->persona->rfc }} <br>
                                CURP: {{ $personal->persona->curp }}
                            </td>

                            <td>
                                @if ($slugRolPrincipal === 'director_sin_grupo')
                                    Vo.Bo. <br>JEFE INMEDIATO <br>{{ $nivel->supervisor->cargo }} <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorPrimariaNombre }}<br>
                                    RFC: {{ $nivel->supervisor->rfc }} <br>
                                    CURP: {{ $nivel->supervisor->curp }}
                                @else
                                    Vo.Bo. <br>DIRECTORA <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorPrimariaNombre }}<br>
                                    RFC: {{ $nivel->director->rfc }} <br>
                                    CURP: {{ $nivel->director->curp }}
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

                    if (empty(trim($copias))) {
                        $copiasFormateado =
                            'C.C.P. JOSÉ ZAMORA ÁVILA, JEFE DE DEPARTAMENTO DE PRIMARIA ESTATAL<br>C.C.P. LIC. SABINO FLORES LEÒN. JEFE REGIONAL 01. REGION TIERRA CALIENTE. CD. ALTAMIRANO, GRO<br>C.C.P. PROFR (A) ADELFO MARTINEZ MARTINEZ - SUPERVISOR (A) ZONA ESCOLAR 030. CD ALTAMIRANO, GRO.<br>C.C.P. EL INTERESADO';
                    } else {
                        $texto = e($copias);

                        $patronCcp = '/\s*(\(?\s*(?:c\s*\.?\s*c\s*\.?\s*p|ccp)\s*\.?\s*\)?)/iu';
                        $texto = preg_replace($patronCcp, '<br>$1', $texto);
                        $texto = preg_replace('/^(<br>\s*)+/u', '', $texto);

                        $patronTitulos =
                            '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                        $count = 0;
                        $copiasFormateado = preg_replace_callback(
                            $patronTitulos,
                            function ($m) use (&$count) {
                                $count++;
                                return $count === 1 ? ' ' . $m[1] : '<br>' . $m[1];
                            },
                            $texto,
                        );
                    }
                @endphp

                <div class="ccp" style="margin-top: 0px; font-family: Verdana, Geneva, Tahoma, sans-serif;">
                    <p style="font-size: 8px">{!! $copiasFormateado !!}</p>
                </div>

            </div>

            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach

        {{-- =========================
        SECUNDARIA
    ========================= --}}
    @elseif ($nivel->slug == 'secundaria')
        @foreach ($asignacionesNivel as $personal)
            @php
                $rolPrincipal =
                    collect($personal->persona->personaRoles ?? [])->first(
                        fn($r) => optional($r->rolePersona)->slug === 'director_sin_grupo',
                    ) ?? collect($personal->persona->personaRoles ?? [])->first();

                $slugRolPrincipal = optional(optional($rolPrincipal)->rolePersona)->slug;
                $nombreRolPrincipal = optional(optional($rolPrincipal)->rolePersona)->nombre;

                \Carbon\Carbon::setLocale('es');
                $fechaDocente = mb_strtoupper(
                    \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_docente)
                        ->locale('es')
                        ->isoFormat('DD [DE] MMMM [DE] YYYY'),
                );
                $fechaDirector = mb_strtoupper(
                    \Carbon\Carbon::createFromFormat('Y-m-d', $fecha_director)
                        ->locale('es')
                        ->isoFormat('DD [DE] MMMM [DE] YYYY'),
                );
                $fechaCarta = $slugRolPrincipal === 'director_sin_grupo' ? $fechaDirector : $fechaDocente;

                $nombreCompletoRaw = trim(
                    ($personal->persona->nombre ?? '') .
                        ' ' .
                        ($personal->persona->apellido_paterno ?? '') .
                        ' ' .
                        ($personal->persona->apellido_materno ?? ''),
                );
                $nombreCompleto = $nombreCompletoRaw !== '' ? mb_strtoupper($nombreCompletoRaw) : '---------';

                // ✅ IMPORTANTÍSIMO: ordenar detalles por orden antes de imprimir cargos
                $detallesOrdenados = collect($personal->detalles ?? [])
                    ->sortBy(fn($d) => [(int) ($d->orden ?? 999999), (int) ($d->id ?? 0)])
                    ->values();

                $cargosDetalles = $detallesOrdenados
                    ->map(function ($d) {
                        $nombre = optional(optional($d->PersonaRole)->rolePersona)->nombre;
                        $grado = optional($d->grado)->nombre;

                        if (!$nombre) {
                            return null;
                        }

                        return $grado ? mb_strtoupper('>' . $grado . '° ' . $nombre) : mb_strtoupper($nombre);
                    })
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $cargosHtml = !empty($cargosDetalles) ? implode('<br />', $cargosDetalles) : '---------';
            @endphp

            <img class="fondo_secundaria" src="{{ public_path('storage/membrete_reanudacion_secundaria.png') }}"
                alt="fondo" style="width: 100%;">

            <div class="contenedor_secundaria" style="padding: 100px 60px 0">

                <p style="text-align: right; font-size: 17px; line-height: 25px; text-transform: uppercase;">
                    <b>ASUNTO: REANUDACIÓN DE LABORES</b> <br>
                    CD. ALTAMIRANO, GRO., A {{ $fechaCarta }}.
                    <br>
                    <span style="font-size: 14px; font-weight: bold;">
                        @if (empty(trim($escuela->lema ?? '')))
                        @else
                            "{{ $escuela->lema }}"
                        @endif
                    </span>
                </p>

                <div class="delegado" style="margin-top: 10px; text-transform: uppercase; font-size: 15px">
                    <p style="width: 400px;">
                        <b>
                            {{ $delegado->titulo }} {{ $delegado->nombre }}
                            {{ $delegado->apellido_paterno }}
                            {{ $delegado->apellido_materno }} <br>
                        </b>
                    </p>
                    <p style="margin-top:-15px; width:320px">
                        {{ $delegado->cargo }}
                    </p>
                    <p><b>P R E S E N T E</b></p>
                </div>

                <div>
                    <p style="text-transform: uppercase; text-align: justify; text-indent: 30px; line-height: 20px;">
                        EL (A) QUE SUSCRIBE C. <b><u>
                                @if ($personal->persona->titulo !== 'C.')
                                    {{ $personal->persona->titulo }}
                                @endif{{ $nombreCompleto }}
                            </u></b>,
                        SE DIRIGE A USTED PARA INFORMARLE QUE, CON FECHA ARRIBA SEÑALADA, ME PRESENTÉ A REANUDAR
                        LABORES, DESPUÉS DE HABER DISFRUTADO <b><u>LAS VACACIONES DE INVIERNO</u></b>,
                        CORRESPONDIENTE AL CICLO ESCOLAR
                        <b>{{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}</b>.
                        PARA LO CUAL PROPORCIONO LOS SIGUIENTES DATOS:
                    </p>
                </div>

                @php
                    $datos = [
                        'NOMBRE' => $nombreCompleto,
                        'FILIACIÓN' => mb_strtoupper($personal->persona->rfc ?? '---------'),
                        'CURP' => mb_strtoupper($personal->persona->curp ?? '---------'),
                        'CLAVE (S) PRESUPUESTAL (S)' => 'S/C',
                        'CARGO QUE DESEMPEÑA' => $cargosHtml,
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
                                    @if ($key === 'CARGO QUE DESEMPEÑA')
                                        {!! $dato !!}
                                    @else
                                        {{ $dato }}
                                    @endif
                                </td>
                            </tr>
                        </table>
                    @endforeach
                </div>

                <p style="text-align: justify;">
                    SIN OTRO PARTICULAR, APROVECHO LA OCASIÓN PARA ENVIARLE UN AFECTUOSO SALUDO.
                </p>

                <div class="firmas">
                    <table
                        style="width: 100%; margin:auto; font-size: 13px; text-align: center; text-transform: uppercase; font-weight: bold;">
                        <tr>
                            <td>
                                ATENTAMENTE<br>
                                @if ($slugRolPrincipal === 'director_sin_grupo')
                                    DIRECTOR(A)
                                @endif
                                <br><br><br><br>
                                ___________________________________<br>
                                {{ $personal->persona->titulo }} {{ $nombreCompleto }}
                            </td>

                            <td>
                                @if ($slugRolPrincipal === 'director_sin_grupo')
                                    Vo.Bo. <br>
                                    JEFE INMEDIATO <br>
                                    {{ $nivel->supervisor->cargo ?? '---------' }} <br><br><br>
                                    ___________________________________<br>
                                    {{ $supervisorSecundariaNombre }}
                                @else
                                    Vo.Bo. <br>
                                    DIRECTOR(A) <br><br><br><br>
                                    ___________________________________<br>
                                    {{ $nombreDirectorSecundariaNombre }}
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
                    $patron = '/\s+(' . implode('|', array_map(fn($t) => preg_quote($t, '/'), $titulos)) . ')/u';

                    $count = 0;
                    $copiasFormateado = preg_replace_callback(
                        $patron,
                        function ($m) use (&$count) {
                            $count++;
                            return $count === 1 ? ' ' . $m[1] : '<br>' . $m[1];
                        },
                        $texto,
                    );

                    $lineas = array_values(array_filter(array_map('trim', preg_split('/<br>/', $copiasFormateado))));

                    $copiasIndentado = '';
                    foreach ($lineas as $i => $linea) {
                        $copiasIndentado .=
                            $i === 0 ? $linea : '<br><span class="ccp-indent-sec">' . $linea . '</span>';
                    }
                @endphp

                <div class="ccp" style="margin-top: 13px; font-family: Verdana, Geneva, Tahoma, sans-serif;">
                    <p style="font-size: 9px; margin:0;">
                        <b>{!! $copiasIndentado !!}</b>
                    </p>
                </div>

            </div>

            @if (!$loop->last)
                <div class="page-break"></div>
            @endif
        @endforeach

    @endif

</body>

</html>
