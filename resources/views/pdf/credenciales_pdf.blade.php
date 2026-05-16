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
            /* width: 100%; */
            margin: auto;
            /* padding: 0 0 0 70px; */
        }

        .bloqueCredencial {
            position: relative;
            width: 17cm;
            height: 5.5cm;
            margin: 5px auto;

        }

        .credenciales {
            border: 1px solid #000;
            width: 18cm;
            height: 5.7cm;
            display: block;
        }

        .fotoAlumno {
            position: absolute;
            top: 68px;
            left: 42px;
            width: 82px;
            height: 96px;
            object-fit: cover;
            border: 1px solid #ffffff;
            background: #e5e7eb;
        }

        .sinFoto {
            position: absolute;
            top: 30px;
            left: 20px;
            width: 70px;
            height: 50px;
            border: 1px solid #ffffff;
            background: #e5e7eb;
            color: #666;
            text-align: center;
            font-size: 9px;
            font-weight: 700;
            padding-top: 42px;
        }

        .titulo {
            position: absolute;
            top: 38px;
            left: 150px;
            width: 320px;
            font-size: 10px;
            color: #fff;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info {
            position: absolute;
            top: 10px;
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
        }

        .qr {
            position: absolute;
            top: 70px;
            right: 35px;
            width: 70px;
            height: 70px;
        }

        .page-break {
            page-break-after: always;
        }

        .nivel {
            font-size: 15px;
            margin-left: 70px;
            font-weight: 700;
            color: #000000;
        }
    </style>
</head>

<body>
    <div class="contenedorCredenciales">

        @foreach ($alumnos as $index => $alumno)
            <div class="bloqueCredencial">






                {{-- Fondo de la credencial --}}
                <img class="credenciales" src="{{ public_path('imagenes/credencial.jpg') }}" alt="Credencial">


                {{-- Foto del alumno --}}
                @if (!empty($alumno->foto) && file_exists(public_path('storage/' . $alumno->foto)))
                    <img class="fotoAlumno" src="{{ public_path('storage/' . $alumno->foto) }}" alt="Foto del alumno">
                @else
                    <div class="sinFoto">
                        SIN FOTO
                    </div>
                @endif



                {{-- Título --}}
                <h1 class="titulo">
                    CREDENCIAL DEL ESTUDIANTE
                </h1>

                {{-- Información del alumno --}}
                <div class="info">
                    <p class="nivel">
                        {{ mb_strtoupper($alumno->nivel->nombre ?? 'No especificado') }}
                    </p>
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


                    @if ($alumno->semestre)
                        <b>Semestre:</b>
                        {{ $alumno->semestre->nombre ?? ($alumno->semestre->semestre ?? 'Semestre ' . $alumno->semestre->id) }}
                        <br>
                    @endif


                </div>
            </div>

            @if (($index + 1) % 4 === 0)
                <div class="page-break"></div>
            @endif
        @endforeach

    </div>
</body>

</html>
