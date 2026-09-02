<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Lista de alumnos institucional</title>
    <style>
        @page { size: letter portrait; margin: 0.32in 0.38in; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: DejaVu Sans, Arial, sans-serif; color: #000; font-size: 8px; }
        .pagina { page-break-after: always; }
        .pagina:last-child { page-break-after: auto; }
        .encabezado { width: 100%; border-collapse: collapse; margin-bottom: 2px; }
        .encabezado td { border: none; vertical-align: middle; }
        .logo { width: 28%; text-align: left; }
        .logo img { width: 150px; max-height: 52px; object-fit: contain; }
        .titulo { width: 72%; text-align: center; }
        .titulo-principal { font-family: DejaVu Serif, Times New Roman, serif; font-size: 13px; font-weight: bold; }
        .ciclo { font-family: DejaVu Serif, Times New Roman, serif; font-size: 10px; font-weight: bold; margin-top: 1px; }
        .contexto { width: 100%; border-collapse: collapse; margin-bottom: 4px; }
        .contexto td { border: none; font-size: 8px; font-weight: bold; vertical-align: middle; }
        .contexto .escuela { width: 70%; text-align: center; }
        .contexto .grupo { width: 30%; text-align: right; white-space: nowrap; }
        .subrayado { text-decoration: underline; }
        table.lista { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.lista th, table.lista td { border: 0.7px solid #000; padding: 2px 3px; height: 18px; line-height: 1.05; }
        table.lista th { background: #f2f2f2; font-size: 7.5px; font-weight: bold; text-align: center; }
        table.lista td { font-size: 7.5px; }
        .np { width: 5%; text-align: center; }
        .ap { width: 17%; }
        .am { width: 17%; }
        .nombre { width: 19%; }
        .curp { width: 27%; }
        .fnac { width: 15%; text-align: center; }
        .firmas { width: 100%; border-collapse: collapse; margin-top: 16px; }
        .firmas td { width: 50%; border: none; text-align: center; vertical-align: top; font-size: 7.5px; font-weight: bold; }
        .firma-espacio { height: 26px; }
        .linea { display: inline-block; width: 62%; border-top: 0.7px solid #000; padding-top: 2px; }
        .pie { width: 100%; border-collapse: collapse; margin-top: 14px; }
        .pie td { border: none; vertical-align: middle; }
        .supervisor { width: 70%; text-align: center; font-size: 7.5px; font-weight: bold; }
        .totales-wrap { width: 30%; text-align: right; }
        table.totales { margin-left: auto; border-collapse: collapse; }
        table.totales th, table.totales td { border: 0.7px solid #000; padding: 3px 8px; text-align: center; font-size: 8px; }
        table.totales th { font-weight: bold; }
        .pagina-indice { margin-top: 1px; text-align: right; color: #666; font-size: 6.5px; }
    </style>
</head>
<body>
@foreach ($paginas as $pagina)
    <div class="pagina">
        <table class="encabezado">
            <tr>
                <td class="logo">
                    @if (is_file($logo_seg))
                        <img src="{{ $logo_seg }}" alt="Secretaría de Educación Guerrero">
                    @endif
                </td>
                <td class="titulo">
                    <div class="titulo-principal">LISTA DE ALUMNOS</div>
                    <div class="ciclo">{{ $ciclo->nombre }}</div>
                </td>
            </tr>
        </table>

        <table class="contexto">
            <tr>
                <td class="escuela">
                    {{ $pagina['escuela'] }}
                    C.C.T.: <span class="subrayado">{{ $pagina['cct'] }}</span>
                </td>
                <td class="grupo">
                    GRADO: <span class="subrayado">{{ $pagina['grado'] }}</span>
                    @if (filled($pagina['semestre']))
                        &nbsp; SEM.: <span class="subrayado">{{ $pagina['semestre'] }}</span>
                    @endif
                    &nbsp; GRUPO: <span class="subrayado">{{ $pagina['grupo_nombre'] }}</span>
                </td>
            </tr>
        </table>

        <table class="lista">
            <thead>
                <tr>
                    <th class="np">NP</th>
                    <th class="ap">APELLIDO P.</th>
                    <th class="am">APELLIDO M.</th>
                    <th class="nombre">NOMBRE</th>
                    <th class="curp">CURP</th>
                    <th class="fnac">FECHA DE<br>NAC.</th>
                </tr>
            </thead>
            <tbody>
                @php($numeroBase = ((int) $pagina['pagina_grupo'] - 1) * 30)
                @foreach ($pagina['filas'] as $indice => $alumno)
                    <tr>
                        @if ($alumno)
                            <td class="np">{{ $numeroBase + $indice + 1 }}</td>
                            <td>{{ mb_strtoupper((string) $alumno->apellido_paterno) }}</td>
                            <td>{{ mb_strtoupper((string) $alumno->apellido_materno) }}</td>
                            <td>{{ mb_strtoupper((string) $alumno->nombre) }}</td>
                            <td>{{ mb_strtoupper((string) $alumno->curp) }}</td>
                            <td class="fnac">{{ $alumno->fecha_nacimiento?->format('d/m/Y') }}</td>
                        @else
                            <td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>

        <table class="firmas">
            <tr>
                <td>
                    <div>{{ $pagina['docente_cargo'] }}</div>
                    <div class="firma-espacio"></div>
                    <div class="linea">{{ $pagina['docente_nombre'] ?: ' ' }}</div>
                </td>
                <td>
                    <div>{{ $pagina['director_cargo'] }}</div>
                    <div class="firma-espacio"></div>
                    <div class="linea">{{ $pagina['director_nombre'] ?: ' ' }}</div>
                </td>
            </tr>
        </table>

        <table class="pie">
            <tr>
                <td class="supervisor">
                    <div>{{ $pagina['supervisor_cargo'] }}</div>
                    <div class="firma-espacio"></div>
                    <div class="linea">{{ $pagina['supervisor_nombre'] ?: ' ' }}</div>
                </td>
                <td class="totales-wrap">
                    <table class="totales">
                        <tr><th>H</th><th>M</th><th>TOTAL</th></tr>
                        <tr><td>{{ $pagina['hombres'] }}</td><td>{{ $pagina['mujeres'] }}</td><td>{{ $pagina['total'] }}</td></tr>
                    </table>
                </td>
            </tr>
        </table>

        @if ((int) $pagina['paginas_grupo'] > 1)
            <div class="pagina-indice">
                Página {{ $pagina['pagina_grupo'] }} de {{ $pagina['paginas_grupo'] }} del grupo
            </div>
        @endif
    </div>
@endforeach
</body>
</html>
