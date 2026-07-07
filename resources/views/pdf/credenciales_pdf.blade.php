<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>CREDENCIALES DE ESTUDIANTES</title>

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
            /* background: #000; */
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

        .fotoAlumno {
            position: absolute;
            top: 25px;
            left: 5px;
            width: 2.5cm;
            height: 3cm;
            border: 1px solid #efefef;
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

        .nombreAlumno {
            text-transform: uppercase;
            font-weight: 700;
            font-size: 9.5px;
        }


        .page-break {
            page-break-after: always;
        }

        .nivel {
            font-size: 11px;
            margin-left: 35px;
            margin-bottom: 10px;
            font-weight: 700;
            color: rgb(255, 255, 255);
        }

        .titulo {
            font-size: 11px;
            margin-left: 35px;
            /* margin-top: 50px; */
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

        @foreach ($alumnos as $index => $alumno)
            <div class="bloqueCredencial">

                {{-- Fondo de la credencial --}}
                <img class="credenciales" src="{{ public_path('imagenes/credencial.jpg') }}" alt="Credencial">


                <img class="logo" src="{{ public_path('storage/logos/' . $nivel->logo ?? 'logo.png') }}"
                    alt="Logo del nivel">


                <img class="logo2" src="{{ public_path('storage/logos/' . $nivel->logo ?? 'logo.png') }}"
                    alt="Logo del nivel">



                @if ($alumno->nivel)
                    <span class="cct">
                        C.C.T.{{ $nivel->cct ?? 'CCT no especificado' }}
                    </span>
                @endif



                {{-- Foto del alumno --}}
                @php($fotoDataUri = $alumno->foto_data_uri)
                @if ($fotoDataUri)
                    <img class="fotoAlumno" src="{{ $fotoDataUri }}"
                        alt="Foto del alumno">
                @else
                    <div class="sinFoto">
                        FOTO + SELLO

                    </div>
                @endif

                {{-- Información del alumno --}}
                <div class="info">
                    {{-- Título --}}
                    <span class="titulo">
                        CREDENCIAL DEL ESTUDIANTE
                    </span>
                    <br>

                    <b>Nombre:</b>
                    <span class="nombreAlumno">
                        {{ $alumno->nombre }}
                        {{ $alumno->apellido_paterno }}
                        {{ $alumno->apellido_materno }}
                    </span>
                    <br>

                    <b>Matrícula:</b>
                    {{ $alumno->matricula ?? 'No especificado' }}
                    <br>
                    <b>CURP:</b>
                    {{ $alumno->curp ?? 'No especificado' }}
                    <br>

                    <b>Nivel:</b>
                    {{ $alumno->nivel->nombre ?? 'No especificado' }} <b>Grado:</b>
                    {{ $alumno->grado->nombre ?? 'No especificado' }}°
                    <b>Grupo:</b> "{{ $alumno->grupo?->asignacionGrupo?->nombre ?? 'No especificado' }}"
                    <br>

                    <b>Vigencia:</b> Agosto {{ $cicloEscolar->fin_anio }}


                    @php
                        $nombreDirector = $nivel->director
                            ? mb_strtoupper(
                                $nivel->director->titulo .
                                    ' ' .
                                    $nivel->director->nombre .
                                    ' ' .
                                    $nivel->director->apellido_paterno .
                                    ' ' .
                                    $nivel->director->apellido_materno,
                                'UTF-8',
                            )
                            : 'No especificado';
                    @endphp
                    <br>


                </div>
                <span class="director">{{ $nombreDirector }}<br>FIRMA Y SELLO</span>
            </div>


            @if (($index + 1) % 4 === 0)
                <div class="page-break"></div>
            @endif
        @endforeach

    </div>
</body>

</html>
