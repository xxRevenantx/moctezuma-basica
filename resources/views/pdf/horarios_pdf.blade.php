<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Horario</title>
    <style>
        @page {
            margin: 20px 24px;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');

        }

        /* arial bold */
        @font-face {
            font-family: 'ARIAL';
            font-style: bold;
            font-weight: 700;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        body {
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
        }

        * {
            box-sizing: border-box;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 14px;
            border-bottom: 2px solid #7c3aed;
            padding-bottom: 10px;
        }

        .titulo {
            font-size: 18px;
            font-weight: bold;
            color: #4c1d95;
            margin: 0 0 6px 0;
        }

        .subtitulo {
            font-size: 10px;
            color: #64748b;
            margin: 0;
        }

        .bloque-info {
            width: 100%;
            margin-bottom: 14px;
        }

        .info-item {
            display: inline-block;
            width: 24%;
            vertical-align: top;
            margin-right: 1%;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 8px 10px;
        }

        .info-item:last-child {
            margin-right: 0;
        }

        .info-label {
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            color: #64748b;
            margin-bottom: 3px;
        }

        .info-valor {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        thead th {
            background: #ede9fe;
            color: #4c1d95;
            border: 1px solid #cbd5e1;
            padding: 8px 6px;
            text-align: center;
            font-size: 10px;
        }

        tbody td {
            border: 1px solid #cbd5e1;
            padding: 6px;
            vertical-align: top;
        }

        .col-hora {
            width: 110px;
            background: #f8fafc;
            font-weight: bold;
            text-align: center;
            vertical-align: middle;
            color: #334155;
        }

        .celda {
            min-height: 58px;
        }

        .materia {
            display: block;
            font-weight: bold;
            font-size: 10px;
            color: #1e293b;
            margin-bottom: 5px;
            text-align: center;
        }

        .profesor {
            display: block;
            font-size: 9px;
            color: #475569;
            line-height: 1.35;
            text-align: center;
        }

        .vacio {
            color: #94a3b8;
            font-style: italic;
            text-align: center;
            margin-top: 10px;
        }

        .pie {
            margin-top: 12px;
            font-size: 9px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="encabezado">
        <p class="titulo">Horario escolar</p>
        <p class="subtitulo">
            Documento generado el {{ $fecha_impresion->format('d/m/Y h:i A') }}
        </p>
    </div>

    <div class="bloque-info">
        <div class="info-item">
            <div class="info-label">Nivel</div>
            <div class="info-valor">{{ $nivel->nombre }}</div>
        </div>

        <div class="info-item">
            <div class="info-label">Grado</div>
            <div class="info-valor">{{ $grado->nombre }}</div>
        </div>

        <div class="info-item">
            <div class="info-label">Grupo</div>
            <div class="info-valor">{{ $grupo->nombre }}</div>
        </div>

        @if ($esBachillerato)
            <div class="info-item">
                <div class="info-label">Semestre</div>
                <div class="info-valor">
                    {{ $semestre->semestre ?? ($semestre->nombre ?? 'Sin semestre') }}
                </div>
            </div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th class="col-hora">Hora</th>
                @foreach ($dias as $dia)
                    <th>{{ $dia->dia }}</th>
                @endforeach
            </tr>
        </thead>

        <tbody>
            @forelse ($horas as $hora)
                <tr>
                    <td class="col-hora">
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_inicio)->format('h:i A') }}
                        -
                        {{ \Carbon\Carbon::createFromFormat('H:i:s', $hora->hora_fin)->format('h:i A') }}
                    </td>

                    @foreach ($dias as $dia)
                        @php
                            $registro = $horarioPorCelda->get($hora->id . '-' . $dia->id);
                            $asignacion = $registro?->asignacionMateria;
                            $profesor = $asignacion?->profesor;

                            $nombreProfesor = $profesor
                                ? trim(
                                    ($profesor->nombre ?? '') .
                                        ' ' .
                                        ($profesor->apellido_paterno ?? '') .
                                        ' ' .
                                        ($profesor->apellido_materno ?? ''),
                                )
                                : null;
                        @endphp

                        <td>
                            <div class="celda">
                                @if ($asignacion)
                                    <span class="materia">
                                        {{ $asignacion->materia }}
                                    </span>

                                    <span class="profesor">
                                        {{ $nombreProfesor ?: 'Sin profesor asignado' }}
                                    </span>
                                @else
                                    <div class="vacio">Sin asignación</div>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $dias->count() + 1 }}" style="text-align:center; padding: 18px;">
                        No hay registros de horario para los filtros seleccionados.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pie">
        Centro Universitario Moctezuma
    </div>
</body>

</html>
