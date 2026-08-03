<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de horario de clases</title>

    <style>
        @page {
            margin: 16px 20px 22px 20px;
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
            page-break-after: always;
        }

        .pagina:last-child {
            page-break-after: auto;
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
            vertical-align: middle;
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
            table-layout: fixed;
        }

        .tabla-horario th,
        .tabla-horario td {
            border: none;
            padding: 5px 4px;
            text-align: center;
            vertical-align: middle;
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
            width: 78px;
            background: #f2616b;
        }

        .th-horario {
            width: 84px;
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
            width: 78px;
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
            width: 84px;
            background: #c8d8ac;
            color: #000000;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.25;
        }

        .celda-vacia {
            height: 42px;
            padding: 0 5px !important;
            background: #cfe0ef;
            color: #0f172a;
            font-size: 10px;
            line-height: 1.15;
        }

        .celda-receso {
            height: 42px;
            background: #f2aa18;
            color: #000000;
            font-size: 11px;
            font-weight: 700;
            line-height: 1.15;
            letter-spacing: 0.45px;
            text-transform: uppercase;
        }

        .linea-captura {
            height: 13px;
            border-bottom: 1px solid #9fb8cb;
        }

        .etiqueta-captura {
            margin-top: 2px;
            color: #476579;
            font-size: 6px;
            line-height: 1;
            text-align: left;
            text-transform: uppercase;
        }

        .tabla-docentes {
            width: 100%;
            margin-top: 11px;
            border-collapse: collapse;
            font-size: 11px;
            page-break-inside: avoid;
        }

        .tabla-docentes th {
            border: 1px solid #7f96a8;
            padding: 6px;
            background: #b9d0e2;
            color: #0f172a;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-docentes td {
            border: 1px solid #7f96a8;
            padding: 5px 7px;
            text-align: center;
            vertical-align: middle;
        }

        .materia-docente+.materia-docente {
            margin-top: 2px;
            padding-top: 2px;
            border-top: 1px dotted #cbd5e1;
        }

        .sin-registro {
            color: #64748b;
            font-style: italic;
            font-weight: 400;
        }

        .mensaje-materias {
            padding: 9px 8px !important;
            background: #fffbeb;
            color: #92400e;
            font-size: 10px;
            font-weight: 700;
            text-align: center;
        }

        /* Ajustes automáticos para conservar horario y tabla en una sola hoja. */
        .pagina.compacta .titulo-institucion {
            font-size: 27px;
        }

        .pagina.compacta .logo-izq img,
        .pagina.compacta .logo-der img {
            max-width: 78px;
            max-height: 64px;
        }

        .pagina.compacta .tabla-horario th,
        .pagina.compacta .tabla-horario td {
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .pagina.compacta .celda-vacia,
        .pagina.compacta .celda-receso {
            height: 36px;
        }

        .pagina.compacta .imagen-nivel img {
            width: 56px;
        }

        .pagina.compacta .tabla-docentes {
            margin-top: 8px;
            font-size: 10px;
        }

        .pagina.compacta .tabla-docentes th,
        .pagina.compacta .tabla-docentes td {
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .pagina.muy-compacta .encabezado {
            margin-bottom: 5px;
        }

        .pagina.muy-compacta .titulo-institucion {
            font-size: 24px;
        }

        .pagina.muy-compacta .logo-izq img,
        .pagina.muy-compacta .logo-der img {
            max-width: 70px;
            max-height: 56px;
        }

        .pagina.muy-compacta .franja-grupo {
            margin-top: 4px;
            padding-top: 4px;
            padding-bottom: 4px;
            font-size: 11px;
        }

        .pagina.muy-compacta .tabla-horario {
            margin-top: 6px;
        }

        .pagina.muy-compacta .tabla-horario th,
        .pagina.muy-compacta .tabla-horario td {
            padding-top: 3px;
            padding-bottom: 3px;
        }

        .pagina.muy-compacta .celda-vacia,
        .pagina.muy-compacta .celda-receso {
            height: 31px;
        }

        .pagina.muy-compacta .imagen-nivel img {
            width: 48px;
        }

        .pagina.muy-compacta .tabla-docentes {
            margin-top: 6px;
            font-size: 9px;
        }

        .pagina.muy-compacta .tabla-docentes th,
        .pagina.muy-compacta .tabla-docentes td {
            padding: 3px 5px;
        }
    </style>
</head>

<body>
    @php
        $diasOrdenados = collect($dias ?? [])->values();
        $horasOrdenadas = collect($horas ?? [])->values();

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
    @endphp

    @foreach ($paginas as $pagina)
        @php
            $grupo = $pagina['grupo'];
            $recesoHoraIds = collect($pagina['receso_hora_ids'] ?? [])->map(fn ($id) => (int) $id);
            $materiasDocentes = collect($pagina['materias_docentes'] ?? [])->values();
            $filasVisualesMaterias = $materiasDocentes->sum(
                fn ($fila) => max(1, count($fila['materias'] ?? [])),
            );
            $densidad = $horasOrdenadas->count() + $filasVisualesMaterias;
            $claseDensidad = $densidad >= 19 ? 'muy-compacta' : ($densidad >= 15 ? 'compacta' : '');

            $nombreNivel = mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8');
            $nombreGeneracion = trim((string) ($pagina['generacion'] ?? ''));

            if ($nombreGeneracion === '') {
                $nombreGeneracion = collect([
                    $grupo->generacion?->anio_ingreso,
                    $grupo->generacion?->anio_egreso,
                ])->filter()->implode('-');
            }

            if ($nombreGeneracion === '') {
                $nombreGeneracion = 'SIN GENERACIÓN';
            }

            $nombreGrado = mb_strtoupper(trim((string) ($grupo->grado?->nombre ?? 'GRADO')), 'UTF-8');
            $nombreGrupo = mb_strtoupper(
                trim((string) ($grupo->asignacionGrupo?->nombre ?? 'GRUPO')),
                'UTF-8',
            );

            $tituloGrupo = 'GENERACIÓN: ' . $nombreGeneracion
                . ' · ' . $nombreGrado . '° GRADO, GRUPO: ' . $nombreGrupo;

            if ($grupo->semestre) {
                $tituloGrupo .= ' · SEMESTRE: ' . ($grupo->semestre->numero ?? '');
            }

            if (!empty($pagina['titular'])) {
                $tituloGrupo .= ' · PROFESOR(A): '
                    . mb_strtoupper((string) $pagina['titular'], 'UTF-8');
            }

            $encabezadoMateria = match ((string) $nivel->slug) {
                'primaria' => 'Materias extra',
                'secundaria' => 'Materia / taller',
                default => 'Materia',
            };

            $encabezadoDocente = $nivel->slug === 'primaria' ? 'Docente extra' : 'Docente';
        @endphp

        <div class="pagina {{ $claseDensidad }}">
            <div class="encabezado">
                <table class="tabla-encabezado">
                    <tr>
                        <td class="logo-izq">
                            @if (!empty($logoIzquierdo))
                                <img src="{{ $logoIzquierdo }}" alt="Logo izquierdo">
                            @endif
                        </td>

                        <td class="centro">
                            <p class="titulo-institucion">Centro Universitario Moctezuma</p>
                            <div class="linea-titulo"></div>
                            <p class="titulo-principal">HORARIO DE CLASES</p>
                            <p class="subtitulo-principal">
                                CICLO ESCOLAR {{ $cicloEscolar->inicio_anio }}-{{ $cicloEscolar->fin_anio }}
                            </p>
                        </td>

                        <td class="logo-der">
                            @if (!empty($logoDerecho))
                                <img src="{{ $logoDerecho }}" alt="Logo derecho">
                            @endif
                        </td>
                    </tr>
                </table>

                <div class="franja-grupo">{{ $tituloGrupo }}</div>
            </div>

            <table class="tabla-horario">
                <thead>
                    <tr>
                        <th class="th-grado">Grado</th>
                        <th class="th-horario">Horario</th>

                        @foreach ($diasOrdenados as $dia)
                            <th class="th-dia {{ $claseDia($dia) }}">
                                {{ mb_strtoupper($dia->dia ?? ($dia->nombre ?? 'DÍA'), 'UTF-8') }}
                            </th>
                        @endforeach
                    </tr>
                </thead>

                <tbody>
                    @forelse ($horasOrdenadas as $hora)
                        @php
                            $esReceso = $recesoHoraIds->contains((int) $hora->id);
                            $horaInicio = !empty($hora->hora_inicio)
                                ? \Carbon\Carbon::parse($hora->hora_inicio)->format('g:i a')
                                : '';
                            $horaFin = !empty($hora->hora_fin)
                                ? \Carbon\Carbon::parse($hora->hora_fin)->format('g:i a')
                                : '';
                        @endphp

                        <tr>
                            @if ($loop->first)
                                <td class="columna-grado" rowspan="{{ $horasOrdenadas->count() }}">
                                    <div>{{ $nombreNivel }}</div>
                                    <div style="margin-top: 5px;">{{ $nombreGrado }}° {{ $nombreGrupo }}</div>

                                    @if (!empty($imagenNivel))
                                        <div class="imagen-nivel">
                                            <img src="{{ $imagenNivel }}" alt="Imagen del nivel">
                                        </div>
                                    @endif
                                </td>
                            @endif

                            <td class="columna-hora">{{ $horaInicio }}-{{ $horaFin }}</td>

                            @foreach ($diasOrdenados as $dia)
                                @if ($esReceso)
                                    <td class="celda-receso">RECESO</td>
                                @else
                                    <td class="celda-vacia">
                                        @if ($estiloCelda === 'lineas')
                                            <div class="linea-captura"></div>
                                            <div class="linea-captura"></div>
                                        @elseif ($estiloCelda === 'campos')
                                            <div class="etiqueta-captura">Materia</div>
                                            <div class="linea-captura"></div>
                                            <div class="etiqueta-captura">Profesor</div>
                                            <div class="linea-captura"></div>
                                        @endif
                                    </td>
                                @endif
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

            <table class="tabla-docentes">
                <thead>
                    <tr>
                        <th style="width: 56%;">{{ $encabezadoMateria }}</th>
                        <th>{{ $encabezadoDocente }}</th>
                    </tr>
                </thead>

                <tbody>
                    @if ($materiasDocentes->isEmpty())
                        <tr>
                            <td colspan="2" class="mensaje-materias">
                                {{ $pagina['mensaje_materias'] ?: 'Aún no hay materias asignadas para este grupo.' }}
                            </td>
                        </tr>
                    @else
                        @foreach ($materiasDocentes as $item)
                            <tr>
                                <td>
                                    @forelse ($item['materias'] ?? [] as $materiaDocente)
                                        <div class="materia-docente">
                                            {{ mb_strtoupper($materiaDocente, 'UTF-8') }}
                                        </div>
                                    @empty
                                        <span class="sin-registro">Sin materia</span>
                                    @endforelse
                                </td>

                                <td class="{{ !empty($item['sin_docente']) ? 'sin-registro' : '' }}">
                                    {{ mb_strtoupper($item['docente'] ?? 'SIN DOCENTE ASIGNADO', 'UTF-8') }}
                                </td>
                            </tr>
                        @endforeach
                    @endif
                </tbody>
            </table>
        </div>
    @endforeach
</body>

</html>
