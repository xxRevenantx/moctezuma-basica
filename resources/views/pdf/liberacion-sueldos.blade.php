<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>Liberación de sueldos</title>
    <style>
        @page {
            level: portrait;
            margin: 1.5cm 1.75cm 1.5cm 1.75cm;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: normal;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: bold;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'ARIAL', sans-serif;
            font-size: 14px;
            color: #000;
            line-height: 1.4;
        }

        .pagina {
            page-break-after: always;
            font-size: 10.2pt;
            line-height: 1.08;
        }

        .pagina:last-child {
            page-break-after: auto;
        }

        .encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .encabezado td {
            vertical-align: top;
            padding: 0;
        }

        .logo {
            width: 70mm;
            object-fit: contain;
            object-position: left top;
        }

        .titulos {
            width: 500px;
            text-align: center;
            font-weight: 700;
            font-size: 9.8pt;
            line-height: 1.08;
            padding-top: 0.3mm !important;
            letter-spacing: -0.9px;
            font-family: 'ARIAL', sans-serif;
        }

        .datos {
            width: 68%;
            margin: 5mm 250px 0;
            border-collapse: collapse;
            font-size: 13.5pt;
            font-weight: 700;
        }

        .datos td {
            padding: 0.7mm 1.5mm;
        }

        .datos .etiqueta {
            width: 24mm;
            text-align: left;
        }

        .datos .valor {
            border-bottom: 1.2px solid #111;
            text-align: center;
        }

        .fecha {
            margin-top: 5.4mm;
            text-align: right;
            font-weight: 700;
            font-size: 13pt;
        }

        .subrayado {
            border-bottom: 1.1px solid #111;
            display: inline-block;
        }

        .destinatario {
            margin-top: 8.2mm;
            font-weight: 700;
            font-size: 11.2pt;
        }

        .destinatario .nombre {
            min-width: 95mm;
            padding: 0 2mm 0.3mm;
        }

        .presente {
            margin-top: 0.8mm;
            letter-spacing: 3.2px;
            font-weight: 700;
            font-size: 11.2pt;
        }

        .cuerpo {
            margin-top: 9.6mm;
            font-weight: 700;
            text-align: justify;
            line-height: 15px;
            font-size: 15px;
        }

        .cuerpo p {
            margin: 0 0 4.1mm;
        }

        .linea-clave {
            width: 100%;
            margin: 1.5mm 0 4.6mm;
            padding-bottom: 1mm;
            border-bottom: 1.25px solid #111;
            text-align: center;
            font-size: 11pt;
        }

        .firmas {
            width: 100%;
            border-collapse: collapse;
            margin-top: 100px;
            font-weight: 700;
        }

        .firmas td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding: 0 5mm;
        }

        .firmas .atentamente,
        .firmas .vobo {
            font-size: 11.2pt;
            letter-spacing: 1.8px;
        }

        .firmas .cargo {
            margin-top: 0.6mm;
            font-size: 10.4pt;
        }

        .firmas .espacio {
            height: 11mm;
        }

        .firmas .linea {
            border-bottom: 1px solid #111;
            margin: 0 5mm;
        }

        .firmas .nombre {
            margin-top: 1mm;
            font-size: 9.9pt;
        }

        .franja {
            position: absolute;
            /* left: 8mm; */
            right: 8mm;
            bottom: 4mm;
            width: 200mm;
            height: 5.5mm;
            object-fit: fill;
        }

        .campo-subrayado {
            display: inline-block;
            border-bottom: 1px solid #111;
            padding: 0 0.1mm 0.15mm;
            margin: 0;
            line-height: inherit;
            white-space: nowrap;
            text-decoration: none;

            /* Baja ligeramente el campo para alinearlo con el texto normal */
            vertical-align: -0.7mm;
        }
    </style>
</head>

<body>
    @foreach ($documentos as $d)
        <section class="pagina">
            <table class="encabezado">
                <tr>
                    <td style="width: 40%;"><img class="logo" src="{{ $d['logo_data_uri'] }}" alt="Logos"></td>
                    <td class="titulos">
                        <div>SECRETARÍA DE EDUCACIÓN</div>
                        <div>{{ $d['encabezado_subsecretaria'] ?? 'SUBSECRETARÍA DE EDUCACIÓN BÁSICA' }}</div>
                        <div>{{ $d['encabezado_direccion'] }}</div>
                        <div>DEPARTAMENTO DE ADMINISTRACIÓN Y DESARROLLO DE PERSONAL</div>
                    </td>
                </tr>
            </table>
            <table class="datos">
                <tr>
                    <td class="etiqueta">NIVEL:</td>
                    <td class="valor">{{ $d['nivel_nombre'] }}</td>
                </tr>
                <tr>
                    <td class="etiqueta">ASUNTO:</td>
                    <td class="valor">Constancia de Liberación de sueldos.</td>
                </tr>
            </table>
            <div class="fecha">Cd. Altamirano, Gro., a <span>{{ $d['fecha_documento_texto'] }}</span>.</div>
            <div class="destinatario">C. PROFR. (A): <span
                    class="nombre"><u>{{ mb_strtoupper($d['trabajador_nombre']) }}</u></span></div>
            <div class="presente">P R E S E N T E.</div>
            <div class="cuerpo">
                <p>
                    La que suscribe C.
                    <span class="campo-subrayado">
                        {{ mb_strtoupper($d['director_nombre'] ?: '____________________________') }}
                    </span>
                    en mi carácter de
                    <span class="campo-subrayado">
                        {{ $d['director_cargo'] }}
                    </span>
                    del C.T.
                    <span class="campo-subrayado">
                        {{ mb_strtoupper($d['escuela_nombre']) }}
                    </span>,
                    C.C.T.
                    <span class="campo-subrayado">
                        {{ mb_strtoupper($d['cct']) }}
                    </span>
                    ubicada en
                    <span class="campo-subrayado">
                        Cd. {{ trim($d['localidad'] . ', Municipio de ' . $d['municipio'], ', ') }}
                    </span>,
                    Gro., después de haber cumplido con toda la documentación y actividades
                    relacionadas con el fin de cursos del <span class="campo-subrayado">Ciclo Escolar
                        {{ $d['ciclo_escolar'] }},</span> tengo a bien
                    autorizar el cobro
                    correspondiente a la(s) quincena(s)
                    <span class="campo-subrayado">
                        {{ $d['quincena_inicio'] }} y {{ $d['quincena_fin'] }}
                        del año {{ $d['anio'] }}
                    </span>
                    en la(s) clave(s) presupuestal(es):
                </p>

                <div class="linea-clave">
                    {{ $d['clave_presupuestal'] ?: 'S/C' }}
                </div>

                <p>
                    Lo anterior por haber cumplido con la normatividad establecida del Ciclo Escolar
                    {{ $d['ciclo_escolar'] }} en función al nombramiento que se le ha conferido.
                </p>

                <p>
                    Asimismo, aprovecho la ocasión para hacer de su conocimiento que la reanudación
                    de labores será el día
                    <span class="campo-subrayado">
                        {{ $d['fecha_reanudacion_texto'] ?: '________________' }}
                    </span>,
                    de acuerdo a lo establecido en el calendario escolar emitido por la Secretaría
                    de Educación Pública (SEP) y a las disposiciones generales de inicio de cursos

                    @if (!empty($d['ciclo_escolar']) && preg_match('/^(\d{4})-(\d{4})$/', $d['ciclo_escolar'], $cicloPartes))
                        {{ (int) $cicloPartes[1] + 1 }}-{{ (int) $cicloPartes[2] + 1 }}
                    @endif.
                </p>
            </div>
            <table class="firmas">
                <tr>
                    <td>
                        <div class="atentamente">A T E N T A M E N T E</div>
                        <div class="cargo">{{ mb_strtoupper($d['firma_izquierda_cargo_texto']) }}</div>
                        <div class="espacio"></div>
                        <div class="linea"></div>
                        <div class="nombre">{{ mb_strtoupper($d['firma_izquierda_nombre']) }}</div>
                    </td>
                    <td>
                        <div class="vobo">Vo. &nbsp;&nbsp; Bo.</div>
                        <div class="cargo">{{ mb_strtoupper($d['firma_derecha_cargo_texto']) }}</div>
                        <div class="espacio"></div>
                        <div class="linea"></div>
                        <div class="nombre">{{ mb_strtoupper($d['firma_derecha_nombre']) }}</div>
                    </td>
                </tr>
            </table>

            <img class="franja"
                style="left: {{ max(0, (0 - (float) $d['franja_ancho_mm']) / 2) }}mm; bottom: {{ (float) $d['franja_inferior_mm'] }}mm; width: 100%; height: {{ (float) $d['franja_alto_mm'] }}mm;"
                src="{{ $d['franja_data_uri'] }}" alt="Franja decorativa">
        </section>
    @endforeach
</body>

</html>
