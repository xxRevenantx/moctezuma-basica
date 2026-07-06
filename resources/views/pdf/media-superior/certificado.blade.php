<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Certificación de estudios - {{ $alumno->matricula }}</title>
    <style>
        @page {
            margin: 8mm 12mm 8mm 12mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 7px;
            color: #080808;
        }

        .first-page {
            position: relative;
            height: 985px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border: 0;
            vertical-align: top;
        }

        .logo-seg {
            width: 128px;
            max-height: 47px;
            object-fit: contain;
        }

        .logo-cum {
            width: 155px;
            max-height: 48px;
            object-fit: contain;
        }

        .document-title {
            margin: 12px 0 0;
            text-align: center;
            font-size: 15px;
            font-weight: normal;
            letter-spacing: .1px;
        }

        .plantel-title {
            margin: 13px 0 0;
            text-align: center;
            font-size: 11px;
            font-weight: normal;
        }

        .certificate-text {
            width: 70%;
            margin-top: 20px;
            margin-left: 250px;
            margin-bottom: 15px;
            font-size: 11px;
            line-height: 1.45;
            text-align: left;
        }

        .certificate-text strong {
            font-weight: bold;
        }

        .semester-columns {
            border: .75px solid #000;
        }

        .semester-columns>tbody>tr>td {
            padding: 0;
            vertical-align: top;
            width: 50%;
        }

        .semester-columns>tbody>tr>td:first-child {
            border-right: .75px solid #000;
        }

        .side-header {
            height: 22px;
        }

        .side-header th {
            border-bottom: .75px solid #000;
            border-right: .75px solid #000;
            padding: 2px 1px;
            text-align: center;
            vertical-align: middle;
            font-size: 10px;
            line-height: 1.05;
        }

        .side-header th:last-child {
            border-right: 0;
        }

        .subject-col {
            width: 64%;
        }

        .grade-col {
            width: 15%;
        }

        .obs-col {
            width: 21%;
        }

        .semester-box {
            position: relative;
            height: 91px;
            overflow: hidden;
            border-bottom: .75px solid #000;
        }

        .semester-box:last-child {
            border-bottom: 0;
        }

        .semester-title {
            height: 20px;
            padding: 3px 2px 1px;
            text-align: center;
            font-size: 10px;
            font-weight: bold;
            line-height: 1.13;
        }

        .semester-table {
            /* table-layout: fixed; */
        }

        .semester-table td {
            padding: .65px 3px;
            border-right: .75px solid #000;
            vertical-align: top;
            line-height: 1.08;
        }

        .semester-table td:last-child {
            border-right: 0;
        }

        .semester-table .subject {
            width: 64%;
            text-align: left;
        }

        .semester-table .grade {
            width: 15%;
            text-align: center;
        }

        .semester-table .obs {
            width: 21%;
            text-align: center;
        }

        .semester-table.compact td {
            font-size: 10px;
            padding-top: .35px;
            padding-bottom: .35px;
        }

        .semester-table.very-compact td {
            font-size: 10px;
            padding-top: .35px;
            padding-bottom: .35px;
        }

        .empty-semester .semester-title {
            color: #333;
        }

        .empty-semester .diagonal {
            position: absolute;
            left: -12%;
            top: 49%;
            width: 126%;
            height: 0;
            border-top: .75px solid #000;
            transform: rotate(18deg);
            transform-origin: center center;
        }

        .summary {
            margin-top: 18px;
            font-size: 10px;
            line-height: 1.55;
            text-align: justify;
        }

        .expedition {
            margin-top: 9px;
            font-size: 10px;
            line-height: 1.45;
            text-align: left;
        }

        .director-signature {
            position: absolute;
            left: 50%;
            bottom: 4px;
            width: 245px;
            margin-left: -122px;
            text-align: center;
            font-size: 10px;
        }

        .director-line {
            width: 140px;
            margin: 0 auto 4px;
            border-top: .75px solid #000;
        }

        .director-name {
            font-weight: normal;
        }

        .director-role {
            margin-top: 4px;
        }

        .page-break {
            page-break-before: always;
        }

        .second-page {
            position: relative;
            height: 985px;
        }

        .review-boxes {
            margin-top: 0;
            /* table-layout: fixed; */
        }

        .review-boxes td {
            width: 50%;
            vertical-align: top;
        }

        .review-boxes td:first-child {
            padding-right: 47px;
        }

        .review-boxes td:last-child {
            padding-left: 47px;
        }

        .review-box {
            position: relative;
            height: 132px;
            border: .75px solid #000;
            padding: 5px 7px;
            font-size: 10px;
            text-align: center;
        }

        .review-box-title {
            line-height: 1.15;
        }

        .review-box-name {
            position: absolute;
            left: 5px;
            right: 5px;
            bottom: 22px;
            font-size: 10px;
            text-align: center;
        }

        .review-box-date {
            position: absolute;
            left: 7px;
            bottom: 5px;
            text-align: left;
        }

        .folio-area {
            position: absolute;
            left: 7px;
            bottom: 30px;
            width: 104px;
        }

        .folio-box {
            width: 104px;
            min-height: 42px;
            border: .75px solid #000;
            padding: 5px 4px;
            text-align: center;
            font-size: 10px;
        }

        .folio-number {
            margin-top: 3px;
            font-size: 10px;
            font-weight: bold;
        }

        .legal-note {
            position: absolute;
            left: 7px;
            right: 7px;
            bottom: 0;
            font-size: 10px;
            line-height: 1.2;
            text-align: left;
        }
    </style>
</head>

<body>
    <div class="first-page">
        <table class="header">
            <tr>
                <td style="width:35%;">
                    @if (is_file($institucional['logo_seg'] ?? ''))
                        <img class="logo-seg" src="{{ $institucional['logo_seg'] }}"
                            alt="Secretaría de Educación Guerrero">
                    @endif
                </td>
                <td style="width:30%;"></td>
                <td style="width:35%;text-align:right;">
                    @if (is_file($institucional['logo_certificado'] ?? ''))
                        <img class="logo-cum" src="{{ $institucional['logo_certificado'] }}"
                            alt="Centro Universitario Moctezuma">
                    @endif
                </td>
            </tr>
        </table>

        <h1 class="document-title">CERTIFICACIÓN DE ESTUDIOS</h1>
        <h2 class="plantel-title">{{ mb_strtoupper($institucional['plantel']) }}</h2>

        <div class="certificate-text">{!! nl2br(e($texto_certificado_renderizado)) !!}</div>

        <table class="semester-columns">
            <tr>
                @foreach ([$semestres_certificado_izquierda, $semestres_certificado_derecha] as $lado)
                    <td>
                        <table class="side-header">
                            <tr>
                                <th class="subject-col">ASIGNATURAS</th>
                                <th class="grade-col">CALIF.<br>FINAL</th>
                                <th class="obs-col">OBSERVACIONES</th>
                            </tr>
                        </table>

                        @foreach ($lado as $semestre)
                            @php
                                $cantidadMaterias = collect($semestre['oficiales'] ?? [])->count();
                                $claseCompacta =
                                    $cantidadMaterias > 10 ? 'very-compact' : ($cantidadMaterias > 8 ? 'compact' : '');
                                $ordinales = [
                                    1 => 'PRIMER',
                                    2 => 'SEGUNDO',
                                    3 => 'TERCER',
                                    4 => 'CUARTO',
                                    5 => 'QUINTO',
                                    6 => 'SEXTO',
                                ];
                            @endphp
                            <div class="semester-box {{ $semestre['incluido'] ? '' : 'empty-semester' }}">
                                <div class="semester-title">
                                    {{ $ordinales[$semestre['numero']] ?? $semestre['numero'] . '°' }} SEMESTRE
                                    @if ($semestre['incluido'])
                                        <br>CICLO ESCOLAR {{ $semestre['ciclo']?->nombre ?: '—' }}
                                    @endif
                                </div>

                                @if ($semestre['incluido'])
                                    <table class="semester-table {{ $claseCompacta }}">
                                        @foreach ($semestre['oficiales'] as $materia)
                                            <tr>
                                                <td class="subject">{{ mb_strtoupper($materia['nombre']) }}</td>
                                                <td class="grade">{{ $materia['valor'] }}</td>
                                                <td class="obs"></td>
                                            </tr>
                                        @endforeach
                                    </table>
                                @else
                                    <div class="diagonal"></div>
                                @endif
                            </div>
                        @endforeach
                    </td>
                @endforeach
            </tr>
        </table>

        <div class="summary">{{ $resumen_certificado }}</div>
        <div class="expedition">
            EXPEDIDO EN {{ mb_strtoupper($institucional['localidad_expedicion']) }}, A LOS
            {{ $fecha_documento_texto_letra }}.
        </div>

        <div class="director-signature">
            <div class="director-line"></div>
            <div class="director-name">{{ $institucional['firmantes']['director']['nombre'] }}</div>
            <div class="director-role">{{ $institucional['firmantes']['director']['cargo'] }}</div>
        </div>

    </div>

    <div class="page-break"></div>

    <div class="second-page">
        <table class="review-boxes">
            <tr>
                <td>
                    <div class="review-box">
                        <div class="review-box-title">REVISADO Y CONFRONTADO POR:</div>
                        <div class="review-box-name">{{ mb_strtoupper($certificado_revisado_por ?? '') }}</div>
                        <div class="review-box-date">FECHA:</div>
                    </div>
                </td>
                <td>
                    <div class="review-box">
                        <div class="review-box-title">JEFE DEL DEPARTAMENTO DE REGISTRO<br>Y CERTIFICACIÓN</div>
                        <div class="review-box-name">{{ mb_strtoupper($certificado_jefe_registro_por ?? '') }}</div>
                    </div>
                </td>
            </tr>
        </table>

        <div class="folio-area">
            <div class="folio-box">
                <div>FOLIO</div>
                <div class="folio-number">{{ $folio }}</div>
            </div>
        </div>

        <div class="legal-note">{{ mb_strtoupper($institucional['leyenda_certificado']) }}</div>
    </div>
</body>

</html>
