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
        width: 80%;
        margin: 10px auto;
        border-collapse: collapse;

        font-size: 12px;
        color: #000;

    }

    table th,
    table td {
        border: 1px solid #000;
        padding: 0 8px;
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
                <td style="width:320px">
                    {{ $alumno->nombre }} {{ $alumno->apellido_paterno }} {{ $alumno->apellido_materno }}
                </td>

                <td>
                    {{ $nombreGrado }}°GRADO DE {{ $nombreNivel }}, GRUPO: {{ $nombreGrupo }}
                </td>
                <td>
                    GEN:
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
