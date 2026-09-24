<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Carta compromiso</title>
    <style>
        @page {
            margin: 18mm 17mm 13mm 17mm;
            size: letter portrait;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: Arial, Helvetica, sans-serif;
            color: #111;
            font-size: 11.2pt;
            line-height: 1.43;
        }

        .page {
            min-height: 245mm;
            position: relative;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            vertical-align: top;
        }

        .header-logo {
            width: 50%;
        }

        .header-logo img.seg {
            width: 95%;
            max-height: 30mm;
            object-fit: contain;
            object-position: left top;
        }

        .header-logo img.cum {
            width: auto;
            max-width: 78%;
            max-height: 24mm;
        }

        .header-text {
            width: 50%;
            text-align: right;
            font-family: 'Times New Roman', serif;
            font-size: 9.2pt;
            line-height: 1.08;
            padding-top: 2mm;
        }

        .subject {
            margin-top: 7mm;
            text-align: right;
            font-weight: bold;
            font-size: 10.5pt;
        }

        .date {
            margin-top: 6mm;
            text-align: right;
            font-size: 12pt;
        }

        .legend {
            margin-top: 5mm;
            text-align: right;
            font-weight: bold;
            font-style: italic;
            font-size: 9.5pt;
        }

        .recipient {
            margin-top: 7mm;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.3;
        }

        .body {
            margin-top: 17mm;
            text-align: justify;
        }

        .body p {
            margin: 0 0 3mm;
        }

        .body p:last-child {
            margin-bottom: 0;
        }

        .body ul,
        .body ol {
            margin: 0 0 3mm 5mm;
            padding-left: 5mm;
        }

        .body blockquote {
            margin: 0 0 3mm 4mm;
            padding-left: 3mm;
            border-left: 1px solid #777;
        }

        .closing {
            margin-top: 5mm;
        }

        .attentive {
            margin-top: 7mm;
            text-align: center;
        }

        .attentive .title {
            font-weight: bold;
        }

        .signature-main {
            width: 54mm;
            margin: 12mm auto 0 auto;
            border-top: 1px solid #111;
            padding-top: 1.5mm;
            text-align: center;
            font-size: 9.7pt;
        }

        .signatures {
            width: 100%;
            margin-top: 8mm;
            border-collapse: separate;
            border-spacing: 15mm 0;
        }

        .signatures td {
            width: 50%;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-label {
            font-weight: bold;
            margin-bottom: 10mm;
        }

        .signature-line {
            border-top: 1px solid #111;
            padding-top: 1.5mm;
            font-size: 9.2pt;
            min-height: 8mm;
        }

        .footer-strip {
            position: absolute;
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
        }

        .footer-strip img {
            width: 100%;
            height: 7mm;
            object-fit: fill;
        }

        .cct {
            margin-top: 1.5mm;
            text-align: right;
            font-size: 7.5pt;
            color: #555;
        }
    </style>
</head>

<body>
    @foreach ($cartas as $carta)
        @php
            $fecha = $carta->fecha_expedicion
                ? mb_strtoupper($carta->fecha_expedicion->locale('es')->translatedFormat('j \d\e F \d\e Y'))
                : '';
            $nivelSlug = $carta->alumno?->nivel?->slug ?? '';
            $calidad = trim((string) $carta->suscriptor_calidad) ?: 'Responsable del alumno';
            $nombreFirma = trim((string) $carta->suscriptor_nombre);
            $cct = $carta->alumno?->nivel?->cct;
            $contenidoSeguro = app(\App\Services\HtmlSanitizerService::class)->sanitize(
                (string) $carta->contenido_cuerpo,
            );
        @endphp
        <div class="page">
            <table class="header">
                <tr>
                    <td class="header-logo">
                        @if ($carta->membrete_tipo === 'cum')
                            <img class="cum" src="{{ public_path('imagenes/logo-oficial-cum.png') }}" alt="CUM">
                        @else
                            <img class="seg" src="{{ public_path('imagenes/logo-seg.png') }}" alt="SEG">
                        @endif
                    </td>
                    <td class="header-text">
                        <div>{{ mb_strtoupper((string) $carta->encabezado_linea_1) }}</div>
                        <div>{{ mb_strtoupper((string) $carta->encabezado_linea_2) }}</div>
                    </td>
                </tr>
            </table>

            <div class="subject">ASUNTO: {{ mb_strtoupper((string) $carta->asunto) }}</div>
            <div class="date">{{ mb_strtoupper((string) $carta->lugar) }}, A {{ $fecha }}.</div>
            @if (filled($carta->leyenda_anual))
                <div class="legend">“{{ mb_strtoupper((string) $carta->leyenda_anual) }}”</div>
            @endif

            <div class="recipient">
                <div>{{ $carta->destinatario_nombre }}</div>
                <div>{{ $carta->destinatario_cargo }}</div>
                <div>{{ $carta->destinatario_institucion }}</div>
            </div>

            <div class="body">
                {!! $contenidoSeguro !!}
            </div>

            <div class="closing">Sin otro particular reciba un cordial saludo.</div>

            <div class="attentive">
                <div class="title">ATENTAMENTE</div>
                <div>{{ ucfirst($calidad) }}</div>
                <div class="signature-main">{{ $nombreFirma }}</div>
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="signature-label">Docente</div>
                        <div class="signature-line">{{ $carta->docente_nombre }}</div>
                    </td>
                    <td>
                        <div class="signature-label">Director(a)</div>
                        <div class="signature-line">{{ $carta->directora_nombre }}</div>
                    </td>
                </tr>
            </table>

            @if ($cct)
                <div class="cct">C.C.T. {{ $cct }}</div>
            @endif

            <div class="footer-strip">
                <img src="{{ public_path('imagenes/tira.jpg') }}" alt="">
            </div>
        </div>
    @endforeach
</body>

</html>
