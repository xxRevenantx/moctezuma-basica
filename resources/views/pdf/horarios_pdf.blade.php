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
    @endphp

    <title>Horario escolar de {{ $tituloGrupo }}</title>

    <style>
        @page {
            margin: 18px 22px;
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

        body {
            font-family: 'ARIAL', DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #0f172a;
            margin: 0;
            padding: 0;
        }

        * {
            box-sizing: border-box;
        }

        .pagina {
            width: 100%;
        }

        .encabezado {
            width: 100%;
            margin-bottom: 10px;
        }

        .tabla-encabezado {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-encabezado td {
            border: none;
            vertical-align: middle;
        }

        .logo-izq,
        .logo-der {
            width: 95px;
            text-align: center;
        }

        .logo-izq img,
        .logo-der img {
            max-width: 95px;
            max-height: 85px;
        }

        .centro {
            text-align: center;
            padding: 0 10px;
        }

        .titulo-institucion {
            font-size: 35px;
            font-family: coolvetica;
            color: #5790d9;
            margin: 0;
            line-height: 1.1;
        }

        .linea-titulo {
            height: 2px;
            background: #9aa7b8;
            margin: 4px 0 8px 0;
        }

        .titulo-principal {
            font-size: 16px;
            font-weight: 700;
            color: #000000;
            margin: 0;
            text-transform: uppercase;
        }

        .subtitulo-principal {
            font-size: 16px;
            font-weight: 700;
            color: #000000;
            margin: 2px 0 0 0;
            text-transform: uppercase;
        }

        .franja-grupo {
            margin-top: 8px;
            border-top: 2px solid #3d95c8;
            border-bottom: 2px solid #3d95c8;
            padding: 6px 10px;
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            color: #0869a6;
            text-transform: uppercase;
        }

        .tabla-horario {
            width: 100%;
            border-collapse: separate;
            border-spacing: 3px;
            table-layout: fixed;
            margin-top: 12px;
        }

        .tabla-horario th,
        .tabla-horario td {
            border: none;
            text-align: center;
            vertical-align: middle;
            padding: 8px 6px;
            word-wrap: break-word;
        }

        .th-grado {
            background: #f2616b;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-horario {
            background: #f4943b;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-lunes {
            background: #ef5e72;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-martes {
            background: #8a71b7;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-miercoles {
            background: #36aebc;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-jueves {
            background: #2f89c7;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .th-viernes {
            background: #4caf67;
            color: #ffffff;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .columna-grado {
            background: #24a7dc;
            color: #ffffff;
            font-weight: 700;
            font-size: 14px;
            line-height: 1.4;
            text-transform: uppercase;
        }

        .columna-hora {
            background: #c8d8ac;
            color: #000000;
            font-size: 10px;
            font-weight: 700;
        }

        .celda-materia {
            background: #cfe0ef;
            color: #0f172a;
            font-size: 12px;
            line-height: 13px;
        }

        .celda-no-calificable {
            background: #dbeafe;
            color: #1d4ed8;
            font-size: 12px;
            font-weight: 700;
        }

        .celda-extra {
            background: #ede9fe;
            color: #5b21b6;
            font-size: 12px;
            font-weight: 700;
        }

        .celda-receso {
            background: #f2aa18;
            color: #000000;
            font-size: 14px;
            font-weight: 700;
            line-height: 10px;
            text-transform: uppercase;
        }

        .texto-grado {
            margin-bottom: 12px;
        }

        .imagen-nivel {
            margin-top: 10px;
        }

        .imagen-nivel img {
            width: 74px;
            height: auto;
        }

        .tabla-docentes {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
            table-layout: fixed;
            font-size: 10px;
            page-break-inside: avoid;
        }

        .tabla-docentes th {
            background: #b9d0e2;
            color: #0f172a;
            border: 1px solid #7f96a8;
            padding: 3px;
            text-transform: uppercase;
            font-weight: 700;
        }

        .tabla-docentes td {
            border: 1px solid #7f96a8;
            padding: 2px 7px;
            text-align: center;
            vertical-align: middle;
            line-height: 1.35;
        }

        .sin-registro {
            color: #64748b;
            font-style: italic;
            font-weight: 400;
        }

        footer {
            position: fixed;
            left: 0;
            right: 0;
            bottom: 5px;
            text-align: center;
            font-size: 10px;
            color: #475569;
            border-top: 1px solid #cbd5e1;
            padding-top: 6px;
        }

        footer p {
            margin: 0;
            line-height: 1.25;
        }
    </style>
</head>

<body>
    @php
        use Carbon\Carbon;

        $nombreNivel = mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8');

        $nombreGeneracion = isset($generacion)
            ? ($generacion->anio_ingreso ?? '') . '-' . ($generacion->anio_egreso ?? '')
            : 'GENERACIÓN';

        $nombreGrado = mb_strtoupper($grado->nombre ?? 'GRADO', 'UTF-8');

        $nombreGrupo = mb_strtoupper($grupo->asignacionGrupo->nombre ?? ($grupo->nombre ?? 'GRUPO'), 'UTF-8');

        $tituloGrupo = 'GENERACIÓN: ' . $nombreGeneracion . ' · ' . $nombreGrado . '° GRADO, GRUPO: ' . $nombreGrupo;

        $esPreescolar = (int) ($nivel->id ?? 0) === 1 || ($nivel->slug ?? '') === 'preescolar';

        $esPrimaria = (int) ($nivel->id ?? 0) === 2 || ($nivel->slug ?? '') === 'primaria';

        $esSecundaria = (int) ($nivel->id ?? 0) === 3 || ($nivel->slug ?? '') === 'secundaria';

        $esBachillerato = (int) ($nivel->id ?? 0) === 4 || ($nivel->slug ?? '') === 'bachillerato';

        if ($esBachillerato && isset($semestre) && $semestre) {
            $tituloGrupo .= ' · SEMESTRE: ' . ($semestre->numero ?? ($semestre->nombre ?? ($semestre->semestre ?? '')));
        }

        $profesorTitular = $profesor_titular ?? null;

        /*
         * La colección viene preparada desde el controlador:
         * - Agrupada por docente.
         * - Sin el profesor titular.
         * - Sin recesos.
         * - Con materias sin profesor agrupadas en "SIN DOCENTE".
         */
        $docentesPreescolar = collect($docentes_preescolar ?? []);

        $diasOrdenados = $dias->values();

        $encabezadosPorDia = [
            0 => [
                'texto' => 'LUNES',
                'class' => 'th-lunes',
            ],
            1 => [
                'texto' => 'MARTES',
                'class' => 'th-martes',
            ],
            2 => [
                'texto' => 'MIÉRCOLES',
                'class' => 'th-miercoles',
            ],
            3 => [
                'texto' => 'JUEVES',
                'class' => 'th-jueves',
            ],
            4 => [
                'texto' => 'VIERNES',
                'class' => 'th-viernes',
            ],
        ];

        /*
         * Esta colección se conserva para primaria,
         * secundaria y bachillerato.
         */
        $docentes = collect();

        if (!$esPreescolar) {
            $slugsExcluidosDocentes = ['calculo-mental', 'caligrafia', 'lectura'];

            foreach ($horas as $horaTmp) {
                foreach ($diasOrdenados as $diaTmp) {
                    $registroTmp = $horarioPorCelda->get($horaTmp->id . '-' . $diaTmp->id);

                    $asignacionTmp = $registroTmp?->asignacionMateria;
                    $materiaTmp = $asignacionTmp?->materia;

                    if (!$asignacionTmp || !$materiaTmp) {
                        continue;
                    }

                    $nombreMateriaTmp = $materiaTmp->materia ?? 'Sin materia';

                    $slugMateriaTmp = mb_strtolower(trim($materiaTmp->slug ?? ''), 'UTF-8');

                    $calificableTmp = (int) ($materiaTmp->calificable ?? 0);
                    $extraTmp = (int) ($materiaTmp->extra ?? 0);
                    $recesoTmp = (int) ($materiaTmp->receso ?? 0);
                    $ordenTmp = (int) ($materiaTmp->orden ?? 999999);

                    /*
                     * Los recesos no tienen docente.
                     */
                    if ($recesoTmp === 1) {
                        continue;
                    }

                    if ($esSecundaria || $esBachillerato) {
                        /*
                         * Secundaria y bachillerato muestran
                         * materias calificables.
                         */
                        if ($calificableTmp !== 1) {
                            continue;
                        }
                    } else {
                        /*
                         * Primaria conserva su lógica:
                         * materias extra calificables.
                         */
                        if ($extraTmp !== 1 || $calificableTmp !== 1) {
                            continue;
                        }

                        if (in_array($slugMateriaTmp, $slugsExcluidosDocentes, true)) {
                            continue;
                        }
                    }

                    $profesorTmp = $asignacionTmp->profesor;

                    $nombreProfesorTmp = $profesorTmp
                        ? trim(
                            ($profesorTmp->nombre ?? '') .
                                ' ' .
                                ($profesorTmp->apellido_paterno ?? '') .
                                ' ' .
                                ($profesorTmp->apellido_materno ?? ''),
                        )
                        : 'Sin profesor asignado';

                    $docentes->push([
                        'materia' => $nombreMateriaTmp,
                        'docente' => $nombreProfesorTmp,
                        'slug' => $slugMateriaTmp,
                        'orden' => $ordenTmp,
                        'calificable' => $calificableTmp,
                        'extra' => $extraTmp,
                        'receso' => $recesoTmp,
                    ]);
                }
            }

            $docentes = $docentes
                ->unique(function ($item) {
                    return mb_strtoupper(trim($item['materia'] . '|' . $item['docente']), 'UTF-8');
                })
                ->sortBy([['orden', 'asc'], ['materia', 'asc']])
                ->values();
        }
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
                            HORARIO DE CLASES <br>
                            CICLO ESCOLAR
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
                    <th class="th-grado" style="width: 125px;">
                        GRADO
                    </th>

                    <th class="th-horario" style="width: 135px;">
                        HORARIO
                    </th>

                    @foreach ($diasOrdenados as $index => $dia)
                        @php
                            $nombreDia = $dia->dia ?? ($dia->nombre ?? 'DÍA');

                            $encabezado = $encabezadosPorDia[$index] ?? [
                                'texto' => mb_strtoupper($nombreDia, 'UTF-8'),
                                'class' => 'th-lunes',
                            ];
                        @endphp

                        <th class="{{ $encabezado['class'] }}">
                            {{ $encabezado['texto'] }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody>
                @forelse ($horas as $hora)
                    <tr>
                        @if ($loop->first)
                            <td class="columna-grado" rowspan="{{ $horas->count() }}">
                                @if (!empty($imagen_nivel))
                                    <div class="imagen-nivel">
                                        <img src="{{ $imagen_nivel }}" alt="Imagen del nivel">
                                    </div>
                                @endif
                            </td>
                        @endif

                        <td class="columna-hora">
                            @php
                                $horaInicio = $hora->hora_inicio
                                    ? Carbon::createFromFormat('H:i:s', $hora->hora_inicio)->format('g:ia')
                                    : '';

                                $horaFin = $hora->hora_fin
                                    ? Carbon::createFromFormat('H:i:s', $hora->hora_fin)->format('g:ia')
                                    : '';
                            @endphp

                            {{ $horaInicio }} - {{ $horaFin }}
                        </td>

                        @foreach ($diasOrdenados as $dia)
                            @php
                                $registro = $horarioPorCelda->get($hora->id . '-' . $dia->id);

                                $asignacion = $registro?->asignacionMateria;

                                $materia = $asignacion?->materia;

                                $textoMateria = $materia?->materia ?? null;

                                $calificable = (int) ($materia?->calificable ?? 0);

                                $extra = (int) ($materia?->extra ?? 0);

                                $receso = (int) ($materia?->receso ?? 0);

                                $esReceso = $receso === 1;
                                $claseCelda = 'celda-materia';

                                /*
                                 * En primaria se marca diferente una
                                 * materia que no es calificable.
                                 */
                                if ($esPrimaria && !$esReceso && $calificable === 0) {
                                    $claseCelda = 'celda-no-calificable';
                                }

                                /*
                                 * Las materias extra se distinguen
                                 * visualmente.
                                 */
                                if (!$esReceso && $extra === 1 && $calificable === 1) {
                                    $claseCelda = 'celda-extra';
                                }
                            @endphp

                            @if ($esReceso)
                                <td class="celda-receso">
                                    @if ($textoMateria)
                                        {{ mb_strtoupper($textoMateria, 'UTF-8') }}
                                    @else
                                        <span class="sin-registro">
                                            ---
                                        </span>
                                    @endif
                                </td>
                            @else
                                <td class="{{ $claseCelda }}">
                                    @if ($textoMateria)
                                        {{ $textoMateria }}
                                    @else
                                        <span class="sin-registro">
                                            ---
                                        </span>
                                    @endif
                                </td>
                            @endif
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ $diasOrdenados->count() + 2 }}" style="padding: 18px; text-align: center;">
                            No hay registros de horario para los filtros
                            seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{--
            PREESCOLAR:
            Todos los docentes del horario agrupados por docente,
            excepto el profesor titular del grado y grupo.
        --}}
        @if ($esPreescolar)
            @if ($docentesPreescolar->isNotEmpty())
                <table class="tabla-docentes">
                    <thead>
                        <tr>
                            <th style="width: 58%;">
                                MATERIA
                            </th>

                            <th>
                                DOCENTE
                            </th>
                        </tr>
                    </thead>

                    <tbody>
                        @foreach ($docentesPreescolar as $item)
                            <tr>
                                <td>
                                    {{ mb_strtoupper($item['materias_texto'] ?? 'SIN MATERIA', 'UTF-8') }}
                                </td>

                                <td class="{{ !empty($item['sin_docente']) ? 'sin-registro' : '' }}">
                                    {{ mb_strtoupper($item['docente'] ?? 'SIN DOCENTE', 'UTF-8') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        @elseif ($docentes->isNotEmpty())
            {{--
                Tabla existente para primaria,
                secundaria y bachillerato.
            --}}
            <table class="tabla-docentes">
                <thead>
                    <tr>
                        @if ($esSecundaria || $esBachillerato)
                            <th style="width: 34%;">
                                MATERIA
                            </th>

                            <th>
                                DOCENTE
                            </th>
                        @else
                            <th style="width: 34%;">
                                MATERIA EXTRA
                            </th>

                            <th>
                                DOCENTE EXTRA
                            </th>
                        @endif
                    </tr>
                </thead>

                <tbody>
                    @foreach ($docentes as $item)
                        <tr>
                            <td>
                                {{ mb_strtoupper($item['materia'], 'UTF-8') }}
                            </td>

                            <td>
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
                — C.C.T. {{ $nivel->cct }}
            @endif

            <br>

            C. {{ $escuela->calle ?? '' }}
            No.{{ $escuela->no_exterior ?? '' }},
            Col. {{ $escuela->colonia ?? '' }},
            C.P. {{ $escuela->codigo_postal ?? '' }},
            {{ $escuela->ciudad ?? '' }},
            {{ $escuela->estado ?? '' }}

            @if (!empty($escuela->telefono))
                · Tel. {{ $escuela->telefono }}
            @endif

            <br>

            <strong>Fecha de expedición:</strong>

            {{ Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
        </footer>
    </div>
</body>

</html>
