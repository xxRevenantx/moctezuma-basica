<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">

    <title>Historial académico</title>

    <style>
        @page {
            margin: 7mm 8mm 7mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #20242a;
            font-size: 12px;
        }

        .page {
            position: relative;
            height: 257mm;
        }

        .page-one {
            page-break-after: always;
        }

        .page-two {
            page-break-after: avoid;
        }

        /*
        |--------------------------------------------------------------------------
        | Encabezado
        |--------------------------------------------------------------------------
        */

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3px;
        }

        .header td {
            border: 0;
            vertical-align: middle;
        }

        .logo-seg {
            width: 160px;
            height: auto;
        }

        .logo-plantel {
            width: 190px;
            height: auto;
        }

        .school {
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .document-title {
            margin-top: 4px;
            text-align: center;
            color: #8f2929;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 3.2px;
        }

        /*
        |--------------------------------------------------------------------------
        | Datos del alumno
        |--------------------------------------------------------------------------
        */

        .student {
            width: 100%;
            border-collapse: collapse;
        }

        .student th,
        .student td {
            border: .65px solid #303030;
            padding: 1.2px 2px;
            text-align: center;
            line-height: 1.05;
        }

        .student th {
            background: #f3f4f6;
            font-size: 10px;
            text-transform: uppercase;
        }

        .student td {
            font-size: 10px;
        }

        .student-layout {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }

        .student-layout>tbody>tr>td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .student-panel {
            width: 100%;
        }

        /*
        |--------------------------------------------------------------------------
        | Fotografía
        |--------------------------------------------------------------------------
        */

        .photo-panel {
            width: 54px;
            text-align: center;
            padding: 15px 0 0 5px !important;
        }

        .photo {
            width: 43px;
            height: 54px;
            object-fit: cover;
            border: .6px solid #999;
        }

        .photo-empty {
            width: 43px;
            height: 54px;
            margin: auto;
            border: .6px dashed #aaa;
            color: #999;
            font-size: 5.5px;
            display: table;
        }

        .photo-empty span {
            display: table-cell;
            vertical-align: middle;
        }

        /*
        |--------------------------------------------------------------------------
        | Semestres y materias
        |--------------------------------------------------------------------------
        */

        .semester {
            margin-top: 3px;
            page-break-inside: avoid;
        }

        .semester-head {
            display: table;
            width: 100%;
            color: #9d4a4a;
            font-size: 10px;
            margin-bottom: 0;
        }

        .semester-head span {
            display: table-cell;
            width: 50%;
        }

        .semester-head span:last-child {
            text-align: right;
        }

        .subjects {
            width: 100%;
            border-collapse: collapse;
        }

        .subjects th {
            color: #9d4a4a;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.02;
            padding: .75px 1.3px;
            border-bottom: .55px solid #d2b8b8;
        }

        .subjects td {
            font-size: 10px;
            line-height: 1.02;
            padding: .10px 1.3px;
            border-bottom: .35px solid #ececec;
        }

        .subjects .key {
            width: 7%;
            text-align: center;
        }

        .subjects .name {
            width: 43%;
            text-align: left;
        }

        .subjects .grade {
            width: 7%;
            text-align: center;
            font-weight: 700;
        }

        .subjects .assist {
            width: 7%;
            text-align: center;
        }

        .subjects .regular {
            width: 36%;
            text-align: center;
        }

        .reg-grid {
            width: 100%;
            border-collapse: collapse;
        }

        .reg-grid td {
            padding: 0;
            border: 0;
            text-align: center;
            font-size: 7px;
            color: #777;
        }

        /*
        |--------------------------------------------------------------------------
        | Resumen académico
        |--------------------------------------------------------------------------
        */

        .summary-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            margin-top: 5px;
            page-break-inside: avoid;
        }

        .summary-grid td {
            width: 20%;
            border: .6px solid #d8dee8;
            border-radius: 5px;
            padding: 3px;
            background: #f8fafc;
            text-align: center;
        }

        .summary-grid small {
            display: block;
            color: #667085;
            font-size: 7.5px;
            text-transform: uppercase;
        }

        .summary-grid strong {
            display: block;
            margin-top: 2px;
            color: #0f172a;
            font-size: 10px;
        }

        .final-title {
            margin-top: 10px;
            text-align: center;
            font-size: 12px;
            font-weight: 700;
        }

        .average {
            margin-top: 5px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | Firmas y sellos
        |--------------------------------------------------------------------------
        */

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;

            /*
             * Se reduce el padding para que los sellos tengan suficiente espacio.
             */
            padding: 0 35px;
        }

        /*
         * El contenedor debe tener altura suficiente para:
         * - sello;
         * - firma;
         * - nombre;
         * - cargo.
         */
        .signature-box {
            position: relative;
            height: 180px;
        }

        /*
         * No se establece una altura fija para evitar que Dompdf
         * corte o deforme las imágenes.
         */
        .signature-seal {
            position: absolute;
            z-index: 1;
            display: block;
            height: auto;
        }

        /*
         * Sello del director.
         * Es una imagen principalmente vertical.
         */
        .signature-seal.left {
            width: 115px;
            height: auto;
            top: 0;
            left: 50%;
            margin-left: -57.5px;
        }

        /*
         * Sello del jefe de departamento.
         * Es una imagen horizontal que ya puede contener la firma.
         */
        .signature-seal.right {
            width: 150px;
            height: auto;
            top: 0;
            left: 50%;
            margin-left: -105px;
        }

        /*
         * Firma manuscrita independiente.
         */
        .signature-hand {
            position: absolute;
            z-index: 2;
            display: block;
            width: 155px;
            height: auto;
            left: 50%;
            top: 26px;
            margin-left: -77.5px;
        }

        /*
         * Línea para firma física cuando no se incluyen imágenes digitales.
         */
        .signature-physical-line {
            position: absolute;
            left: 16%;
            right: 16%;
            bottom: 40px;
            border-top: .6px solid #303030;
        }

        /*
         * Nombre y cargo siempre permanecen en la parte inferior.
         */
        .signature-data {
            position: absolute;
            z-index: 3;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
        }

        .signatures .name {
            font-size: 7.5px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .signatures .role {
            margin-top: 2px;
            font-size: 7.5px;
            line-height: 1.2;
        }

        /*
        |--------------------------------------------------------------------------
        | Pie de página
        |--------------------------------------------------------------------------
        */

        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
            color: #8a8f98;
            font-size: 7.5px;
        }
    </style>
</head>

<body>
    @php
        $nombreCompleto = mb_strtoupper(
            trim(implode(' ', array_filter([$alumno->apellido_paterno, $alumno->apellido_materno, $alumno->nombre]))),
        );

        $firmanteDirector = data_get($institucional, 'firmantes.director', []);

        $firmanteJefe = data_get($institucional, 'firmantes.jefe_registro', []);

        $logoSegPdf = data_get($institucional, 'logo_seg_pdf');

        $logoPlantelPdf = data_get($institucional, 'logo_plantel_pdf');

        $firmaDirectorPdf = data_get($firmanteDirector, 'firma_pdf');

        $selloDirectorPdf = data_get($firmanteDirector, 'sello_pdf');

        $firmaJefePdf = data_get($firmanteJefe, 'firma_pdf');

        $selloJefePdf = data_get($firmanteJefe, 'sello_pdf');

        $incluirFirmas = (bool) ($incluir_firmas_digitales ?? true);
    @endphp

    {{-- ========================================================= --}}
    {{-- PÁGINA 1 --}}
    {{-- ========================================================= --}}

    <div class="page page-one">
        <table class="header">
            <tr>
                <td style="width: 20%;">
                    @if ($logoSegPdf)
                        <img class="logo-seg" src="{{ $logoSegPdf }}" alt="Logotipo SEG">
                    @endif
                </td>

                <td style="width: 60%;" class="school">
                    {{ mb_strtoupper($institucional['plantel']) }}
                </td>

                <td style="width: 30%; text-align: right;">
                    @if ($logoPlantelPdf)
                        <img class="logo-plantel" src="{{ $logoPlantelPdf }}" alt="Logotipo del plantel">
                    @endif
                </td>
            </tr>
        </table>

        <div class="document-title">
            HISTORIAL ACADÉMICO
        </div>

        <table class="student-layout">
            <tr>
                <td>
                    <table class="student student-panel">
                        <tr>
                            <th colspan="4">
                                Nombre del plantel
                            </th>

                            <th>
                                Clave
                            </th>
                        </tr>

                        <tr>
                            <td colspan="4">
                                {{ mb_strtoupper($institucional['plantel']) }}
                            </td>

                            <td>
                                {{ $institucional['cct'] }}
                            </td>
                        </tr>

                        <tr>
                            <th colspan="5">
                                Domicilio
                            </th>
                        </tr>

                        <tr>
                            <td colspan="5">
                                {{ mb_strtoupper($institucional['direccion']) }}
                            </td>
                        </tr>

                        <tr>
                            <th>
                                Primer apellido
                            </th>

                            <th>
                                Segundo apellido
                            </th>

                            <th>
                                Nombre(s)
                            </th>

                            <th>
                                CURP
                            </th>

                            <th>
                                Matrícula
                            </th>
                        </tr>

                        <tr>
                            <td>
                                {{ mb_strtoupper($alumno->apellido_paterno) }}
                            </td>

                            <td>
                                {{ mb_strtoupper($alumno->apellido_materno) }}
                            </td>

                            <td>
                                {{ mb_strtoupper($alumno->nombre) }}
                            </td>

                            <td>
                                {{ mb_strtoupper($alumno->curp) }}
                            </td>

                            <td>
                                {{ mb_strtoupper($alumno->matricula) }}
                            </td>
                        </tr>

                        <tr>
                            <th colspan="3">
                                Nivel de estudios
                            </th>

                            <th>
                                Modalidad
                            </th>

                            <th>
                                Núm. de incorporación
                            </th>
                        </tr>

                        <tr>
                            <td colspan="3">
                                BACHILLERATO GENERAL
                            </td>

                            <td>
                                {{ mb_strtoupper($institucional['modalidad']) }}
                            </td>

                            <td>
                                {{ $institucional['numero_acuerdo'] }}
                            </td>
                        </tr>
                    </table>
                </td>

                @if ($mostrar_foto)
                    <td class="photo-panel">
                        @if ($foto_data_uri)
                            <img src="{{ $foto_data_uri }}" class="photo" alt="Fotografía del alumno">
                        @else
                            <div class="photo-empty">
                                <span>SIN FOTO</span>
                            </div>
                        @endif
                    </td>
                @endif
            </tr>
        </table>

        @foreach ($semestres_pagina_1 as $semestre)
            @include('pdf.media-superior.partials.historial-semestre', [
                'semestre' => $semestre,
                'institucional' => $institucional,
            ])
        @endforeach

        <div class="footer">
            Página 1 de 2 · Documento generado el
            {{ $fecha_documento_corta ?? now()->format('d/m/Y') }}
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- PÁGINA 2 --}}
    {{-- ========================================================= --}}

    <div class="page page-two">
        <table class="header">
            <tr>
                <td style="width: 20%;">
                    @if ($logoSegPdf)
                        <img class="logo-seg" src="{{ $logoSegPdf }}" alt="Logotipo SEG">
                    @endif
                </td>

                <td style="width: 60%;" class="school">
                    {{ mb_strtoupper($institucional['plantel']) }}
                </td>

                <td style="width: 20%; text-align: right;">
                    @if ($logoPlantelPdf)
                        <img class="logo-plantel" src="{{ $logoPlantelPdf }}" alt="Logotipo del plantel">
                    @endif
                </td>
            </tr>
        </table>

        <div class="document-title">
            HISTORIAL ACADÉMICO
        </div>

        <div
            style="
                margin-top: 5px;
                text-align: center;
                font-size: 7px;
                font-weight: 700;
            ">
            {{ $nombreCompleto }} · {{ $alumno->matricula }}
        </div>

        @foreach ($semestres_pagina_2 as $semestre)
            @include('pdf.media-superior.partials.historial-semestre', [
                'semestre' => $semestre,
                'institucional' => $institucional,
            ])
        @endforeach

        <table class="summary-grid">
            <tr>
                <td>
                    <small>Promedio general</small>

                    <strong>
                        {{ $promedio_general }}
                    </strong>
                </td>

                <td>
                    <small>Materias evaluadas</small>

                    <strong>
                        {{ data_get($resumen_historial, 'materias_evaluadas', 0) }}
                    </strong>
                </td>

                <td>
                    <small>Acreditadas</small>

                    <strong>
                        {{ data_get($resumen_historial, 'materias_acreditadas', 0) }}
                    </strong>
                </td>

                <td>
                    <small>No acreditadas</small>

                    <strong>
                        {{ data_get($resumen_historial, 'materias_no_acreditadas', 0) }}
                    </strong>
                </td>

                <td>
                    <small>Situación</small>

                    <strong style="font-size: 10px;">
                        {{ data_get($resumen_historial, 'situacion', 'SIN REGISTROS') }}
                    </strong>
                </td>
            </tr>
        </table>

        <div class="average">
            PROMEDIO GENERAL: {{ $promedio_general }}
        </div>

        {{-- ===================================================== --}}
        {{-- FIRMAS --}}
        {{-- ===================================================== --}}

        <table class="signatures">
            <tr>
                {{-- Firma del director --}}
                <td>
                    <div class="signature-box">
                        @if ($incluirFirmas && $selloDirectorPdf)
                            <img class="signature-seal left" src="{{ $selloDirectorPdf }}" alt="Sello del plantel">
                        @endif

                        @if ($incluirFirmas && $firmaDirectorPdf)
                            <img class="signature-hand" src="{{ $firmaDirectorPdf }}"
                                alt="Firma del director del plantel">
                        @endif

                        @if (!$incluirFirmas || (!$firmaDirectorPdf && !$selloDirectorPdf))
                            <div class="signature-physical-line"></div>
                        @endif

                        <div class="signature-data">
                            <div class="name">
                                {{ data_get($firmanteDirector, 'nombre', 'SIN CONFIGURAR') }}
                            </div>

                            <div class="role">
                                {{ data_get($firmanteDirector, 'cargo', 'DIRECTOR(A) DEL PLANTEL') }}
                            </div>
                        </div>
                    </div>
                </td>

                {{-- Firma del jefe de departamento --}}
                <td>
                    <div class="signature-box">
                        @if ($incluirFirmas && $selloJefePdf)
                            <img class="signature-seal right" src="{{ $selloJefePdf }}"
                                alt="Sello de registro y certificación">
                        @endif

                        {{--
                            El sello actual del jefe ya incluye una firma.

                            Por eso la firma independiente solo se imprime
                            cuando NO existe un sello cargado.
                        --}}
                        @if ($incluirFirmas && $firmaJefePdf && !$selloJefePdf)
                            <img class="signature-hand" src="{{ $firmaJefePdf }}"
                                alt="Firma del jefe de registro y certificación">
                        @endif

                        @if (!$incluirFirmas || (!$firmaJefePdf && !$selloJefePdf))
                            <div class="signature-physical-line"></div>
                        @endif

                        <div class="signature-data">
                            <div class="name">
                                {{ data_get($firmanteJefe, 'nombre', 'SIN CONFIGURAR') }}
                            </div>

                            <div class="role">
                                {{ data_get($firmanteJefe, 'cargo', 'JEFE DEL DEPARTAMENTO DE REGISTRO Y CERTIFICACIÓN') }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">
            Página 2 de 2 ·
            {{ mb_strtoupper($institucional['localidad_expedicion']) }},
            {{ $fecha_documento_texto ?? '' }}
        </div>
    </div>
</body>

</html>
