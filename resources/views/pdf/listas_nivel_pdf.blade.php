<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Listas por nivel</title>

    <style>
        @page {
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
        }

        .salto-pagina {
            page-break-after: always;
        }

        .salto-pagina:last-child {
            page-break-after: auto;
        }
    </style>
</head>

<body>
    @foreach ($contextos as $contexto)
        <div class="salto-pagina">
            @include($contexto['vistaPdf'], $contexto['data'])
        </div>
    @endforeach
</body>

</html>
