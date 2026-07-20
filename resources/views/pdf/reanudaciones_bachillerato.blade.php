<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reanudaciones de Bachillerato</title>
    <style>
        @page { size: letter portrait; margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, sans-serif; color: #172033; }
        .pagina { position: relative; width: 100%; height: 100%; min-height: 1056px; page-break-after: always; overflow: hidden; }
        .pagina:last-child { page-break-after: auto; }
        .barra-superior { height: 12px; background: #006492; }
        .barra-verde { height: 5px; background: #88AC2E; }
        .contenido { padding: 34px 68px 42px; }
        .encabezado { border-bottom: 2px solid #006492; padding-bottom: 14px; margin-bottom: 24px; }
        .institucion { font-size: 16px; font-weight: bold; color: #006492; text-transform: uppercase; }
        .nivel { margin-top: 4px; font-size: 11px; color: #52627a; text-transform: uppercase; }
        .asunto { text-align: right; font-size: 11px; line-height: 1.6; text-transform: uppercase; margin-bottom: 26px; }
        .destinatario { font-size: 12px; line-height: 1.45; text-transform: uppercase; margin-bottom: 26px; }
        .cuerpo { font-family: DejaVu Serif, serif; font-size: 13px; line-height: 1.75; text-align: justify; }
        .firma { margin-top: 54px; text-align: center; font-size: 12px; text-transform: uppercase; }
        .firma .linea { width: 320px; margin: 56px auto 8px; border-top: 1px solid #1f2937; }
        .cargo { margin-top: 5px; font-size: 10px; color: #44536a; }
        .ccp { position: absolute; left: 68px; right: 68px; bottom: 38px; font-size: 8px; line-height: 1.35; color: #39475d; }
        .marca { position: absolute; right: -42px; bottom: 100px; width: 170px; height: 170px; border: 20px solid rgba(0,100,146,.05); border-radius: 50%; }
        .pie { position: absolute; bottom: 0; left: 0; right: 0; height: 14px; background: linear-gradient(90deg, #006492 0 62%, #88AC2E 62%); }
    </style>
</head>
<body>
@foreach ($documentos as $documento)
    @php
        $snapshot = $documento['snapshot'] ?? [];
        $escuela = data_get($snapshot, 'escuela.nombre', 'CENTRO UNIVERSITARIO MOCTEZUMA');
        $cct = data_get($snapshot, 'nivel.cct');
        $destinatario = $documento['destinatario_nombre'] ?: data_get($snapshot, 'autoridades.destinatario_nombre', 'AUTORIDAD EDUCATIVA');
        $cargoDestinatario = $documento['destinatario_cargo'] ?: data_get($snapshot, 'autoridades.destinatario_cargo', 'PRESENTE');
        $fecha = \Carbon\Carbon::parse($documento['fecha_documento'])->locale('es')->isoFormat('D [de] MMMM [de] YYYY');
        $periodo = match($documento['tipo']) {
            'receso' => 'el receso escolar',
            'invierno' => 'las vacaciones de invierno',
            'primavera' => 'el periodo vacacional de primavera',
            default => 'el periodo correspondiente',
        };
    @endphp
    <section class="pagina">
        <div class="barra-superior"></div>
        <div class="barra-verde"></div>
        <div class="marca"></div>
        <div class="contenido">
            <header class="encabezado">
                <div class="institucion">{{ $escuela }}</div>
                <div class="nivel">Bachillerato{{ $cct ? ' · C.C.T. ' . $cct : '' }} · Ciclo escolar {{ data_get($snapshot, 'ciclo.nombre') }}</div>
            </header>

            <div class="asunto">
                <b>ASUNTO:</b> REANUDACIÓN DE LABORES<br>
                CIUDAD ALTAMIRANO, GRO., A {{ mb_strtoupper($fecha) }}.
            </div>

            <div class="destinatario">
                <b>{{ $destinatario }}</b><br>
                {{ $cargoDestinatario }}<br>
                P R E S E N T E
            </div>

            <div class="cuerpo">
                <p>
                    Por este conducto, me permito informar que con fecha arriba señalada me presenté a reanudar mis labores,
                    después de haber disfrutado <b><u>{{ mb_strtoupper($periodo) }}</u></b>, incorporándome a las actividades
                    correspondientes del nivel Bachillerato del {{ $escuela }}.
                </p>
                <p>Sin otro particular, reciba un cordial saludo.</p>
            </div>

            <div class="firma">
                <b>A T E N T A M E N T E</b>
                <div class="linea"></div>
                <b>{{ mb_strtoupper($documento['persona_nombre']) }}</b>
                <div class="cargo">{{ mb_strtoupper(implode(' / ', $documento['cargos'] ?? [])) }}</div>
            </div>

            @if (filled($documento['copias']))
                <div class="ccp">
                    @foreach (preg_split('/\r\n|\r|\n/', $documento['copias']) ?: [] as $linea)
                        @if (trim($linea) !== '')
                            <div>{{ $linea }}</div>
                        @endif
                    @endforeach
                </div>
            @endif
        </div>
        <div class="pie"></div>
    </section>
@endforeach
</body>
</html>
