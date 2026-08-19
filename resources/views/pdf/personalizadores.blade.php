<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Personalizadores</title>
</head>

<style>
    @page {
        margin: 18px 28px;
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

    body {
        font-family: 'ARIAL', sans-serif;
        text-transform: uppercase;
    }

    table {
        width: 15.4cm;
        height: 1cm;
        margin: 10px auto;
        border-collapse: collapse;
        font-size: 11px;
        color: #000;
    }

    table th,
    table td {
        border: 1px solid #000;
        vertical-align: middle;
        padding: 8px 7px;
    }
</style>

<body>
    @php
        $modoGlobalActivo = (bool) ($modoGlobal ?? false);

        $nombreGrado = $grado->nombre ?? ($grado->grado ?? '');
        $nombreNivel = mb_strtoupper((string) ($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL')), 'UTF-8');
        $nombreGrupo = $grupo?->asignacionGrupo?->nombre ?? '';
    @endphp

    <table>
        @forelse ($alumnos as $alumno)
            @php
                if ($modoGlobalActivo) {
                    $nivelAlumno = $alumno->nivel;
                    $gradoAlumno = $alumno->grado;
                    $grupoAlumno = $alumno->grupo;
                    $generacionAlumno = $alumno->generacion;

                    $nivelTexto = mb_strtoupper((string) ($nivelAlumno?->nombre ?? 'SIN NIVEL'), 'UTF-8');
                    $gradoTexto = (string) ($gradoAlumno?->nombre ?? 'SIN GRADO');
                    $grupoTexto = (string) ($grupoAlumno?->asignacionGrupo?->nombre ?? 'SIN GRUPO');
                    $esBachilleratoAlumno = (int) ($nivelAlumno?->id ?? 0) === 4 || $nivelAlumno?->slug === 'bachillerato';
                    $generacionTexto = $generacionAlumno
                        ? trim((string) $generacionAlumno->anio_ingreso . ' - ' . (string) $generacionAlumno->anio_egreso)
                        : 'SIN GENERACIÓN';
                } else {
                    $nivelTexto = $nombreNivel;
                    $gradoTexto = $nombreGrado;
                    $grupoTexto = $nombreGrupo;
                    $esBachilleratoAlumno = (bool) ($esBachillerato ?? false);
                    $generacionTexto = $generacion
                        ? trim((string) $generacion->anio_ingreso . ' - ' . (string) $generacion->anio_egreso)
                        : 'SIN GENERACIÓN';
                }
            @endphp

            <tr>
                <td style="width:260px">
                    {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }} {{ $alumno->nombre }}
                </td>

                <td>
                    @if ($esBachilleratoAlumno)
                        {{ $nivelTexto }}, GRUPO: {{ $grupoTexto }}
                    @else
                        {{ $gradoTexto }}° DE {{ $nivelTexto }}, GRUPO: {{ $grupoTexto }}
                    @endif
                </td>

                <td>
                    GEN: {{ $generacionTexto }}
                </td>
            </tr>
        @empty
            <tr>
                <td>
                    <p>No hay alumnos registrados.</p>
                </td>
            </tr>
        @endforelse
    </table>
</body>

</html>
