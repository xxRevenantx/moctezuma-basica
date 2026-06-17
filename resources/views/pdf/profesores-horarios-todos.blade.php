<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Todos los horarios docentes</title>

    <style>
        @page {
            margin: 18px 28px 22px 28px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #00152e;
            font-size: 10px;
        }

        .page {
            position: relative;
            width: 100%;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .watermark {
            position: fixed;
            top: 125px;
            left: 150px;
            width: 470px;
            height: 470px;
            opacity: 0.075;
            z-index: -1;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }

        .logo-cell {
            width: 160px;
            text-align: center;
            vertical-align: top;
        }

        .logo-left {
            width: 115px;
            max-height: 78px;
        }

        .logo-right {
            width: 84px;
            max-height: 76px;
        }

        .center-cell {
            text-align: center;
            vertical-align: top;
        }

        .school-title {
            display: inline-block;
            border-top: 2px solid #9ca3af;
            border-bottom: 2px solid #9ca3af;
            padding: 2px 16px;
            color: #5f6f7f;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: .3px;
            text-transform: uppercase;
        }

        .main-title {
            margin-top: 7px;
            font-size: 18px;
            line-height: 1.25;
            font-weight: bold;
            text-transform: uppercase;
            color: #000d21;
        }

        .address {
            margin-top: 5px;
            font-size: 10px;
            line-height: 1.25;
            color: #00152e;
        }

        .profesor-table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0 14px 0;
        }

        .profesor-label {
            width: 185px;
            font-weight: bold;
            text-transform: uppercase;
            color: #00152e;
            padding: 4px 5px;
        }

        .profesor-name {
            border: 1px solid #9cb8c9;
            background: #c7e0f2;
            color: #00152e;
            font-weight: bold;
            text-align: center;
            padding: 4px 6px;
            text-transform: uppercase;
        }

        table.horario {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            margin-top: 4px;
        }

        .horario th {
            border: 1px solid #506273;
            background: #bdd8ea;
            color: #00152e;
            padding: 9px 5px;
            font-size: 12px;
            font-weight: bold;
            text-align: center;
            text-transform: uppercase;
        }

        .horario td {
            border: 1px solid #6b7280;
            height: 43px;
            padding: 5px 4px;
            vertical-align: middle;
            text-align: center;
            color: #00152e;
        }

        .hora-col {
            width: 105px;
            font-size: 12px;
            line-height: 1.45;
            font-weight: normal;
        }

        .materia {
            font-size: 8.5px;
            line-height: 1.08;
            color: #00152e;
        }

        .materia strong {
            font-size: 8.4px;
            font-weight: bold;
        }

        .libre {
            font-size: 11px;
            font-weight: bold;
            color: #00152e;
        }

        .horas-dia td {
            height: auto;
            background: #e8f3fa;
            font-size: 12px;
            font-weight: bold;
            padding: 7px 4px;
        }

        .horas-dia .label {
            background: #d5ebf8;
            text-align: center;
        }

        .section-title {
            margin: 14px 0 8px 0;
            font-size: 11px;
            font-weight: bold;
            color: #00152e;
        }

        table.materias {
            width: 94%;
            margin-left: auto;
            margin-right: auto;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .materias th {
            background: #e8f3fa;
            color: #00152e;
            font-size: 10px;
            font-weight: bold;
            padding: 4px 5px;
            text-align: center;
        }

        .materias td {
            padding: 4px 5px;
            font-size: 9.5px;
            font-weight: bold;
            text-align: center;
            vertical-align: top;
        }

        .materias .num {
            width: 35px;
        }

        .materias .mat {
            width: 245px;
        }

        .materias .nivel {
            width: 150px;
        }

        .materias .grado {
            width: 80px;
        }

        .materias .grupo {
            width: 80px;
        }

        .materias .bloques {
            width: 80px;
        }

        .total-wrapper {
            margin-top: 30px;
            width: 100%;
            text-align: center;
        }

        .total-table {
            margin: 0 auto;
            border-collapse: collapse;
        }

        .total-label {
            padding: 5px 14px;
            font-size: 10px;
            font-weight: bold;
            text-align: right;
        }

        .total-value {
            min-width: 36px;
            border: 1px solid #9fd0bf;
            background: #d9f4ea;
            padding: 5px 12px;
            font-size: 10px;
            font-weight: bold;
            text-align: center;
        }

        .empty {
            margin-top: 60px;
            text-align: center;
            font-size: 14px;
            font-weight: bold;
            color: #64748b;
        }
    </style>
</head>

<body>
    @forelse ($profesoresHorarios as $bloque)
        <div class="page">
            @if ($logoIzquierdo)
                <img src="{{ $logoIzquierdo }}" class="watermark">
            @endif

            <table class="header-table">
                <tr>
                    <td class="logo-cell">
                        @if ($logoIzquierdo)
                            <img src="{{ $logoIzquierdo }}" class="logo-left">
                        @endif
                    </td>

                    <td class="center-cell">
                        <div class="school-title">
                            CENTRO UNIVERSITARIO MOCTEZUMA
                        </div>

                        <div class="main-title">
                            HORARIO DEL DOCENTE | CICLO<br>
                            ESCOLAR {{ $cicloEscolar }}
                        </div>

                        <div class="address">
                            Francisco I. Madero Ote. #800, Col. Esquipulas, Cd.<br>
                            Altamirano, Gro.
                        </div>
                    </td>

                    <td class="logo-cell">
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" class="logo-right">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="profesor-table">
                <tr>
                    <td class="profesor-label">PROFESOR(A)</td>
                    <td class="profesor-name">{{ $bloque['profesorNombre'] }}</td>
                </tr>
            </table>

            @if ($bloque['horarioGeneral']['dias']->isNotEmpty() && $bloque['horarioGeneral']['horas']->isNotEmpty())
                <table class="horario">
                    <thead>
                        <tr>
                            <th class="hora-col">Hora</th>

                            @foreach ($bloque['horarioGeneral']['dias'] as $dia)
                                <th>{{ $dia['nombre'] }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bloque['horarioGeneral']['horas'] as $hora)
                            <tr>
                                <td class="hora-col">
                                    {{ $hora['inicio'] }} - {{ $hora['fin'] }}
                                </td>

                                @foreach ($bloque['horarioGeneral']['dias'] as $dia)
                                    @php
                                        $celdas = $bloque['horarioGeneral']['celdas'][$hora['key']][$dia['key']] ?? [];
                                    @endphp

                                    <td>
                                        @forelse ($celdas as $horario)
                                            <div class="materia">
                                                <strong>
                                                    {{ $horario->grado?->nombre ?? 'Grado' }}
                                                    @if ($horario->nivel?->nombre)
                                                        {{ $horario->nivel->nombre }}
                                                    @endif
                                                </strong>
                                                <br>
                                                {{ $horario->asignacionMateria?->materia?->materia ?? 'Materia no definida' }}
                                            </div>

                                            @if (!$loop->last)
                                                <br>
                                            @endif
                                        @empty
                                            <div class="libre">---</div>
                                        @endforelse
                                    </td>
                                @endforeach
                            </tr>
                        @endforeach

                        <tr class="horas-dia">
                            <td class="label">Horas por día</td>

                            @foreach ($bloque['horarioGeneral']['dias'] as $dia)
                                <td>{{ $bloque['horasPorDia'][$dia['key']] ?? 0 }}</td>
                            @endforeach
                        </tr>
                    </tbody>
                </table>

                <div class="section-title">
                    Materias asignadas al docente
                </div>

                <table class="materias">
                    <thead>
                        <tr>
                            <th class="num">#</th>
                            <th class="mat">Materia</th>
                            <th class="nivel">Nivel</th>
                            <th class="grado">Grados</th>
                            <th class="grupo">Grupos</th>
                            <th class="bloques">Bloques</th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($bloque['materiasAsignadas'] as $materia)
                            <tr>
                                <td>{{ $loop->iteration }}</td>
                                <td>{{ $materia['materia'] }}</td>
                                <td>{{ $materia['nivel'] }}</td>
                                <td>{{ $materia['grado'] }}</td>
                                <td>{{ $materia['grupo'] }}</td>
                                <td>{{ $materia['bloques'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                <div class="total-wrapper">
                    <table class="total-table">
                        <tr>
                            <td class="total-label">Total de horas semanales</td>
                            <td class="total-value">{{ $bloque['totalHorasSemanales'] }}</td>
                        </tr>
                    </table>
                </div>
            @else
                <div class="empty">
                    No hay horario registrado para este profesor.
                </div>
            @endif
        </div>
    @empty
        <div class="page">
            <div class="empty">
                No hay horarios registrados.
            </div>
        </div>
    @endforelse
</body>

</html>
