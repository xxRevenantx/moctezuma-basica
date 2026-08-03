<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Formato de horario de clases</title>

    <style>
        @page {
            margin: 14px 18px 18px 18px;
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
            font-size: 11px;
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
            margin-bottom: 7px;
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
            width: 78px;
            text-align: center;
        }

        .logo-izq img,
        .logo-der img {
            max-width: 72px;
            max-height: 58px;
        }

        .centro {
            padding: 0 12px;
            text-align: center;
        }

        .titulo-institucion {
            margin: 0;
            color: #5790d9;
            font-family: coolvetica, DejaVu Sans, sans-serif;
            font-size: 27px;
            line-height: 1;
        }

        .linea-titulo {
            height: 2px;
            margin: 4px 0 5px;
            background: #94a3b8;
        }

        .titulo-principal,
        .subtitulo-principal {
            margin: 0;
            color: #000000;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .subtitulo-principal {
            font-size: 11px;
        }

        .tabla-datos {
            width: 100%;
            margin-top: 7px;
            border-collapse: separate;
            border-spacing: 2px 0;
            table-layout: fixed;
        }

        .tabla-datos td {
            height: 39px;
            padding: 5px 8px;
            border: 1px solid #9fb8cb;
            background: #f8fbfd;
            text-align: left;
            vertical-align: middle;
        }

        .tabla-datos .dato-generacion {
            width: 20%;
        }

        .tabla-datos .dato-nivel {
            width: 24%;
        }

        .tabla-datos .dato-grupo {
            width: 11%;
            text-align: center;
        }

        .tabla-datos .dato-docente {
            width: 45%;
        }

        .dato-etiqueta {
            display: block;
            margin-bottom: 2px;
            color: #006492;
            font-size: 7px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.35px;
            text-transform: uppercase;
        }

        .dato-valor {
            display: block;
            color: #0f172a;
            font-size: 10px;
            font-weight: 700;
            line-height: 1.15;
            text-transform: uppercase;
        }

        .dato-secundario {
            display: block;
            margin-top: 2px;
            color: #475569;
            font-size: 8px;
            font-weight: 700;
            line-height: 1.1;
            text-transform: uppercase;
        }

        .sin-registro {
            color: #64748b;
            font-style: italic;
            font-weight: 400;
        }

        .tabla-horario {
            width: 100%;
            margin-top: 7px;
            border-collapse: separate;
            border-spacing: 2px;
            table-layout: fixed;
        }

        .tabla-horario th,
        .tabla-horario td {
            border: none;
            padding: 4px;
            text-align: center;
            vertical-align: middle;
        }

        .tabla-horario tr {
            page-break-inside: avoid;
        }

        .th-horario,
        .th-dia {
            height: 25px;
            color: #ffffff;
            font-size: 10px;
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

        .columna-hora {
            width: 94px;
            background: #dce8c7;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            line-height: 1.15;
            white-space: nowrap;
        }

        .celda-vacia {
            height: 40px;
            padding: 2px 7px !important;
            border: 1px solid #bfd2e0 !important;
            background: #eef5fa;
            color: #0f172a;
            font-size: 8px;
            line-height: 1.1;
        }

        .celda-receso {
            height: 32px;
            border: 1px solid #d79408 !important;
            background: #f4ad18;
            color: #111827;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 1.2px;
            text-transform: uppercase;
        }

        .linea-captura {
            height: 12px;
            border-bottom: 1px solid #adc2d1;
        }

        .etiqueta-captura {
            margin-top: 1px;
            color: #526f82;
            font-size: 5px;
            line-height: 1;
            text-align: left;
            text-transform: uppercase;
        }

        .bloque-complementarias {
            margin-top: 8px;
            page-break-inside: avoid;
        }

        .titulo-complementarias {
            padding: 5px 8px;
            border: 1px solid #7f9db2;
            border-bottom: none;
            background: #d8e6ef;
            color: #0f172a;
            font-size: 9px;
            font-weight: 700;
            text-align: center;
            letter-spacing: 0.4px;
            text-transform: uppercase;
        }

        .tabla-complementarias {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
            font-size: 8px;
        }

        .tabla-complementarias th {
            padding: 4px 6px;
            border: 1px solid #8fa8ba;
            background: #edf3f7;
            color: #006492;
            font-size: 7px;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-complementarias td {
            min-height: 25px;
            padding: 5px 7px;
            border: 1px solid #8fa8ba;
            color: #0f172a;
            line-height: 1.15;
            vertical-align: middle;
        }

        .tabla-complementarias .materia {
            width: 19%;
            font-weight: 700;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-complementarias .docente {
            width: 31%;
            text-align: center;
            text-transform: uppercase;
        }

        .tabla-complementarias .celda-sin-contenido {
            background: #f8fafc;
        }

        .mensaje-materias {
            padding: 8px !important;
            background: #fffbeb;
            color: #92400e;
            font-size: 8px;
            font-weight: 700;
            text-align: center;
        }

        /* Ajustes para grupos con más horas o más materias complementarias. */
        .pagina.compacta .titulo-institucion {
            font-size: 24px;
        }

        .pagina.compacta .logo-izq img,
        .pagina.compacta .logo-der img {
            max-width: 64px;
            max-height: 52px;
        }

        .pagina.compacta .tabla-datos td {
            height: 34px;
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .pagina.compacta .celda-vacia {
            height: 34px;
        }

        .pagina.compacta .celda-receso {
            height: 28px;
        }

        .pagina.compacta .linea-captura {
            height: 9px;
        }

        .pagina.compacta .tabla-complementarias td {
            padding-top: 4px;
            padding-bottom: 4px;
        }

        .pagina.muy-compacta .encabezado {
            margin-bottom: 4px;
        }

        .pagina.muy-compacta .titulo-institucion {
            font-size: 21px;
        }

        .pagina.muy-compacta .logo-izq img,
        .pagina.muy-compacta .logo-der img {
            max-width: 56px;
            max-height: 46px;
        }

        .pagina.muy-compacta .titulo-principal {
            font-size: 10px;
        }

        .pagina.muy-compacta .subtitulo-principal {
            font-size: 9px;
        }

        .pagina.muy-compacta .tabla-datos {
            margin-top: 4px;
        }

        .pagina.muy-compacta .tabla-datos td {
            height: 30px;
            padding: 3px 6px;
        }

        .pagina.muy-compacta .dato-valor {
            font-size: 8px;
        }

        .pagina.muy-compacta .tabla-horario {
            margin-top: 4px;
        }

        .pagina.muy-compacta .celda-vacia {
            height: 29px;
        }

        .pagina.muy-compacta .celda-receso {
            height: 25px;
        }

        .pagina.muy-compacta .linea-captura {
            height: 7px;
        }

        .pagina.muy-compacta .etiqueta-captura {
            font-size: 4px;
        }

        .pagina.muy-compacta .bloque-complementarias {
            margin-top: 5px;
        }

        .pagina.muy-compacta .titulo-complementarias,
        .pagina.muy-compacta .tabla-complementarias th,
        .pagina.muy-compacta .tabla-complementarias td {
            padding-top: 3px;
            padding-bottom: 3px;
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
            $recesoHoraIds = collect($pagina['receso_hora_ids'] ?? [])->map(fn($id) => (int) $id);
            $materiasDocentes = collect($pagina['materias_docentes'] ?? [])->values();
            $materiasComplementarias = $materiasDocentes
                ->flatMap(function ($item) {
                    return collect($item['materias'] ?? [])->map(function ($materia) use ($item) {
                        return [
                            'materia' => $materia,
                            'docente' => $item['docente'] ?? 'Sin docente asignado',
                            'sin_docente' => !empty($item['sin_docente']),
                        ];
                    });
                })
                ->values();

            $filasComplementarias = (int) ceil($materiasComplementarias->count() / 2);
            $densidad = $horasOrdenadas->count() + $filasComplementarias;
            $claseDensidad = $densidad >= 15 ? 'muy-compacta' : ($densidad >= 12 ? 'compacta' : '');

            $nombreNivel = mb_strtoupper($nivel->nombre ?? 'NIVEL', 'UTF-8');
            $nombreGeneracion = trim((string) ($pagina['generacion'] ?? ''));

            if ($nombreGeneracion === '') {
                $nombreGeneracion = collect([$grupo->generacion?->anio_ingreso, $grupo->generacion?->anio_egreso])
                    ->filter()
                    ->implode('-');
            }

            if ($nombreGeneracion === '') {
                $nombreGeneracion = 'SIN GENERACIÓN';
            }

            $nombreGrado = mb_strtoupper(trim((string) ($grupo->grado?->nombre ?? 'GRADO')), 'UTF-8');
            $gradoConOrdinal = preg_match('/[°º]/u', $nombreGrado) ? $nombreGrado : $nombreGrado . '°';
            $nombreGrupo = mb_strtoupper(trim((string) ($grupo->asignacionGrupo?->nombre ?? 'GRUPO')), 'UTF-8');
            $nombreTitular = !empty($pagina['titular'])
                ? mb_strtoupper((string) $pagina['titular'], 'UTF-8')
                : 'SIN DOCENTE TITULAR ASIGNADO';

            $tituloComplementarias = match ((string) $nivel->slug) {
                'preescolar', 'primaria' => 'Materias complementarias',
                'secundaria' => 'Materias, talleres y docentes',
                default => 'Materias y docentes',
            };
        @endphp

        <div class="pagina {{ $claseDensidad }}">
            <div class="encabezado">
                <table class="tabla-encabezado">
                    <tr>
                        <td class="logo-izq">
                            @if (!empty($logoIzquierdo))
                                <img src="{{ $logoIzquierdo }}" alt="Logo institucional">
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
                                <img src="{{ $logoDerecho }}" alt="Logo del nivel">
                            @endif
                        </td>
                    </tr>
                </table>

                <table class="tabla-datos">
                    <tr>
                        <td class="dato-generacion">
                            <span class="dato-etiqueta">Generación</span>
                            <span class="dato-valor">{{ $nombreGeneracion }}</span>
                        </td>

                        <td class="dato-nivel">
                            <span class="dato-etiqueta">Nivel y grado</span>
                            <span class="dato-valor">{{ $nombreNivel }} · {{ $gradoConOrdinal }}</span>
                            @if ($grupo->semestre)
                                <span class="dato-secundario">Semestre {{ $grupo->semestre->numero ?? '' }}</span>
                            @endif
                        </td>

                        <td class="dato-grupo">
                            <span class="dato-etiqueta">Grupo</span>
                            <span class="dato-valor">{{ $nombreGrupo }}</span>
                        </td>

                        <td class="dato-docente">
                            <span class="dato-etiqueta">Docente titular</span>
                            <span class="dato-valor {{ empty($pagina['titular']) ? 'sin-registro' : '' }}">
                                {{ $nombreTitular }}
                            </span>
                        </td>
                    </tr>
                </table>
            </div>

            <table class="tabla-horario">
                <thead>
                    <tr>
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
                            <td class="columna-hora">{{ $horaInicio }} - {{ $horaFin }}</td>

                            @if ($esReceso)
                                <td class="celda-receso" colspan="{{ max(1, $diasOrdenados->count()) }}">RECESO</td>
                            @else
                                @foreach ($diasOrdenados as $dia)
                                    <td class="celda-vacia">
                                        @if ($estiloCelda === 'lineas')
                                            <div class="linea-captura"></div>
                                            <div class="linea-captura"></div>
                                        @elseif ($estiloCelda === 'campos')
                                            <div class="etiqueta-captura">Materia</div>
                                            <div class="linea-captura"></div>
                                            <div class="etiqueta-captura">Docente</div>
                                            <div class="linea-captura"></div>
                                        @endif
                                    </td>
                                @endforeach
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $diasOrdenados->count() + 1 }}" style="padding: 16px; text-align: center;">
                                No hay bloques de horario configurados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="bloque-complementarias">
                <div class="titulo-complementarias">{{ $tituloComplementarias }}</div>

                <table class="tabla-complementarias">
                    <thead>
                        <tr>
                            <th class="materia">Materia</th>
                            <th class="docente">Docente</th>
                            <th class="materia">Materia</th>
                            <th class="docente">Docente</th>
                        </tr>
                    </thead>

                    <tbody>
                        @if ($materiasComplementarias->isEmpty())
                            <tr>
                                <td colspan="4" class="mensaje-materias">
                                    {{ $pagina['mensaje_materias'] ?: 'Aún no hay materias asignadas para este grupo.' }}
                                </td>
                            </tr>
                        @else
                            @foreach ($materiasComplementarias->chunk(2) as $parMaterias)
                                @php
                                    $parMaterias = $parMaterias->values();
                                    $primera = $parMaterias->get(0);
                                    $segunda = $parMaterias->get(1);
                                @endphp

                                <tr>
                                    <td class="materia">{{ mb_strtoupper($primera['materia'], 'UTF-8') }}</td>
                                    <td class="docente {{ !empty($primera['sin_docente']) ? 'sin-registro' : '' }}">
                                        {{ mb_strtoupper($primera['docente'], 'UTF-8') }}
                                    </td>

                                    @if ($segunda)
                                        <td class="materia">{{ mb_strtoupper($segunda['materia'], 'UTF-8') }}</td>
                                        <td
                                            class="docente {{ !empty($segunda['sin_docente']) ? 'sin-registro' : '' }}">
                                            {{ mb_strtoupper($segunda['docente'], 'UTF-8') }}
                                        </td>
                                    @else
                                        <td class="materia celda-sin-contenido">&nbsp;</td>
                                        <td class="docente celda-sin-contenido">&nbsp;</td>
                                    @endif
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    @endforeach
</body>

</html>
