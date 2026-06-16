<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Horario del profesor</title>

    <style>
        @page {
            margin: 24px 28px;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, Arial, Helvetica, sans-serif;
            color: #1f2937;
            font-size: 10px;
        }

        .header {
            border-bottom: 4px solid #006492;
            padding-bottom: 10px;
            margin-bottom: 14px;
        }

        .institucion {
            font-size: 11px;
            font-weight: bold;
            color: #006492;
            text-transform: uppercase;
        }

        .titulo {
            margin-top: 4px;
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }

        .subtitulo {
            margin-top: 4px;
            font-size: 10px;
            color: #4b5563;
        }

        .datos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .datos td {
            border: 1px solid #d1d5db;
            padding: 6px 8px;
        }

        .label {
            width: 120px;
            font-weight: bold;
            background: #f3f4f6;
            color: #374151;
        }

        .nivel-box {
            margin-top: 14px;
            margin-bottom: 8px;
            border-left: 6px solid #88AC2E;
            background: #f8fafc;
            padding: 8px 10px;
        }

        .nivel-title {
            font-size: 13px;
            font-weight: bold;
            color: #111827;
        }

        .nivel-meta {
            margin-top: 3px;
            font-size: 9px;
            color: #6b7280;
        }

        table.horario {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14px;
            table-layout: fixed;
        }

        .horario th {
            border: 1px solid #94a3b8;
            background: #006492;
            color: white;
            padding: 6px 5px;
            font-size: 8px;
            text-transform: uppercase;
        }

        .horario td {
            border: 1px solid #cbd5e1;
            padding: 5px;
            vertical-align: top;
            min-height: 42px;
        }

        .hora {
            width: 72px;
            background: #f1f5f9;
            font-weight: bold;
            text-align: center;
            color: #0f172a;
        }

        .materia {
            font-weight: bold;
            color: #111827;
            font-size: 8.7px;
            line-height: 1.25;
        }

        .grupo {
            margin-top: 3px;
            color: #006492;
            font-size: 8px;
            font-weight: bold;
        }

        .extra {
            margin-top: 2px;
            color: #64748b;
            font-size: 7.5px;
        }

        .libre {
            color: #cbd5e1;
            text-align: center;
            font-size: 8px;
            padding-top: 8px;
        }

        .empty {
            margin-top: 40px;
            text-align: center;
            color: #64748b;
            font-size: 13px;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -10px;
            left: 0;
            right: 0;
            font-size: 8px;
            color: #64748b;
            text-align: right;
        }
    </style>
</head>

<body>
    <div class="header">
        <div class="institucion">Centro Universitario Moctezuma</div>
        <div class="titulo">Horario del profesor</div>
        <div class="subtitulo">
            Documento generado para consulta académica institucional.
        </div>
    </div>

    <table class="datos">
        <tr>
            <td class="label">Profesor</td>
            <td>{{ $profesorNombre }}</td>

            <td class="label">Vista</td>
            <td>
                {{ $nivelSeleccionado ? $nivelSeleccionado->nombre : 'Horario completo' }}
            </td>
        </tr>

        <tr>
            <td class="label">Correo</td>
            <td>{{ $profesor->correo ?: 'Sin correo registrado' }}</td>

            <td class="label">Teléfono</td>
            <td>{{ $profesor->telefono_movil ?: 'Sin teléfono registrado' }}</td>
        </tr>
    </table>

    @forelse ($matriz as $bloque)
        <div class="nivel-box">
            <div class="nivel-title">
                {{ $bloque['nivel']->nombre ?? 'Nivel no definido' }}
            </div>

            <div class="nivel-meta">
                {{ $bloque['total_clases'] }} clases ·
                {{ $bloque['total_materias'] }} materias ·
                {{ $bloque['total_grupos'] }} grupos
                @if (!empty($bloque['nivel']->cct))
                    · C.C.T. {{ $bloque['nivel']->cct }}
                @endif
            </div>
        </div>

        <table class="horario">
            <thead>
                <tr>
                    <th class="hora">Hora</th>

                    @foreach ($bloque['dias'] as $dia)
                        <th>{{ $dia->dia }}</th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @foreach ($bloque['horas'] as $hora)
                    <tr>
                        <td class="hora">
                            {{ \Carbon\Carbon::parse($hora->hora_inicio)->format('H:i') }}
                            <br>
                            {{ \Carbon\Carbon::parse($hora->hora_fin)->format('H:i') }}
                        </td>

                        @foreach ($bloque['dias'] as $dia)
                            @php
                                $celdas = $bloque['celdas'][$hora->id][$dia->id] ?? [];
                            @endphp

                            <td>
                                @forelse ($celdas as $horario)
                                    <div class="materia">
                                        {{ $horario->asignacionMateria?->materia?->materia ?? 'Materia no definida' }}
                                    </div>

                                    <div class="grupo">
                                        {{ $horario->grado?->nombre ?? 'Grado' }}
                                        @if ($horario->grupo?->asignacionGrupo?->nombre)
                                            · Grupo {{ $horario->grupo->asignacionGrupo->nombre }}
                                        @endif
                                    </div>

                                    @if ($horario->generacion)
                                        <div class="extra">
                                            Gen.
                                            {{ $horario->generacion->anio_ingreso }}-{{ $horario->generacion->anio_egreso }}
                                        </div>
                                    @endif

                                    @if ($horario->semestre)
                                        <div class="extra">
                                            Semestre {{ $horario->semestre->numero }}
                                        </div>
                                    @endif
                                @empty
                                    <div class="libre">Libre</div>
                                @endforelse
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
        @empty
            <div class="empty">
                No hay horario registrado para el profesor seleccionado.
            </div>
        @endforelse

        <div class="footer">
            Generado el {{ now()->format('d/m/Y H:i') }}
        </div>
    </body>

    </html>
