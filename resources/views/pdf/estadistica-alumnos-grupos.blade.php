<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Desglose SEP · Alumnado y grupos</title>
    <style>
        @page { margin: 18px 20px; }
        body { font-family: DejaVu Sans, sans-serif; color: #2b2430; font-size: 7px; }
        .header { width: 100%; border-collapse: collapse; margin-bottom: 8px; }
        .header td { vertical-align: middle; }
        .logo { width: 52px; max-height: 52px; }
        h1 { color: #7b1738; font-size: 15px; margin: 0 0 3px; }
        .subtitle { color: #555; font-size: 8px; margin: 0; }
        .meta { border: 1px solid #d7dce5; background: #f6f8fb; padding: 6px; margin-bottom: 8px; }
        .meta span { margin-right: 14px; font-weight: bold; }
        table.matrix { width: 100%; border-collapse: collapse; table-layout: fixed; }
        .matrix th, .matrix td { border: 0.6px solid #9a5570; padding: 3px 2px; text-align: center; }
        .matrix th { background: #7b1738; color: white; font-weight: bold; font-size: 6.3px; }
        .matrix td.left { text-align: left; }
        .matrix .subtotal td { background: #f1e8ec; font-weight: bold; }
        .matrix .grand td { background: #f1e8ec; font-weight: bold; }
        .matrix .grand-final td { background: #7b1738; color: white; font-weight: bold; }
        .matrix .total-cell { background: #e6f1f6; color: #006492; font-weight: bold; }
        .matrix .grand-final .total-cell { background: #006492; color: white; }
        .matrix .shaded { background: #d7d9dd; color: #d7d9dd; }
        .section { margin-top: 10px; font-size: 9px; font-weight: bold; color: #7b1738; }
        .groups { width: 45%; border-collapse: collapse; margin-top: 4px; }
        .groups th, .groups td { border: 0.6px solid #aab3bf; padding: 4px; text-align: center; }
        .groups th { background: #006492; color: white; }
        .note { margin-top: 7px; color: #555; line-height: 1.4; }
        .warning { margin-top: 8px; border: 1px solid #d98a9d; background: #fff3f5; padding: 6px; color: #8c2340; }
        .page-break { page-break-before: always; }
    </style>
</head>
<body>
@php($slug = data_get($datos, 'contexto.nivel_slug'))
@php($columnas = data_get($datos, 'columnas', []))
@php($gruposPorGrado = collect(data_get($datos, 'grupos.por_grado', []))->keyBy('grado_id'))

<table class="header">
    <tr>
        <td style="width:65px;">@if($logo)<img src="{{ $logo }}" class="logo">@endif</td>
        <td>
            <h1>DESGLOSE SEP · ALUMNADO Y GRUPOS</h1>
            <p class="subtitle">Apoyo de control escolar para {{ data_get($datos, 'contexto.codigo_formato') }} · {{ data_get($datos, 'contexto.nivel') }}</p>
        </td>
        <td style="width:180px;text-align:right;font-size:8px;font-weight:bold;">CENTRO UNIVERSITARIO MOCTEZUMA</td>
    </tr>
</table>

<div class="meta">
    <span>CCT: {{ data_get($datos, 'contexto.cct') }}</span>
    <span>Ciclo: {{ data_get($datos, 'contexto.ciclo') }}</span>
    <span>Matrícula al: {{ data_get($datos, 'contexto.fecha_corte_texto') }}</span>
    <span>Edad al: {{ data_get($datos, 'contexto.fecha_edad_texto') }}</span>
    <span>Alumnos contabilizados: {{ data_get($datos, 'resumen.contabilizados', 0) }}</span>
</div>

<table class="matrix">
    <thead>
        <tr>
            <th style="width:30px;">Grado</th>
            <th style="width:46px;">Sexo</th>
            @if ($slug !== 'preescolar')<th style="width:64px;">Condición</th>@endif
            @foreach ($columnas as $etiqueta)<th>{{ $etiqueta }}</th>@endforeach
            <th style="width:36px;background:#006492;">Total</th>
            @if ($slug === 'secundaria')<th style="width:36px;background:#006492;">Grupos</th>@endif
        </tr>
    </thead>
    <tbody>
        @foreach (data_get($datos, 'grados', []) as $grado)
            @if ($slug === 'preescolar')
                @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'subtotal' => 'Subtotal'] as $clave => $etiqueta)
                    <tr class="{{ $clave === 'subtotal' ? 'subtotal' : '' }}">
                        <td>{{ $grado['grado'] }}°</td>
                        <td class="left">{{ $etiqueta }}</td>
                        @foreach (array_keys($columnas) as $edad)
                            @php($sombreada = in_array($edad, data_get($grado, 'sombreadas.'.($clave === 'subtotal' ? 'subtotal' : 'simple'), []), true))
                            <td class="{{ $sombreada ? 'shaded' : '' }}">{{ $sombreada ? '' : (data_get($grado, "{$clave}.edades.{$edad}", 0) ?: '') }}</td>
                        @endforeach
                        <td class="total-cell">{{ data_get($grado, "{$clave}.total", 0) }}</td>
                    </tr>
                @endforeach
            @else
                @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres'] as $sexo => $sexoEtiqueta)
                    @foreach (['nuevo_ingreso' => 'Nuevo ingreso', 'repetidores' => 'Repetidores'] as $condicion => $condicionEtiqueta)
                        <tr>
                            <td>{{ $grado['grado'] }}°</td>
                            <td class="left">{{ $sexoEtiqueta }}</td>
                            <td class="left">{{ $condicionEtiqueta }}</td>
                            @foreach (array_keys($columnas) as $edad)
                                @php($sombreada = in_array($edad, data_get($grado, "sombreadas.{$condicion}", []), true))
                                <td class="{{ $sombreada ? 'shaded' : '' }}">{{ $sombreada ? '' : (data_get($grado, "{$sexo}.{$condicion}.edades.{$edad}", 0) ?: '') }}</td>
                            @endforeach
                            <td class="total-cell">{{ data_get($grado, "{$sexo}.{$condicion}.total", 0) }}</td>
                            @if ($slug === 'secundaria')<td></td>@endif
                        </tr>
                    @endforeach
                @endforeach
                <tr class="subtotal">
                    <td>{{ $grado['grado'] }}°</td>
                    <td colspan="2" class="left">Subtotal</td>
                    @foreach (array_keys($columnas) as $edad)
                        @php($sombreada = in_array($edad, data_get($grado, 'sombreadas.subtotal', []), true))
                        <td class="{{ $sombreada ? 'shaded' : '' }}">{{ $sombreada ? '' : (data_get($grado, "subtotal.edades.{$edad}", 0) ?: '') }}</td>
                    @endforeach
                    <td class="total-cell">{{ data_get($grado, 'subtotal.total', 0) }}</td>
                    @if ($slug === 'secundaria')<td class="total-cell">{{ data_get($gruposPorGrado->get($grado['grado_id']), 'total', 0) }}</td>@endif
                </tr>
            @endif
        @endforeach

        @if ($slug === 'preescolar')
            @foreach (['hombres' => 'Hombres', 'mujeres' => 'Mujeres', 'total' => 'Total'] as $claveTotal => $etiquetaTotal)
                <tr class="{{ $claveTotal === 'total' ? 'grand-final' : 'grand' }}">
                    <td>Total</td>
                    <td class="left">{{ $etiquetaTotal }}</td>
                    @foreach (array_keys($columnas) as $edad)
                        <td>{{ data_get($datos, "totales.{$claveTotal}.edades.{$edad}", 0) ?: '' }}</td>
                    @endforeach
                    <td class="total-cell">{{ data_get($datos, "totales.{$claveTotal}.total", 0) }}</td>
                </tr>
            @endforeach
        @else
            @foreach ([
                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                ['sexo' => 'hombres', 'sexo_etiqueta' => 'Hombres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'nuevo_ingreso', 'condicion_etiqueta' => 'Nuevo ingreso'],
                ['sexo' => 'mujeres', 'sexo_etiqueta' => 'Mujeres', 'condicion' => 'repetidores', 'condicion_etiqueta' => 'Repetidores'],
            ] as $filaTotal)
                <tr class="grand">
                    <td>Total</td>
                    <td class="left">{{ $filaTotal['sexo_etiqueta'] }}</td>
                    <td class="left">{{ $filaTotal['condicion_etiqueta'] }}</td>
                    @foreach (array_keys($columnas) as $edad)
                        @php($sombreadaTotal = $filaTotal['condicion'] === 'repetidores' && (($slug === 'primaria' && $edad === 'menos_6') || ($slug === 'secundaria' && $edad === 'menos_12')))
                        <td class="{{ $sombreadaTotal ? 'shaded' : '' }}">{{ $sombreadaTotal ? '' : (data_get($datos, "totales.{$filaTotal['sexo']}.{$filaTotal['condicion']}.edades.{$edad}", 0) ?: '') }}</td>
                    @endforeach
                    <td class="total-cell">{{ data_get($datos, "totales.{$filaTotal['sexo']}.{$filaTotal['condicion']}.total", 0) }}</td>
                    @if ($slug === 'secundaria')<td></td>@endif
                </tr>
            @endforeach
            <tr class="grand-final">
                <td>Total</td>
                <td colspan="2" class="left">Total general</td>
                @foreach (array_keys($columnas) as $edad)
                    <td>{{ data_get($datos, "totales.total.edades.{$edad}", 0) ?: '' }}</td>
                @endforeach
                <td class="total-cell">{{ data_get($datos, 'totales.total.total', 0) }}</td>
                @if ($slug === 'secundaria')<td class="total-cell">{{ data_get($datos, 'grupos.total', 0) }}</td>@endif
            </tr>
        @endif
    </tbody>
</table>

<p class="note"><strong>Áreas grises:</strong> corresponden a celdas sombreadas del formato oficial y no deben utilizarse.</p>

<div class="section">Grupos por grado</div>
<table class="groups">
    <thead><tr><th>Grado</th><th>Grupos</th></tr></thead>
    <tbody>
        @foreach (data_get($datos, 'grupos.por_grado', []) as $grupo)
            <tr><td>{{ $grupo['grado'] }}°</td><td>{{ $grupo['total'] }}</td></tr>
        @endforeach
        <tr><td><strong>Total</strong></td><td><strong>{{ data_get($datos, 'grupos.total', 0) }}</strong></td></tr>
    </tbody>
</table>

<p class="note">
    La matrícula se determina con altas y bajas vigentes al {{ data_get($datos, 'contexto.fecha_corte_texto') }} y la edad se calcula con los años cumplidos al {{ data_get($datos, 'contexto.fecha_edad_texto') }}. El documento es un reporte de apoyo construido con la información registrada en el sistema y debe revisarse antes de la captura oficial del Formato 911.
    @if (data_get($datos, 'contexto.criterio_repetidor')) {{ data_get($datos, 'contexto.criterio_repetidor') }} @endif
</p>

@if (!empty(data_get($datos, 'incidencias', [])))
    <div class="warning">
        <strong>Incidencias para revisión: {{ count(data_get($datos, 'incidencias', [])) }}.</strong>
        Existen registros que no pudieron ubicarse de forma segura en la matriz por falta de fecha de nacimiento, sexo, una edad fuera de las columnas del formato o una combinación que corresponde a un área sombreada oficial. Consulte el archivo Excel o la pantalla del sistema para ver el detalle.
    </div>
@endif
</body>
</html>
