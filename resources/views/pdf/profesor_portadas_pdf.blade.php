<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Portadas de profesores</title>

    <style>
        @page {
            margin: 0;
            size: letter portrait;
        }

        * {
            box-sizing: border-box;
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/ARIAL.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'ARIAL';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/ARIALBD.ttf') }}') format('truetype');
        }

        html,
        body {
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'ARIAL', sans-serif;
            text-transform: uppercase;

        }

        .page {
            position: relative;
            width: 216mm;
            height: 279.4mm;
            overflow: hidden;
            page-break-after: always;
        }

        .page:last-child {
            page-break-after: auto;
        }

        .fondo {
            position: absolute;
            top: 0;
            left: 0;

            width: 216mm;
            height: 279.4mm;

            object-fit: cover;

            z-index: 1;
        }

        /*
        |--------------------------------------------------------------------------
        | CAMPOS
        |--------------------------------------------------------------------------
        */

        .campo {
            position: absolute;

            left: 0;
            width: 100%;

            z-index: 3;

            padding-left: 0mm;
            padding-right: 18mm;

            text-align: center;

            line-height: 1.35;
            font-size: 20px;
        }

        /*
        |--------------------------------------------------------------------------
        | ETIQUETAS
        |--------------------------------------------------------------------------
        */

        .etiqueta {
            color: #215b82;
            font-weight: 700;
        }

        /*
        |--------------------------------------------------------------------------
        | VALORES
        |--------------------------------------------------------------------------
        */

        .valor {
            color: #2c323a;
            font-weight: 400;
        }

        /*
        |--------------------------------------------------------------------------
        | ALERTAS
        |--------------------------------------------------------------------------
        */

        .alertas {
            position: absolute;

            left: 8%;
            right: 8%;
            bottom: 8%;

            z-index: 4;

            padding: 8px 10px;

            border: 1px solid #fcd34d;
            border-radius: 8px;

            background: #fffbeb;
            color: #92400e;



            text-align: center;
        }

        .alerta-item {
            margin-bottom: 3px;
        }

        .alerta-item:last-child {
            margin-bottom: 0;
        }
    </style>
</head>

<body>

    @php

        $camposConfig = $configuracion['campos'] ?? [];

        /*
        |--------------------------------------------------------------------------
        | CAMPOS DE LA PORTADA
        |--------------------------------------------------------------------------
        */

        $mapaCampos = [
            'nombre_cargo' => fn($datos) => [
                'label' => 'PROFESOR(A):',
                'value' => $datos['nombre'] ?? '',
            ],

            'rfc' => fn($datos) => [
                'label' => 'RFC:',
                'value' => $datos['rfc'] ?? 'S/C',
            ],

            'curp' => fn($datos) => [
                'label' => 'CURP:',
                'value' => $datos['curp'] ?? 'S/C',
            ],

            /*
            |--------------------------------------------------------------------------
            | CLAVE PRESUPUESTAL
            |--------------------------------------------------------------------------
            |
            | Siempre se mostrará S/C.
            |
            */

            'clave_presupuestal' => fn($datos) => [
                'label' => 'CLAVE PRESUPUESTAL:',
                'value' => 'S/C',
            ],

            'telefono' => fn($datos) => [
                'label' => 'TEL.:',
                'value' => $datos['telefono'] ?? 'S/C',
            ],

            'escuela' => fn($datos) => [
                'label' => 'NOMBRE DE LA ESCUELA:',
                'value' => 'CENTRO UNIVERSITARIO MOCTEZUMA',
            ],

            'cct' => fn($datos) => [
                'label' => 'C.C.T.:',
                'value' => $datos['cct'] ?? 'S/C',
            ],

            'lugar' => fn($datos) => [
                'label' => 'LUGAR:',
                'value' => $datos['lugar'] ?? 'CD. ALTAMIRANO, GRO.',
            ],
        ];

    @endphp


    @foreach ($documentos as $documento)
        @php

            $datos = $documento['datos'];

            /*
            |--------------------------------------------------------------------------
            | ALERTAS
            |--------------------------------------------------------------------------
            |
            | No mostramos la advertencia de clave presupuestal porque
            | administrativamente el documento mostrará S/C.
            |
            */

            $alertasVisibles = collect($datos['alertas'] ?? [])
                ->reject(function ($alerta) {
                    return str_contains(mb_strtolower((string) $alerta), 'clave presupuestal');
                })
                ->values();

        @endphp


        <div class="page">

            {{-- ============================================================= --}}
            {{-- FONDO --}}
            {{-- ============================================================= --}}

            @if ($fondo_data_uri)
                <img class="fondo" src="{{ $fondo_data_uri }}" alt="Portada institucional">
            @endif



            {{-- ============================================================= --}}
            {{-- DATOS --}}
            {{-- ============================================================= --}}

            @foreach ($camposConfig as $clave => $cfg)
                @continue(empty($cfg['visible']) || !isset($mapaCampos[$clave]))

                @php
                    $campo = $mapaCampos[$clave]($datos);
                @endphp


                <div class="campo" style="
                        top: {{ $cfg['top'] }}%;
                    ">

                    <span class="etiqueta">
                        {{ $campo['label'] }}
                    </span>

                    <span class="valor">
                        {{ $campo['value'] }}
                    </span>

                </div>
            @endforeach



            {{-- ============================================================= --}}
            {{-- ALERTAS --}}
            {{-- ============================================================= --}}

            @if ($alertasVisibles->isNotEmpty())
                <div class="alertas">

                    @foreach ($alertasVisibles as $alerta)
                        <div class="alerta-item">
                            {{ $alerta }}
                        </div>
                    @endforeach
                    <div>
                        Francisco I. Madero Ote. No. 800. Col. Esquipulas. Cd. Altamirano, Gro.
                    </div>
                </div>
            @endif

        </div>
    @endforeach



</body>

</html>
