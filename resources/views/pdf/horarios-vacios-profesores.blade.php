<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Horarios individuales por profesor</title>

    <style>
        @page {
            margin: 14px 18px 16px 18px;
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

        @font-face {
            font-family: 'coolvetica';
            font-style: normal;
            src: url('{{ storage_path('fonts/Coolveticaregular.ttf') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            color: #0f172a;
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            font-size: 10px;
        }

        .pagina {
            width: 100%;
            page-break-after: always;
        }

        .pagina:last-child {
            page-break-after: auto;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-encabezado td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 78px;
            text-align: center;
        }

        .logo img {
            max-width: 72px;
            max-height: 56px;
        }

        .centro {
            padding: 0 12px;
            text-align: center;
        }

        .titulo-institucion {
            margin: 0;
            color: #5790d9;
            font-family: coolvetica, DejaVu Sans, sans-serif;
            font-size: 26px;
            line-height: 1;
        }

        .linea-titulo {
            height: 2px;
            margin: 4px 0 5px;
            background: #94a3b8;
        }

        .titulo-principal {
            margin: 0;
            color: #000;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .subtitulo-principal {
            margin: 2px 0 0;
            color: #334155;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.2;
            text-transform: uppercase;
        }

        .tabla-datos {
            width: 100%;
            margin-top: 7px;
            border-collapse: separate;
            border-spacing: 2px 0;
        }

        .tabla-datos td {
            height: 36px;
            padding: 5px 8px;
            border: 1px solid #9fb8cb;
            background: #f8fbfd;
            vertical-align: middle;
        }

        .dato-docente {
            width: 52%;
        }

        .dato-nivel {
            width: 23%;
        }

        .dato-ciclo {
            width: 25%;
        }

        .dato-etiqueta {
            display: block;
            margin-bottom: 2px;
            color: #006492;
            font-size: 7px;
            font-weight: 700;
            letter-spacing: .35px;
            text-transform: uppercase;
        }

        .dato-valor {
            display: block;
            color: #0f172a;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .aviso {
            margin-top: 6px;
            padding: 5px 8px;
            border: 1px solid #e8b84d;
            background: #fff8e7;
            color: #7c5300;
            font-size: 7px;
            font-weight: 700;
            line-height: 1.25;
        }

        .aviso.neutro {
            border-color: #cbd5e1;
            background: #f8fafc;
            color: #64748b;
        }

        .tabla-horario {
            width: 100%;
            margin-top: 7px;
            border-collapse: separate;
            border-spacing: 2px;

        }

        .tabla-horario th,
        .tabla-horario td {
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .tabla-horario tr {
            page-break-inside: avoid;
        }

        .th-horario,
        .th-dia {
            height: 24px;
            border: none;
            color: #fff;
            font-size: 9px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-horario {
            width: 94px;
            background: #88ac2e;
        }

        .th-lunes {
            background: #006492;
        }

        .th-martes {
            background: #08709f;
        }

        .th-miercoles {
            background: #117ca9;
        }

        .th-jueves {
            background: #2389b3;
        }

        .th-viernes {
            background: #3695bd;
        }

        .th-generico {
            background: #3f8fac;
        }

        .columna-hora {
            width: 94px;
            border: none;
            background: #dce8c7;
            color: #0f172a;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.15;
            white-space: nowrap;
        }

        .celda-vacia {
            height: 37px;
            padding: 2px 7px !important;
            border: 1px solid #bfd2e0;
            background: #eef5fa;
            color: #0f172a;
            font-size: 7px;
            line-height: 1.1;
        }

        .celda-receso {
            height: 30px;
            border: 1px solid #d79408;
            background: #f4ad18;
            color: #111827;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .linea-captura {
            height: 13px;
            border-bottom: 1px solid #adc2d1;
        }

        .campo {
            height: 13px;
            border-bottom: 1px solid #adc2d1;
            color: #526f82;
            font-size: 5px;
            line-height: 12px;
            text-align: left;
            text-transform: uppercase;
        }

        .bloque-carga {
            margin-top: 7px;
            page-break-inside: avoid;
        }

        .titulo-carga {
            padding: 5px 8px;
            border: 1px solid #7f9db2;
            border-bottom: none;
            background: #d8e6ef;
            color: #0f172a;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
            letter-spacing: .35px;
            text-transform: uppercase;
        }

        .tabla-carga {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 7px;
        }

        .tabla-carga th {
            padding: 4px 6px;
            border: 1px solid #8fa8ba;
            background: #edf3f7;
            color: #006492;
            font-size: 6.5px;
            text-transform: uppercase;
        }

        .tabla-carga td {
            padding: 4px 6px;
            border: 1px solid #aebfcb;
            color: #1e293b;
            vertical-align: middle;
        }

        .tabla-carga .tipo {
            width: 12%;
            text-align: center;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tabla-carga .materia {
            width: 43%;
            font-weight: 700;
        }

        .tabla-carga .grupo {
            width: 45%;
        }

        .sin-carga {
            padding: 8px;
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #64748b;
            font-size: 8px;
            font-style: italic;
            text-align: center;
        }

        .pie {
            margin-top: 5px;
            color: #64748b;
            font-size: 6px;
            text-align: right;
        }

        .compacta .tabla-datos td {
            height: 31px;
        }

        .compacta .celda-vacia {
            height: 31px;
        }

        .compacta .celda-receso {
            height: 25px;
        }

        .compacta .tabla-horario {
            margin-top: 5px;
        }

        .compacta .bloque-carga {
            margin-top: 5px;
        }

        .compacta .tabla-carga td,
        .compacta .tabla-carga th {
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .muy-compacta .titulo-institucion {
            font-size: 23px;
        }

        .muy-compacta .tabla-datos td {
            height: 28px;
        }

        .muy-compacta .celda-vacia {
            height: 27px;
        }

        .muy-compacta .celda-receso {
            height: 23px;
        }

        .muy-compacta .tabla-horario {
            margin-top: 4px;
        }

        .muy-compacta .bloque-carga {
            margin-top: 4px;
        }

        .muy-compacta .tabla-carga {
            font-size: 6.5px;
        }
    </style>
</head>

<body>
    @php
        $diasOrdenados = collect($dias)->values();
        $totalHoras = collect($horas)->count();
        $claseCompacta = $totalHoras >= 9 ? 'muy-compacta' : ($totalHoras >= 7 ? 'compacta' : '');

        $claseDia = function ($dia) {
            $nombre = \Illuminate\Support\Str::lower(\Illuminate\Support\Str::ascii((string) ($dia->dia ?? '')));

            return match (true) {
                str_contains($nombre, 'lunes') => 'th-lunes',
                str_contains($nombre, 'martes') => 'th-martes',
                str_contains($nombre, 'miercoles') => 'th-miercoles',
                str_contains($nombre, 'jueves') => 'th-jueves',
                str_contains($nombre, 'viernes') => 'th-viernes',
                default => 'th-generico',
            };
        };
    @endphp

    @foreach ($paginas as $pagina)
        <section class="pagina {{ $claseCompacta }}">
            <table class="tabla-encabezado">
                <tr>
                    <td class="logo">
                        @if ($logoIzquierdo)
                            <img src="{{ $logoIzquierdo }}" alt="Logo">
                        @endif
                    </td>
                    <td class="centro">
                        <h1 class="titulo-institucion">{{ $escuela->nombre ?? 'Centro Universitario Moctezuma' }}</h1>
                        <div class="linea-titulo"></div>
                        <p class="titulo-principal">Horario individual del profesor</p>
                        <p class="subtitulo-principal">Formato vacío para organización académica</p>
                    </td>
                    <td class="logo">
                        @if ($logoDerecho)
                            <img src="{{ $logoDerecho }}" alt="Nivel">
                        @endif
                    </td>
                </tr>
            </table>

            <table class="tabla-datos">
                <tr>
                    <td class="dato-docente">
                        <span class="dato-etiqueta">Profesor</span>
                        <span class="dato-valor">{{ $pagina['profesor_nombre'] }}</span>
                    </td>
                    <td class="dato-nivel">
                        <span class="dato-etiqueta">Nivel</span>
                        <span class="dato-valor">{{ $nivel->nombre }}</span>
                    </td>
                    <td class="dato-ciclo">
                        <span class="dato-etiqueta">Ciclo escolar</span>
                        <span class="dato-valor">{{ $cicloEscolar->inicio_anio }} -
                            {{ $cicloEscolar->fin_anio }}</span>
                    </td>
                </tr>
            </table>

            @if ($recesoInconsistente)
                <div class="aviso">
                    Se detectaron {{ $recesoVariantes }} configuraciones distintas de receso entre los grupos del
                    nivel.
                    Este formato utiliza el horario de receso predominante entre los {{ $gruposEvaluadosReceso }}
                    grupos con horario capturado.
                </div>
            @elseif ($recesoHoraIds->isEmpty())
                <div class="aviso neutro">
                    No se detectó un bloque de receso en los horarios normales capturados para este nivel dentro del
                    rango seleccionado.
                </div>
            @endif

            <table class="tabla-horario">
                <thead>
                    <tr>
                        <th class="th-horario">Horario</th>
                        @foreach ($diasOrdenados as $dia)
                            <th class="th-dia {{ $claseDia($dia) }}">{{ $dia->dia }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @foreach ($horas as $hora)
                        @php($esReceso = $recesoHoraIds->contains((int) $hora->id))
                        <tr>
                            <td class="columna-hora">{{ $hora->hora_inicio }} - {{ $hora->hora_fin }}</td>

                            @if ($esReceso)
                                <td class="celda-receso" colspan="{{ max(1, $diasOrdenados->count()) }}">RECESO</td>
                            @else
                                @foreach ($diasOrdenados as $dia)
                                    <td class="celda-vacia">
                                        @if ($estiloCelda === 'lineas')
                                            <div class="linea-captura"></div>
                                            <div class="linea-captura"></div>
                                        @elseif ($estiloCelda === 'campos')
                                            <div class="campo">Materia:</div>
                                            <div class="campo">Grupo:</div>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="bloque-carga">
                <div class="titulo-carga">Carga académica asignada</div>

                @if ($pagina['sin_carga'])
                    <div class="sin-carga">Sin carga académica asignada para este nivel y ciclo escolar.</div>
                @else
                    <table class="tabla-carga">
                        <thead>
                            <tr>
                                <th class="tipo">Tipo</th>
                                <th class="materia">Materia / actividad</th>
                                <th class="grupo">Grado y grupo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($pagina['carga'] as $carga)
                                <tr>
                                    <td class="tipo">{{ $carga['tipo'] }}</td>
                                    <td class="materia">{{ $carga['materia'] }}</td>
                                    <td class="grupo">{{ $carga['grupo'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="pie">
                Horario individual · {{ $nivel->nombre }} · Ciclo
                {{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}
            </div>
        </section>
    @endforeach
</body>

</html>
