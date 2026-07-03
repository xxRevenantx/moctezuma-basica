<!doctype html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ $tituloDocumento }}</title>

    <style>
        @page {
            size: letter landscape;
            margin: 24px 26px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            line-height: 1.3;
            color: #24364b;
            background: #ffffff;
        }

        .header {
            position: relative;
            width: 100%;
            min-height: 86px;
            margin-bottom: 9px;
            padding: 13px 16px 11px;
            border: 1px solid #dbe7ee;
            border-radius: 10px;
            background: #f8fbfc;
        }

        .header-accent {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: #006492;
        }

        .header-accent-green {
            position: absolute;
            top: 6px;
            left: 0;
            width: 100%;
            height: 2px;
            background: #88ac2e;
        }

        .brand-table {
            width: 100%;
            border-collapse: collapse;
        }

        .brand-table td {
            border: 0;
            padding: 0;
            vertical-align: middle;
        }

        .logo-cell {
            width: 190px;
        }

        .logo {
            width: 168px;
            max-height: 50px;
            object-fit: contain;
        }

        .title-cell {
            text-align: center;
            padding: 0 14px !important;
        }

        .document-label {
            margin: 0 0 2px;
            font-size: 7px;
            font-weight: bold;
            letter-spacing: 1.4px;
            color: #88ac2e;
            text-transform: uppercase;
        }

        h1 {
            margin: 0;
            font-size: 17px;
            line-height: 1.15;
            color: #006492;
            text-transform: uppercase;
        }

        .subtitle {
            margin-top: 4px;
            font-size: 9px;
            font-weight: bold;
            color: #334e68;
        }

        .meta-cell {
            width: 176px;
            text-align: right;
            font-size: 7.4px;
            color: #60758a;
        }

        .meta-value {
            margin-top: 2px;
            font-size: 8.2px;
            font-weight: bold;
            color: #24364b;
        }

        .generation-row {
            margin-top: 8px;
            text-align: center;
        }

        .generation-badge {
            display: inline-block;
            margin: 0 2px 2px;
            padding: 3px 8px;
            border: 1px solid #cfe1e9;
            border-radius: 10px;
            background: #ffffff;
            font-size: 7.5px;
            font-weight: bold;
            color: #006492;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 9px;
            border-collapse: separate;
            border-spacing: 5px 0;
        }

        .summary-table td {
            width: 16.66%;
            padding: 7px 8px;
            border: 1px solid #dce7ed;
            border-radius: 7px;
            background: #ffffff;
            text-align: center;
        }

        .summary-number {
            display: block;
            margin-bottom: 1px;
            font-size: 13px;
            font-weight: bold;
            color: #006492;
        }

        .summary-label {
            font-size: 6.8px;
            font-weight: bold;
            letter-spacing: .5px;
            color: #6b7f91;
            text-transform: uppercase;
        }

        .summary-table .active .summary-number {
            color: #5f8518;
        }

        .summary-table .graduates .summary-number {
            color: #6d28d9;
        }

        .summary-table .withdrawals .summary-number {
            color: #c7354f;
        }

        .table-wrap {
            overflow: hidden;
            border: 1px solid #cad9e2;
            border-radius: 8px;
        }

        table.data-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .data-table thead {
            display: table-header-group;
        }

        .data-table tr {
            page-break-inside: avoid;
        }

        .data-table th {
            padding: 6px 4px;
            border-right: 1px solid rgba(255, 255, 255, .18);
            background: #006492;
            color: #ffffff;
            font-size: 6.7px;
            line-height: 1.15;
            letter-spacing: .35px;
            text-align: left;
            text-transform: uppercase;
        }

        .data-table th:first-child,
        .data-table td:first-child {
            text-align: center;
        }

        .data-table td {
            padding: 5px 4px;
            border-top: 1px solid #dde6ec;
            border-right: 1px solid #edf2f5;
            vertical-align: middle;
            overflow-wrap: break-word;
        }

        .data-table tbody tr:nth-child(even) {
            background: #f7fafb;
        }

        .data-table tbody tr:nth-child(odd) {
            background: #ffffff;
        }

        .student-name {
            font-weight: bold;
            color: #172b3f;
        }

        .muted {
            color: #6f8293;
        }

        .status {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 6.6px;
            font-weight: bold;
            white-space: nowrap;
        }

        .status-active,
        .status-reingreso,
        .status-no-promovido {
            border: 1px solid #c9df9a;
            background: #f3f8e8;
            color: #55751a;
        }

        .status-egresado {
            border: 1px solid #d8c8f6;
            background: #f6f1ff;
            color: #6d28d9;
        }

        .status-baja,
        .status-baja-temporal,
        .status-baja-definitiva {
            border: 1px solid #fac6cf;
            background: #fff1f3;
            color: #b4233c;
        }



        .page-number:after {
            content: "Página " counter(page);
        }

        .w-number {
            width: 4%;
        }

        .w-matricula {
            width: 10%;
        }

        .w-alumno {
            width: 21%;
        }

        .w-curp {
            width: 14%;
        }

        .w-generacion {
            width: 10%;
        }

        .w-grade {
            width: 8%;
        }

        .w-semester {
            width: 7%;
        }

        .w-group {
            width: 7%;
        }

        .w-status {
            width: 10%;
        }

        .w-date {
            width: 9%;
        }
    </style>
</head>

<body>
    <div class="footer">
        <span class="footer-left">
            Centro Universitario Moctezuma · Matrícula de {{ $nivel->nombre }}
        </span>
        <span class="footer-right page-number"></span>
    </div>

    <section class="header">
        <div class="header-accent"></div>
        <div class="header-accent-green"></div>

        <table class="brand-table">
            <tr>
                <td class="logo-cell">
                    <img class="logo" src="{{ public_path('imagenes/logo-letra.png') }}"
                        alt="Centro Universitario Moctezuma">
                </td>

                <td class="title-cell">
                    <div class="document-label">Control escolar</div>
                    <h1>Matrícula escolar por generación</h1>
                    <div class="subtitle">Nivel {{ $nivel->nombre }}</div>
                </td>

                <td class="meta-cell">
                    <div>Fecha de emisión</div>
                    <div class="meta-value">{{ now()->format('d/m/Y') }}</div>
                    <div style="margin-top: 5px;">Hora</div>
                    <div class="meta-value">{{ now()->format('H:i') }} h</div>
                </td>
            </tr>
        </table>

        <div class="generation-row">
            @forelse ($etiquetasGeneracion as $etiqueta)
                <span class="generation-badge">Generación {{ $etiqueta }}</span>
            @empty
                <span class="generation-badge">Sin generación asociada</span>
            @endforelse
        </div>
    </section>

    <table class="summary-table">
        <tr>
            <td>
                <span class="summary-number">{{ number_format($resumen['total']) }}</span>
                <span class="summary-label">Total</span>
            </td>
            <td>
                <span class="summary-number">{{ number_format($resumen['hombres']) }}</span>
                <span class="summary-label">Hombres</span>
            </td>
            <td>
                <span class="summary-number">{{ number_format($resumen['mujeres']) }}</span>
                <span class="summary-label">Mujeres</span>
            </td>
            <td class="active">
                <span class="summary-number">{{ number_format($resumen['activos']) }}</span>
                <span class="summary-label">Activos</span>
            </td>
            <td class="graduates">
                <span class="summary-number">{{ number_format($resumen['egresados']) }}</span>
                <span class="summary-label">Egresados</span>
            </td>
            <td class="withdrawals">
                <span class="summary-number">{{ number_format($resumen['bajas']) }}</span>
                <span class="summary-label">Bajas</span>
            </td>
        </tr>
    </table>

    <div class="table-wrap">
        <table class="data-table">
            <thead>
                <tr>
                    <th class="w-number">No.</th>
                    <th class="w-matricula">Matrícula</th>
                    <th class="w-alumno">Alumno</th>
                    <th class="w-curp">CURP</th>
                    <th class="w-generacion">Generación</th>
                    <th class="w-grade">Grado</th>
                    @if ($nivel->slug === 'bachillerato')
                        <th class="w-semester">Semestre</th>
                    @endif
                    <th class="w-group">Grupo</th>
                    <th class="w-status">Estatus</th>
                    <th class="w-date">Ingreso</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($rows as $i => $alumno)
                    @php
                        $estatus = $alumno->estatus ?? 'activo';
                        $estatusClase = str_replace('_', '-', $estatus);
                        $nombreCompleto = trim(
                            collect([$alumno->apellido_paterno, $alumno->apellido_materno, $alumno->nombre])
                                ->filter()
                                ->implode(' '),
                        );
                    @endphp

                    <tr>
                        <td>{{ $i + 1 }}</td>
                        <td><strong>{{ $alumno->matricula ?: '—' }}</strong></td>
                        <td class="student-name">{{ $nombreCompleto ?: 'Sin nombre' }}</td>
                        <td class="muted">{{ $alumno->curp ?: '—' }}</td>
                        <td>{{ $alumno->generacion?->etiqueta ?: '—' }}</td>
                        <td>{{ $alumno->grado?->nombre ?: '—' }}</td>

                        @if ($nivel->slug === 'bachillerato')
                            <td>
                                {{ $alumno->semestre?->numero ? $alumno->semestre->numero . '°' : '—' }}
                            </td>
                        @endif

                        <td>{{ $alumno->grupo?->asignacionGrupo?->nombre ?: '—' }}</td>
                        <td>
                            <span class="status status-{{ $estatusClase }}">
                                {{ ucfirst(str_replace('_', ' ', $estatus)) }}
                            </span>
                        </td>
                        <td>{{ optional($alumno->fecha_inscripcion)->format('d/m/Y') ?: '—' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="{{ $nivel->slug === 'bachillerato' ? 10 : 9 }}">
                            No se encontraron alumnos con los filtros seleccionados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>

</html>
