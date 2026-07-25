<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de horario</title>
    <style>
        @page {
            margin: 18px 22px 22px 22px;
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
            font-family: 'ARIAL', sans-serif;
            color: #0f172a;
            font-size: 10px;
        }

        .page {
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .header {
            width: 100%;
            margin-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            vertical-align: middle;
        }

        .logo-cell {
            width: 85px;
            text-align: center;
        }

        .logo {
            width: 62px;
            max-height: 62px;
        }

        .title-block {
            text-align: center;
            padding: 0 8px;
        }

        .school-name {
            font-size: 16px;

            color: #006492;
            line-height: 1.2;
        }

        .doc-title {
            margin-top: 2px;
            font-size: 14px;
            color: #88AC2E;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .subtitle {
            margin-top: 2px;
            font-size: 9px;
            color: #334155;
        }

        .meta {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 8px;
            margin-bottom: 10px;
        }

        .meta td {
            border: 1px solid #cbd5e1;
            padding: 6px 7px;
            vertical-align: top;
        }

        .meta-label {
            display: block;
            font-size: 8px;
            text-transform: uppercase;
            color: #475569;

            margin-bottom: 2px;
        }

        .meta-value {
            font-size: 10px;

            color: #0f172a;
        }

        .schedule {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 2px;
        }

        .schedule th,
        .schedule td {
            border: 1px solid #94a3b8;
        }

        .schedule thead th {
            background: #006492;
            color: #ffffff;
            font-size: 10px;
            text-align: center;
            padding: 7px 4px;
        }

        .schedule .th-hour {
            width: 92px;
        }

        .hour-cell {
            background: #e0f2fe;
            text-align: center;

            font-size: 9px;
            color: #0f172a;
            padding: 6px 5px;
        }

        .blank-cell {
            height: 52px;
            position: relative;
            padding: 0;
        }

        .recess-row td {
            background: #fef3c7;
        }

        .recess-cell {
            text-align: center;
            font-weight: 800;
            letter-spacing: 0.5px;
            color: #92400e;
        }

        .line-1,
        .line-2 {
            border-bottom: 1px solid #cbd5e1;
            margin: 0 8px;
        }

        .line-1 {
            margin-top: 16px;
        }

        .line-2 {
            margin-top: 13px;
        }

        .field-label {
            font-size: 7px;
            color: #64748b;

            text-transform: uppercase;
            margin-left: 8px;
        }

        .field-label.profesor {
            margin-top: 9px;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 8px;
            color: #64748b;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 14px;
        }

        .signatures td {
            width: 33.33%;
            text-align: center;
            vertical-align: bottom;
            padding: 0 10px;
        }

        .sign-line {
            border-top: 1px solid #334155;
            height: 28px;
            margin-bottom: 4px;
        }

        .sign-role {
            font-size: 9px;

            color: #334155;
            text-transform: uppercase;
        }

        .sign-name {
            font-size: 8px;
            color: #64748b;
        }

        .ink-saver {
            font-size: 8px;
            color: #475569;
            margin-top: 5px;
        }
    </style>
</head>

<body>
    @foreach ($paginas as $pagina)
        @php
            $grupo = $pagina['grupo'];
            $recesoHoraIds = collect($pagina['receso_hora_ids'] ?? [])->map(fn($id) => (int) $id);
        @endphp
        <div class="page">
            <div class="header">
                <table class="header-table">
                    <tr>
                        <td class="logo-cell">
                            @if ($logoIzquierdo)
                                <img src="{{ $logoIzquierdo }}" class="logo" alt="Logo izquierdo">
                            @endif
                        </td>
                        <td class="title-block">
                            <div class="school-name">{{ $escuela->nombre }}</div>
                            <div class="doc-title">Formato de horario de clases</div>
                            <div class="subtitle">Ciclo escolar {{ $cicloEscolar->inicio_anio }} -
                                {{ $cicloEscolar->fin_anio }}</div>
                        </td>
                        <td class="logo-cell">
                            @if ($logoDerecho)
                                <img src="{{ $logoDerecho }}" class="logo" alt="Logo derecho">
                            @endif
                        </td>
                    </tr>
                </table>
            </div>

            <table class="meta">
                <tr>
                    <td>
                        <span class="meta-label">Nivel</span>
                        <span class="meta-value">{{ $nivel->nombre }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Generación</span>
                        <span class="meta-value">{{ $pagina['generacion'] ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Grado / Grupo</span>
                        <span class="meta-value">{{ $pagina['etiqueta_grupo'] ?: '—' }}</span>
                    </td>
                    <td>
                        <span class="meta-label">Docente titular</span>
                        <span class="meta-value">{{ $pagina['titular'] ?: '______________________________' }}</span>
                    </td>
                </tr>
            </table>

            <table class="schedule">
                <thead>
                    <tr>
                        <th class="th-hour">Hora</th>
                        @foreach ($dias as $dia)
                            <th>{{ $dia->dia }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($horas as $hora)
                        @php
                            $esReceso = $recesoHoraIds->contains((int) $hora->id);
                        @endphp
                        <tr class="{{ $esReceso ? 'recess-row' : '' }}">
                            <td class="hour-cell">
                                {{ $hora->hora_inicio }}<br>{{ $hora->hora_fin }}
                            </td>

                            @foreach ($dias as $dia)
                                @if ($esReceso)
                                    <td class="blank-cell recess-cell">RECESO</td>
                                @else
                                    <td class="blank-cell">
                                        @if ($estiloCelda === 'lineas')
                                            <div class="line-1"></div>
                                            <div class="line-2"></div>
                                        @elseif ($estiloCelda === 'campos')
                                            <div class="field-label">Materia</div>
                                            <div class="line-1"></div>
                                            <div class="field-label profesor">Profesor</div>
                                            <div class="line-2"></div>
                                        @endif
                                    </td>
                                @endif
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="footer-note">
                Formato listo para impresión y llenado manual. Días en columnas y horas en filas.
            </div>

            <table class="signatures">
                <tr>
                    <td>
                        <div class="sign-line"></div>
                        <div class="sign-role">Elaboró</div>
                        <div class="sign-name">Nombre y firma</div>
                    </td>
                    <td>
                        <div class="sign-line"></div>
                        <div class="sign-role">Revisó</div>
                        <div class="sign-name">Nombre y firma</div>
                    </td>
                    <td>
                        <div class="sign-line"></div>
                        <div class="sign-role">Autorizó</div>
                        <div class="sign-name">Nombre y firma</div>
                    </td>
                </tr>
            </table>
        </div>
    @endforeach
</body>

</html>
