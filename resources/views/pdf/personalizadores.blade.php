<!DOCTYPE html>
<html lang="en">

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

        $nombreGrado = $grado->nombre ?? ($grado->grado ?? '');
        $nombreNivel = strtoupper($nivel->nombre ?? ($nivel->nivel ?? 'NIVEL'));
        $nombreGrupo = $grupo->nombre ?? '';

    @endphp




    <table>
        @forelse ($alumnos as $alumno)
            <tr>
                <td style="width:260px">
                    {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }} {{ $alumno->nombre }}
                </td>

                <td>
                    @if ($esBachillerato)
                        {{ $nombreNivel }}, GRUPO: {{ $nombreGrupo }}
                    @else
                        {{ $nombreGrado }}° DE {{ $nombreNivel }}, GRUPO: {{ $nombreGrupo }}
                    @endif


                </td>
                <td>
                    GEN: {{ $generacion->anio_ingreso }} - {{ $generacion->anio_egreso }}
                </td>
            </tr>
        @empty
            <tr>
                <td>
                    <p>No hay alumnos registrados.</p>
                </td>
            </tr>
        @endempty
</table>


</body>

</html>
