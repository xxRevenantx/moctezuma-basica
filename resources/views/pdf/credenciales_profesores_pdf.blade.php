<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CREDENCIALES DE PROFESORES</title>

    <style>
        @page {
            margin: 60px 30px 30px 30px;
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/calibri/calibri.ttf') }}') format('truetype');
        }

        @font-face {
            font-family: 'calibri';
            font-style: normal;
            font-weight: 700;
            src: url('{{ storage_path('fonts/calibri/calibri-bold.ttf') }}') format('truetype');
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: 'calibri', sans-serif;
        }

        .contenedorCredenciales {
            width: 100%;
            margin: auto;
        }

        .bloqueCredencial {
            position: relative;
            width: 18cm;
            height: 5.5cm;
            margin: auto;
            padding: 5px 0;
        }

        .credenciales {
            border: 1px solid #000;
            width: 18cm;
            height: 5.5cm;
            display: block;
        }

        .fotoProfesor {
            position: absolute;
            top: 25px;
            left: 5px;
            width: 2.5cm;
            height: 3cm;
            border: 1px solid #efefef;
            object-fit: cover;
        }

        .sinFoto {
            position: absolute;
            top: 30px;
            left: 15px;
            width: 1.8cm;
            height: 1.3cm;
            border: 1px solid #b8b8b8;
            color: #bebebe;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            padding-top: 42px;
        }

        .info {
            position: absolute;
            top: 29px;
            left: 120px;
            width: 325px;
            font-size: 11px;
            line-height: 11px;
            color: #111;
        }

        .info b {
            font-weight: 700;
        }

        .nombreProfesor {
            text-transform: uppercase;
            font-weight: 700;
            font-size: 9.5px;
        }

        .page-break {
            page-break-after: always;
        }

        .titulo {
            font-size: 11px;
            margin-left: 35px;
            font-weight: 700;
            color: rgb(255, 255, 255);
        }

        .cct {
            position: absolute;
            top: -30px;
            left: 126px;
            font-size: 10px;
            font-weight: 700;
            color: rgb(255, 255, 255);
        }

        .director {
            position: absolute;
            text-align: center;
            line-height: 9px;
            top: 90px;
            bottom: 10px;
            left: 470px;
            font-size: 10px;
            color: rgb(0, 0, 0);
        }

        .logo {
            position: absolute;
            top: -30px;
            left: 270px;
            width: 40px;
        }

        .logo2 {
            position: absolute;
            top: -30px;
            right: 20px;
            width: 40px;
        }
    </style>
</head>

<body>
    <div class="contenedorCredenciales">

        @foreach ($personas as $index => $persona)
            @php
                $nombreCompleto = trim(
                    ($persona->titulo ? $persona->titulo . ' ' : '') .
                        ($persona->nombre ?? '') .
                        ' ' .
                        ($persona->apellido_paterno ?? '') .
                        ' ' .
                        ($persona->apellido_materno ?? ''),
                );

                $fotoExiste = !empty($persona->foto) && file_exists(public_path('storage/' . $persona->foto));

                $logoPrincipal = public_path('imagenes/logo.png');

                $rolPrincipal =
                    $persona->personaRoles
                        ->map(fn($personaRole) => $personaRole->rolePersona?->nombre)
                        ->filter()
                        ->first() ??
                    ($cargo ?? 'PROFESOR');

                $nombreDirector = 'DIRECTOR(A)';
            @endphp

            <div class="bloqueCredencial">

                {{-- Fondo de la credencial --}}
                <img class="credenciales" src="{{ public_path('imagenes/credencial.jpg') }}" alt="Credencial">

                {{-- Logos --}}
                @if (file_exists($logoPrincipal))
                    <img class="logo" src="{{ $logoPrincipal }}" alt="Logo">

                    <img class="logo2" src="{{ $logoPrincipal }}" alt="Logo">
                @endif

                {{-- CCT --}}
                <span class="cct">
                    C.C.T. {{ $cct ?: 'CCT no especificado' }}
                </span>

                {{-- Foto del profesor --}}
                @if ($fotoExiste)
                    <img class="fotoProfesor" src="{{ public_path('storage/' . $persona->foto) }}"
                        alt="Foto del profesor">
                @else
                    <div class="sinFoto">
                        FOTO + SELLO
                    </div>
                @endif

                {{-- Información del profesor --}}
                <div class="info">
                    <span class="titulo">
                        CREDENCIAL DEL PROFESOR
                    </span>
                    <br>

                    <b>Nombre:</b>
                    <span class="nombreProfesor">
                        {{ $nombreCompleto ?: 'No especificado' }}
                    </span>
                    <br>

                    <b>Cargo:</b>
                    {{ $rolPrincipal ?: $cargo ?? 'PROFESOR' }}
                    <br>

                    <b>CURP:</b>
                    {{ $persona->curp ?? 'No especificado' }}
                    <br>

                    <b>RFC:</b>
                    {{ $persona->rfc ?? 'No especificado' }}
                    <br>

                    <b>Correo:</b>
                    {{ $persona->correo ?? 'No especificado' }}
                    <br>

                    <b>Teléfono:</b>
                    {{ $persona->telefono_movil ?? ($persona->telefono_fijo ?? 'No especificado') }}
                    <br>

                    <b>Vigencia:</b>
                    {{ $vigencia ?? 'No especificada' }}

                    <br>
                </div>

                <span class="director">
                    {{ $nombreDirector }}
                    <br>
                    FIRMA Y SELLO
                </span>
            </div>

            @if (($index + 1) % 4 === 0)
                <div class="page-break"></div>
            @endif
        @endforeach

    </div>
</body>

</html>
