<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Historial académico</title>
    <style>
        @page {
            margin: 11mm 10mm 10mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #20242a;
            font-size: 10px;
        }

        .page {
            position: relative;
            min-height: 250mm;
        }

        .page-break {
            page-break-after: always;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }

        .header td {
            border: 0;
            vertical-align: middle;
        }

        .logo-seg {
            width: 62px;
            max-height: 65px;
            object-fit: contain;
        }

        .logo-plantel {
            width: 100px;
            max-height: 48px;
            object-fit: contain;
        }

        .school {
            text-align: center;
            font-size: 13px;
            font-weight: 700;
        }

        .document-title {
            margin-top: 7px;
            text-align: center;
            color: #8f2929;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 4px;
        }

        .student {
            width: 100%;
            border-collapse: collapse;

        }

        .student th,
        .student td {
            border: .65px solid #303030;
            padding: 2px 3px;
            text-align: center;
            line-height: 1.2;
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
            margin-top: 7px;
        }

        .student-layout>tbody>tr>td {
            border: 0;
            padding: 0;
            vertical-align: top;
        }

        .student-panel {
            width: 100%;
        }

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

        .semester {
            margin-top: 6px;
            page-break-inside: avoid;
        }

        .semester-head {
            display: table;
            width: 100%;
            color: #9d4a4a;
            font-size: 10px;
            margin-bottom: 1px;
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
            line-height: 1.05;
            padding: 1.3px 2px;
            border-bottom: .55px solid #d2b8b8;
        }

        .subjects td {
            font-size: 10px;
            line-height: 1.05;
            padding: 1.35px 2px;
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
            font-size: 10px;
            color: #777;
        }

        .extra-label {
            margin-top: 2px;
            color: #7a5a00;
            font-size: 10px;
            font-weight: 700;
        }

        .summary-grid {
            width: 100%;
            border-collapse: separate;
            border-spacing: 5px;
            margin-top: 8px;
        }

        .summary-grid td {
            width: 20%;
            border: .6px solid #d8dee8;
            border-radius: 5px;
            padding: 5px;
            background: #f8fafc;
            text-align: center;
        }

        .summary-grid small {
            display: block;
            color: #667085;
            font-size: 10px;
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
            margin-top: 8px;
            text-align: center;
            font-size: 10px;
            font-weight: 700;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 22px;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 18px;
        }

        .signature-box {
            position: relative;
            height: 102px;
            overflow: hidden;
        }

        .signature-seal {
            position: absolute;
            z-index: 1;
            object-fit: contain;
        }

        .signature-seal.left {
            width: 92px;
            height: 92px;
            top: 0;
            left: 50%;
            margin-left: -46px;
        }

        .signature-seal.right {
            width: 142px;
            height: 88px;
            top: 1px;
            left: 50%;
            margin-left: -71px;
        }

        .signature-hand {
            position: absolute;
            z-index: 2;
            width: 122px;
            height: 43px;
            object-fit: contain;
            left: 50%;
            top: 24px;
            margin-left: -61px;
        }

        .signature-physical-line {
            position: absolute;
            left: 18%;
            right: 18%;
            bottom: 28px;
            border-top: .6px solid #303030;
        }

        .signature-data {
            position: absolute;
            z-index: 3;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
        }

        .signatures .name {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .signatures .role {
            font-size: 10px;
            line-height: 1.2;
        }

        .footer {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
            color: #8a8f98;
            font-size: 10px;
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
        $incluirFirmas = (bool) ($incluir_firmas_digitales ?? true);
    @endphp

    <div class="page page-break">
        <table class="header">
            <tr>
                <td style="width:20%;"><img class="logo-seg" src="{{ public_path('imagenes/seg.png') }}"></td>
                <td style="width:60%;" class="school">{{ mb_strtoupper($institucional['plantel']) }}</td>
                <td style="width:20%; text-align:right;"><img class="logo-plantel"
                        src="{{ public_path('imagenes/plantel.jpg') }}"></td>
            </tr>
        </table>
        <div class="document-title">HISTORIAL ACADÉMICO</div>

        <table class="student-layout">
            <tr>
                <td>
                    <table class="student student-panel">
                        <tr>
                            <th colspan="4">Nombre del plantel</th>
                            <th>Clave</th>
                        </tr>
                        <tr>
                            <td colspan="4">{{ mb_strtoupper($institucional['plantel']) }}</td>
                            <td>{{ $institucional['cct'] }}</td>
                        </tr>
                        <tr>
                            <th colspan="5">Domicilio</th>
                        </tr>
                        <tr>
                            <td colspan="5">{{ mb_strtoupper($institucional['direccion']) }}</td>
                        </tr>
                        <tr>
                            <th>Primer apellido</th>
                            <th>Segundo apellido</th>
                            <th>Nombre(s)</th>
                            <th>CURP</th>
                            <th>Matrícula</th>
                        </tr>
                        <tr>
                            <td>{{ mb_strtoupper($alumno->apellido_paterno) }}</td>
                            <td>{{ mb_strtoupper($alumno->apellido_materno) }}</td>
                            <td>{{ mb_strtoupper($alumno->nombre) }}</td>
                            <td>{{ mb_strtoupper($alumno->curp) }}</td>
                            <td>{{ mb_strtoupper($alumno->matricula) }}</td>
                        </tr>
                        <tr>
                            <th colspan="3">Nivel de estudios</th>
                            <th>Modalidad</th>
                            <th>Núm. de incorporación</th>
                        </tr>
                        <tr>
                            <td colspan="3">BACHILLERATO GENERAL</td>
                            <td>{{ mb_strtoupper($institucional['modalidad']) }}</td>
                            <td>{{ $institucional['numero_acuerdo'] }}</td>
                        </tr>
                    </table>
                </td>
                @if ($mostrar_foto)
                    <td class="photo-panel">
                        @if ($foto_data_uri)
                            <img src="{{ $foto_data_uri }}" class="photo">
                        @else
                            <div class="photo-empty"><span>SIN FOTO</span></div>
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

        <div class="footer">Página 1 de 2 · Documento generado el
            {{ $fecha_documento_corta ?? now()->format('d/m/Y') }}</div>
    </div>

    <div class="page">
        <table class="header">
            <tr>
                <td style="width:20%;"><img class="logo-seg" src="{{ $institucional['logo_seg'] }}"></td>
                <td style="width:60%;" class="school">{{ mb_strtoupper($institucional['plantel']) }}</td>
                <td style="width:20%; text-align:right;"><img class="logo-plantel"
                        src="{{ $institucional['logo_plantel'] }}"></td>
            </tr>
        </table>
        <div class="document-title">HISTORIAL ACADÉMICO</div>
        <div style="margin-top:5px;text-align:center;font-size:7px;font-weight:700;">{{ $nombreCompleto }} ·
            {{ $alumno->matricula }}</div>

        @foreach ($semestres_pagina_2 as $semestre)
            @include('pdf.media-superior.partials.historial-semestre', [
                'semestre' => $semestre,
                'institucional' => $institucional,
            ])
        @endforeach

        <table class="summary-grid">
            <tr>
                <td><small>Promedio general</small><strong>{{ $promedio_general }}</strong></td>
                <td><small>Materias
                        evaluadas</small><strong>{{ data_get($resumen_historial, 'materias_evaluadas', 0) }}</strong>
                </td>
                <td><small>Acreditadas</small><strong>{{ data_get($resumen_historial, 'materias_acreditadas', 0) }}</strong>
                </td>
                <td><small>No
                        acreditadas</small><strong>{{ data_get($resumen_historial, 'materias_no_acreditadas', 0) }}</strong>
                </td>
                <td><small>Situación</small><strong
                        style="font-size:8px;">{{ data_get($resumen_historial, 'situacion', 'SIN REGISTROS') }}</strong>
                </td>
            </tr>
        </table>

        <div class="average">PROMEDIO GENERAL: {{ $promedio_general }}</div>

        <table class="signatures">
            <tr>
                <td>
                    <div class="signature-box">
                        @if ($incluirFirmas && is_file((string) data_get($firmanteDirector, 'sello_ruta')))
                            <img class="signature-seal left" src="{{ data_get($firmanteDirector, 'sello_ruta') }}"
                                alt="Sello del plantel">
                        @endif
                        @if ($incluirFirmas && is_file((string) data_get($firmanteDirector, 'firma_ruta')))
                            <img class="signature-hand" src="{{ data_get($firmanteDirector, 'firma_ruta') }}"
                                alt="Firma del director del plantel">
                        @endif
                        @if (
                            !$incluirFirmas ||
                                (!is_file((string) data_get($firmanteDirector, 'firma_ruta')) &&
                                    !is_file((string) data_get($firmanteDirector, 'sello_ruta'))))
                            <div class="signature-physical-line"></div>
                        @endif
                        <div class="signature-data">
                            <div class="name">{{ data_get($firmanteDirector, 'nombre', 'SIN CONFIGURAR') }}</div>
                            <div class="role">{{ data_get($firmanteDirector, 'cargo', 'DIRECTOR(A) DEL PLANTEL') }}
                            </div>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="signature-box">
                        @if ($incluirFirmas && is_file((string) data_get($firmanteJefe, 'sello_ruta')))
                            <img class="signature-seal right" src="{{ data_get($firmanteJefe, 'sello_ruta') }}"
                                alt="Sello de registro y certificación">
                        @endif
                        @if ($incluirFirmas && is_file((string) data_get($firmanteJefe, 'firma_ruta')))
                            <img class="signature-hand" src="{{ data_get($firmanteJefe, 'firma_ruta') }}"
                                alt="Firma del jefe de registro y certificación">
                        @endif
                        @if (
                            !$incluirFirmas ||
                                (!is_file((string) data_get($firmanteJefe, 'firma_ruta')) &&
                                    !is_file((string) data_get($firmanteJefe, 'sello_ruta'))))
                            <div class="signature-physical-line"></div>
                        @endif
                        <div class="signature-data">
                            <div class="name">{{ data_get($firmanteJefe, 'nombre', 'SIN CONFIGURAR') }}</div>
                            <div class="role">
                                {{ data_get($firmanteJefe, 'cargo', 'JEFE DEL DEPARTAMENTO DE REGISTRO Y CERTIFICACIÓN') }}
                            </div>
                        </div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="footer">Página 2 de 2 · {{ mb_strtoupper($institucional['localidad_expedicion']) }},
            {{ $fecha_documento_texto ?? '' }}</div>
    </div>
</body>

</html>
