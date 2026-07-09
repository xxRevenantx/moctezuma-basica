<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $titulo }}</title>
    <style>
        @page { margin: 24px 26px 30px; }
        * { box-sizing: border-box; }
        body { font-family: DejaVu Sans, sans-serif; color: #1e293b; font-size: 8px; margin: 0; }
        .header { border-bottom: 3px solid #006492; padding-bottom: 8px; margin-bottom: 10px; }
        .brand { color: #006492; font-size: 8px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
        h1 { margin: 3px 0 0; font-size: 16px; color: #0f172a; }
        .meta { margin-top: 3px; color: #64748b; font-size: 7px; }
        table { width: 100%; border-collapse: collapse; table-layout: auto; }
        th { background: #006492; color: white; padding: 5px 3px; border: 1px solid #004d70; font-size: 6.7px; text-transform: uppercase; }
        td { padding: 4px 3px; border: 1px solid #cbd5e1; vertical-align: top; word-wrap: break-word; }
        tbody tr:nth-child(even) td { background: #f8fafc; }
        .footer { position: fixed; bottom: -18px; left: 0; right: 0; border-top: 1px solid #88AC2E; padding-top: 4px; color: #64748b; font-size: 6px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Centro Universitario Moctezuma</div>
        <h1>{{ $titulo }}</h1>
        <div class="meta">Documento independiente generado el {{ now()->format('d/m/Y H:i') }}. No reemplaza ni modifica los formatos PDF existentes.</div>
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($encabezados as $encabezado)
                    <th>{{ $encabezado }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($filas as $fila)
                <tr>
                    @foreach ($fila as $valor)
                        <td>{{ $valor }}</td>
                    @endforeach
                </tr>
            @empty
                <tr><td colspan="{{ count($encabezados) }}" style="text-align:center;padding:20px;">Sin información para mostrar.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer">Centro Universitario Moctezuma · Plantilla de personal</div>
</body>
</html>
