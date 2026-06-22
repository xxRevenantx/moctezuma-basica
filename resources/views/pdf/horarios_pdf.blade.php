<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    @php
        $nombreNivel = mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8');

        $nombreGeneracion = isset($generacion)
            ? ($generacion->anio_ingreso ?? '') . '-' . ($generacion->anio_egreso ?? '')
            : 'GENERACIÓN';

        $nombreGrado = mb_strtoupper($grado->nombre ?? 'GRADO', 'UTF-8');

        $nombreGrupo = mb_strtoupper($grupo->asignacionGrupo->nombre ?? ($grupo->nombre ?? 'GRUPO'), 'UTF-8');

        $tituloGrupo = 'GENERACIÓN: ' . $nombreGeneracion . ' · ' . $nombreGrado . '° GRADO, GRUPO: ' . $nombreGrupo;

        if (!empty($esBachillerato) && isset($semestre) && $semestre) {
            $tituloGrupo .= ' · SEMESTRE: ' . ($semestre->numero ?? '');
        }
    @endphp

    <title>Horario escolar de {{ $tituloGrupo }}</title>

    <style>
        @page {
            margin: 16px 20px 44px 20px;
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
            font-size: 13px;
        }

        .pagina {
            width: 100%;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 8px;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-encabezado td {
            border: none;
        }

        .logo-izq,
        .logo-der {
            width: 88px;
            text-align: center;
        }

        .logo-izq img,
        .logo-der img {
            max-width: 88px;
            max-height: 72px;
        }

        .centro {
            padding: 0 8px;
            text-align: center;
        }

        .titulo-institucion {
            margin: 0;
            color: #5790d9;
            font-family: coolvetica, DejaVu Sans, sans-serif;
            font-size: 30px;
            line-height: 1;
        }

        .linea-titulo {
            height: 2px;
            margin: 4px 0 6px;
            background: #9aa7b8;
        }

        .titulo-principal,
        .subtitulo-principal {
            margin: 0;
            color: #000000;
            font-size: 14px;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .franja-grupo {
            margin-top: 7px;
            padding: 5px 8px;
            border-top: 2px solid #3d95c8;
            border-bottom: 2px solid #3d95c8;
            color: #0869a6;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-horario {
            width: 100%;
            margin-top: 9px;
            border-collapse: separate;
            border-spacing: 2px;

        }

        .tabla-horario th,
        .tabla-horario td {
            border: none;
            padding: 5px 4px;
            text-align: center;
        }

        .tabla-horario tr {
            page-break-inside: avoid;
        }

        .th-grado,
        .th-horario,
        .th-dia {
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-grado {
            background: #f2616b;
        }

        .th-horario {
            background: #f4943b;
        }

        .th-lunes {
            background: #ef5e72;
        }

        .th-martes {
            background: #8a71b7;
        }

        .th-miercoles {
            background: #36aebc;
        }

        .th-jueves {
            background: #2f89c7;
        }

        .th-viernes {
            background: #4caf67;
        }

        .columna-grado {
            background: #24a7dc;
            color: #ffffff;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .imagen-nivel {
            margin-top: 7px;
        }

        .imagen-nivel img {
            width: 64px;
            height: auto;
        }

        .columna-hora {
            background: #c8d8ac;
            color: #000000;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
        }

        .celda-materia {
            background: #cfe0ef;
            color: #0f172a;
            font-size: 12px;
            line-height: 1.25;
        }

        .celda-no-calificable {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
        }

        .celda-extra {
            background: #ede9fe;
            color: #5b21b6;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
        }

        .celda-receso {
            background: #f2aa18;
            color: #000000;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .celda-taller {
            background: #cffafe;
            color: #0e7490;
            font-size: 12px;
            line-height: 1.2;
        }

        .actividad-normal {
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: 1px solid #67e8f9;
            color: #334155;
            font-size: 12px;
            font-weight: 700;
        }

        .bloque-taller {
            page-break-inside: avoid;
        }

        .bloque-taller+.bloque-taller {
            margin-top: 4px;
            padding-top: 4px;
            border-top: 1px solid #67e8f9;
        }

        .nombre-taller {
            display: block;
            color: #0c4a6e;
            font-size: 11px;
            line-height: 1.25;
            font-weight: 700;
        }

        .sin-registro {
            color: #64748b;
            font-style: italic;
            font-weight: 400;
        }

        .tabla-docentes {
            width: 100%;
            margin-top: 11px;
            border-collapse: collapse;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .tabla-docentes th {
            padding: 4px;
            border: 1px solid #7f96a8;
            background: #b9d0e2;
            color: #0f172a;
            font-weight: 700;
            text-transform: uppercase;
        }

        .tabla-docentes td {
            padding: 3px 6px;
            border: 1px solid #7f96a8;
            line-height: 1.25;
            text-align: center;
            vertical-align: middle;
        }

        .materia-docente+.materia-docente {
            margin-top: 2px;
            padding-top: 2px;
            border-top: 1px dotted #cbd5e1;
        }

        footer {
            position: fixed;
            right: 0;
            bottom: -25px;
            left: 0;
            padding-top: 4px;
            border-top: 1px solid #cbd5e1;
            color: #475569;
            font-size: 11px;
            line-height: 1;
            text-align: center;
        }
    </style>
</head>

<body>
    @php
        $profesorTitular = $profesor_titular ?? null;
        $docentesPreescolar = collect($docentes_preescolar ?? []);
        $docentesHorario = collect($docentes_horario ?? []);
        $diasOrdenados = collect($dias ?? [])->values();
        $horasOrdenadas = collect($horas ?? [])->values();
        $horarioPorCelda = collect($horarioPorCelda ?? []);
        $talleresPorCelda = collect($talleresPorCelda ?? []);

        $claseDia = static function ($dia): string {
            $nombre = \Illuminate\Support\Str::lower(
                \Illuminate\Support\Str::ascii(trim((string) ($dia->dia ?? ($dia->nombre ?? '')))),
            );

            return match (true) {
                str_contains($nombre, 'lunes') => 'th-lunes',
                str_contains($nombre, 'martes') => 'th-martes',
                str_contains($nombre, 'miercoles') => 'th-miercoles',
                str_contains($nombre, 'jueves') => 'th-jueves',
                str_contains($nombre, 'viernes') => 'th-viernes',
                default => 'th-lunes',
            };
        };

        $nombreCompleto = static function ($persona): string {
            if (!$persona) {
                return 'Sin profesor asignado';
            }

            $nombre = trim(
                implode(
                    ' ',
                    array_filter([
                        $persona->titulo ?? null,
                        $persona->nombre ?? null,
                        $persona->apellido_paterno ?? null,
                        $persona->apellido_materno ?? null,
                    ]),
                ),
            );

            return $nombre !== '' ? $nombre : 'Sin profesor asignado';
        };
    @endphp

    <div class="pagina">
        <div class="encabezado">
            <table class="tabla-encabezado">
                <tr>
                    <td class="logo-izq">
                        @if (!empty($logo_izquierdo))
                            <img src="{{ $logo_izquierdo }}" alt="Logo izquierdo">
                        @endif
                    </td>

                    <td class="centro">
                        <p class="titulo-institucion">
                            Centro Universitario Moctezuma
                        </p>

                        <div class="linea-titulo"></div>

                        <p class="titulo-principal">
                            Horario de clases
                        </p>

                        <p class="subtitulo-principal">
                            Ciclo escolar
                            {{ $ciclo_escolar->inicio_anio ?? '' }}-{{ $ciclo_escolar->fin_anio ?? '' }}
                        </p>
                    </td>

                    <td class="logo-der">
                        @if (!empty($logo_derecho))
                            <img src="{{ $logo_derecho }}" alt="Logo derecho">
                        @endif
                    </td>
                </tr>
            </table>

            <div class="franja-grupo">
                {{ $tituloGrupo }}

                @if ($profesorTitular)
                    · PROFESOR(A):
                    {{ mb_strtoupper($profesorTitular, 'UTF-8') }}
                @endif
            </div>
        </div>

        <table class="tabla-horario">
            <thead>
                <tr>
                    <th class="th-grado" style="width: 78px;">
                        Grado
                    </th>

                    <th class="th-horario" style="width: 84px;">
                        Horario
                    </th>

                    @foreach ($diasOrdenados as $dia)
                        <th class="th-dia {{ $claseDia($dia) }}">
                            {{ mb_strtoupper($dia->dia ?? ($dia->nombre ?? 'DÍA'), 'UTF-8') }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($horasOrdenadas as $hora)
                    <tr>
                        @if ($loop->first)
                            <td class="columna-grado" rowspan="{{ $horasOrdenadas->count() }}">
                                <div>
                                    {{ $nombreNivel }}
                                </div>

                                <div style="margin-top: 5px;">
                                    {{ $nombreGrado }}° {{ $nombreGrupo }}
                                </div>

                                @if (!empty($imagen_nivel))
                                    <div class="imagen-nivel">
                                        <img src="{{ $imagen_nivel }}" alt="Imagen del nivel">
                                    </div>
                                @endif
                            </td>
                        @endif

                        <td class="columna-hora">
                            @php
                                $horaInicio = !empty($hora->hora_inicio)
                                    ? \Carbon\Carbon::parse($hora->hora_inicio)->format('g:i a')
                                    : '';

                                $horaFin = !empty($hora->hora_fin)
                                    ? \Carbon\Carbon::parse($hora->hora_fin)->format('g:i a')
                                    : '';
                            @endphp

                            {{ $horaInicio }}-{{ $horaFin }}
                        </td>

                        @foreach ($diasOrdenados as $dia)
                            @php
                                $claveCelda = $hora->id . '-' . $dia->id;

                                $registro = $horarioPorCelda->get($claveCelda);

                                $talleresCelda = collect($talleresPorCelda->get($claveCelda, collect()))
                                    ->unique('taller_sesion_id')
                                    ->values();

                                $asignacion = $registro?->asignacionMateria;
                                $materia = $asignacion?->materia;

                                $textoMateria = trim((string) ($materia?->materia ?? ''));
                                $calificable = (int) ($materia?->calificable ?? 0);
                                $extra = (int) ($materia?->extra ?? 0);
                                $receso = (int) ($materia?->receso ?? 0);

                                $hayMateria = $textoMateria !== '';
                                $hayTalleres = $talleresCelda->isNotEmpty();
                                $esReceso = $receso === 1;

                                $claseCelda = 'celda-materia';

                                if ($hayTalleres) {
                                    $claseCelda = 'celda-taller';
                                } elseif ($esReceso) {
                                    $claseCelda = 'celda-receso';
                                } elseif (!empty($esPrimaria) && $calificable === 0) {
                                    $claseCelda = 'celda-no-calificable';
                                } elseif ($extra === 1 && $calificable === 1) {
                                    $claseCelda = 'celda-extra';
                                }
                            @endphp

                            <td class="{{ $claseCelda }}">
                                @if ($hayMateria)
                                    @if ($hayTalleres)
                                        <div class="actividad-normal">
                                            {{ $esReceso ? mb_strtoupper($textoMateria, 'UTF-8') : $textoMateria }}
                                        </div>
                                    @else
                                        {{ $esReceso ? mb_strtoupper($textoMateria, 'UTF-8') : $textoMateria }}
                                    @endif
                                @endif

                                @foreach ($talleresCelda as $tallerHorario)
                                    @php
                                        $sesionTaller = $tallerHorario?->tallerSesion;
                                    @endphp

                                    <div class="bloque-taller">
                                        <span class="nombre-taller">
                                            {{ $sesionTaller?->taller?->nombre ?? 'Taller' }}
                                        </span>
                                    </div>
                                @endforeach

                                @if (!$hayMateria && !$hayTalleres)
                                    <span class="sin-registro">
                                        ---
                                    </span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $diasOrdenados->count() + 2 }}" style="padding: 16px; text-align: center;">
                            No hay bloques de horario configurados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if (!empty($esPreescolar))
            @if ($docentesPreescolar->isNotEmpty())
                <table class="tabla-docentes">
                    <thead>
                        <tr>
                            <th style="width: 56%;">
                                Materia
                            </th>

                            <th>
                                Docente
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($docentesPreescolar as $item)
                            <tr>
                                <td>
                                    @forelse ($item['materias'] ?? [] as $materiaDocente)
                                        <div class="materia-docente">
                                            {{ mb_strtoupper($materiaDocente, 'UTF-8') }}
                                        </div>
                                    @empty
                                        <span class="sin-registro">
                                            Sin materia
                                        </span>
                                    @endforelse
                                </td>

                                <td class="{{ !empty($item['sin_docente']) ? 'sin-registro' : '' }}">
                                    {{ mb_strtoupper($item['docente'] ?? 'SIN DOCENTE', 'UTF-8') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif ($docentesHorario->isNotEmpty())
            <table class="tabla-docentes">
                <thead>
                    <tr>
                        @if (!empty($esPrimaria))
                            <th style="width: 56%;">
                                Materias extra
                            </th>

                            <th>
                                Docente extra
                            </th>
                        @else
                            <th style="width: 56%;">
                                {{ !empty($esSecundaria) ? 'Materia / taller' : 'Materias' }}
                            </th>

                            <th>
                                Docente
                            </th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach ($docentesHorario as $item)
                        <tr>
                            <td>
                                @forelse ($item['materias'] ?? [] as $materiaDocente)
                                    <div class="materia-docente">
                                        {{ mb_strtoupper($materiaDocente, 'UTF-8') }}
                                    </div>
                                @empty
                                    <span class="sin-registro">
                                        Sin materia
                                    </span>
                                @endforelse
                            </td>

                            <td class="{{ !empty($item['sin_docente']) ? 'sin-registro' : '' }}">
                                {{ mb_strtoupper($item['docente'] ?? 'SIN DOCENTE', 'UTF-8') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        <footer>
            <strong>
                {{ $escuela->nombre ?? 'Centro Universitario Moctezuma' }}
            </strong>

            @if (!empty($nivel->cct))
                - C.C.T. {{ $nivel->cct }}
            @endif

            <br>

            C. {{ $escuela->calle ?? '' }}
            No. {{ $escuela->no_exterior ?? '' }},
            Col. {{ $escuela->colonia ?? '' }},
            C.P. {{ $escuela->codigo_postal ?? '' }},
            {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}

            @if (!empty($escuela->telefono))
                · Tel. {{ $escuela->telefono }}
            @endif

            <br>

            <strong>Fecha de expedición:</strong>
            {{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </footer>
    </div>
</body>

</html>
