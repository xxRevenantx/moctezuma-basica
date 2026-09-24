<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Personalizadores de profesores</title>
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

    .nombre {
        width: 260px;
        font-weight: 700;
    }

    .funcion {
        width: 250px;
    }

    .ciclo {
        width: 165px;
        text-align: center;
        font-weight: 700;
    }
</style>

<body>
    @php
        $cicloTexto = trim(
            (string) ($cicloEscolar?->inicio_anio ?? '') . '-' . (string) ($cicloEscolar?->fin_anio ?? ''),
        );
    @endphp

    <table>
        @forelse ($profesores as $profesor)
            @php
                $titulo = trim((string) ($profesor->titulo ?? ''));
                $nombre = trim(
                    implode(
                        ' ',
                        array_filter([
                            $profesor->nombre ?? null,
                            $profesor->apellido_paterno ?? null,
                            $profesor->apellido_materno ?? null,
                        ]),
                    ),
                );
                $nombreCompleto = trim(($titulo !== '' ? $titulo . ' ' : '') . $nombre);
                $niveles = (string) ($profesor->niveles_personalizador ?? 'SIN NIVEL ASIGNADO');
            @endphp

            <tr>
                <td class="nombre">{{ $nombreCompleto }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="3">No hay profesores registrados.</td>
            </tr>
        @endforelse
    </table>
</body>

</html>
