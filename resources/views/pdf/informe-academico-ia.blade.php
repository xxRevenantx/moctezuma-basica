<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 32px 37px 52px; }
        body { font-family: DejaVu Sans, sans-serif; color: #263347; font-size: 9.5px; line-height: 1.55; }
        .cabecera { width: 100%; border-bottom: 3px solid #006492; padding-bottom: 13px; margin-bottom: 19px; }
        .cabecera td { vertical-align: middle; }
        .logo { width: 160px; height: auto; }
        .titulo-institucional { text-align: right; color: #006492; font-size: 17px; font-weight: bold; }
        .subtitulo-institucional { text-align: right; font-size: 8px; color: #64748b; }
        h1 { font-size: 17px; color: #172033; line-height: 1.4; margin: 0 0 10px; }
        h2 { color: #006492; font-size: 11px; margin: 15px 0 7px; page-break-after: avoid; }
        .metas { font-size: 8px; color: #475569; margin-bottom: 13px; }
        .prioridad { font-weight: bold; color: #92400e; }
        .datos { width: 100%; border-collapse: collapse; margin-bottom: 13px; }
        .datos td { background: #f4f8fc; border: 1px solid #dde6ee; padding: 7px 9px; width: 50%; vertical-align: top; }
        .dato-etiqueta { font-size: 7px; font-weight: bold; color: #64748b; text-transform: uppercase; }
        .dato-valor { font-size: 9px; color: #1e293b; margin-top: 2px; }
        .resumen { padding: 13px; background: #eef7fc; border-left: 4px solid #006492; margin-bottom: 12px; }
        .texto { white-space: pre-line; text-align: justify; }
        .lista { margin: 0 0 7px; padding: 0 0 0 17px; }
        .lista li { margin: 0 0 6px; padding-left: 2px; page-break-inside: avoid; }
        .aviso { border-top: 1px solid #cbd5e1; margin-top: 17px; padding-top: 9px; font-size: 8px; color: #64748b; }
        .pie { position: fixed; bottom: -31px; left: 0; right: 0; text-align: center; color: #64748b; font-size: 7.5px; border-top: 1px solid #dbe4eb; padding-top: 6px; }
    </style>
</head>
<body>
    <table class="cabecera"><tr>
        <td style="width: 43%">
            @if ($logo)<img class="logo" src="{{ $logo }}" alt="Centro Universitario Moctezuma">@else
                <strong>Centro Universitario Moctezuma</strong>
            @endif
        </td>
        <td style="width: 57%">
            <div class="titulo-institucional">INFORME ACADÉMICO</div>
            <div class="subtitulo-institucional">Redactado con apoyo de inteligencia artificial</div>
        </td>
    </tr></table>

    <h1>{{ $titulo }}</h1>
    <div class="metas">
        <strong>Tipo:</strong> {{ $tipoInforme }} &nbsp; | &nbsp;
        <span class="prioridad">Prioridad: {{ ucfirst($prioridad) }}</span> &nbsp; | &nbsp;
        <strong>Generado:</strong> {{ $generadoEn }}
    </div>

    @if (count($contextoEtiquetado) > 0)
        <table class="datos">
            @foreach (array_chunk($contextoEtiquetado, 2) as $fila)
                <tr>
                    @foreach ($fila as $item)
                        <td>
                            <div class="dato-etiqueta">{{ $item[0] }}</div>
                            <div class="dato-valor">{{ $item[1] }}</div>
                        </td>
                    @endforeach
                    @if (count($fila) === 1)<td></td>@endif
                </tr>
            @endforeach
        </table>
    @endif

    <h2>Resumen ejecutivo</h2>
    <div class="resumen texto">{{ $informe['resumen_ejecutivo'] ?? '' }}</div>

    @foreach (['fortalezas' => 'Fortalezas', 'areas_atencion' => 'Áreas de atención', 'recomendaciones' => 'Recomendaciones'] as $clave => $etiqueta)
        <h2>{{ $etiqueta }}</h2>
        @if (!empty($informe[$clave]) && is_array($informe[$clave]))
            <ul class="lista">
                @foreach ($informe[$clave] as $item)
                    <li>{{ $item }}</li>
                @endforeach
            </ul>
        @else
            <p>No se señalaron elementos.</p>
        @endif
    @endforeach

    <h2>Diagnóstico pedagógico</h2>
    <div class="texto">{{ $informe['diagnostico_pedagogico'] ?? '' }}</div>

    <div class="aviso"><strong>Aviso:</strong> {{ $informe['aviso'] ?? '' }}</div>
    <div class="pie">Centro Universitario Moctezuma &nbsp; | &nbsp; Informe orientativo sujeto a revisión institucional</div>
</body>
</html>
