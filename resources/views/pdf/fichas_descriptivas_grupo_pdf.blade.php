<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Fichas descriptivas</title>
    @include('pdf.partials.ficha_descriptiva_css')
</head>

<body>
    @foreach ($alumnos as $alumno)
        @php
            $fichasAlumno = $fichas->get($alumno->id, collect());
            $educadoraActual = $educadoraNombre ?: 'EDUCADORA';
        @endphp

        @include('pdf.partials.ficha_descriptiva_page', [
            'alumno' => $alumno,
            'periodo' => $periodo,
            'periodoNombre' => $periodoNombre,
            'cicloEscolar' => $cicloEscolar,
            'campos' => $campos,
            'fichas' => $fichasAlumno,
            'fechaLugar' => $fechaLugar,
            'logoPrincipal' => $logoPrincipal ?? null,
            'logoPenacho' => $logoPenacho ?? null,
            'marcaAgua' => $marcaAgua ?? null,
            'campoImagenes' => $campoImagenes ?? [],
            'educadoraNombre' => $educadoraActual,
            'directoraNombre' => $directoraNombre ?? 'DIRECCIÓN',
        ])

        @if (!$loop->last)
            <div style="page-break-after: always;"></div>
        @endif
    @endforeach
</body>

</html>
