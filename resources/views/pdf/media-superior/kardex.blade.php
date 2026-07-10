<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Kardex del alumno</title>
    <style>
        @page {
            margin: 8mm 10mm;
        }

        body {
            font-family: Arial, sans-serif;
            font-size: 7px;
            color: #111;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .header td {
            border: 0;
            vertical-align: middle;
        }

        .logo-seg {
            width: 130px;
            max-height: 52px;
            object-fit: contain;
        }

        .logo-cum {
            width: 150px;
            max-height: 50px;
            object-fit: contain;
        }

        .title {
            text-align: center;
            font-size: 11px;
            font-weight: bold;
        }

        .student {
            margin-top: 5px;
            border: 1px solid #111;
        }

        .student td {
            padding: 3px 5px;
            border: 0;
        }

        .semester-title {
            margin-top: 5px;
            padding: 2px 3px;
            font-weight: bold;
            border-bottom: 1px solid #111;
        }

        .subjects {
            table-layout: fixed;
        }

        .subjects th,
        .subjects td {
            border: .6px solid #111;
            padding: 2px 3px;
            text-align: center;
        }

        .subjects .name {
            text-align: left;
        }

        .extra-title {
            margin-top: 3px;
            color: #8a5a00;
            font-weight: bold;
        }

        .summary {
            margin-top: 5px;
            text-align: right;
            font-size: 8px;
        }

        .regular {
            width: 42%;
        }

        .signatures {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            page-break-inside: avoid;
        }

        .signatures td {
            width: 50%;
            border: 0;
            padding: 0 18px;
            text-align: center;
            vertical-align: bottom;
        }

        .signature-box {
            position: relative;
            height: 102px;
            overflow: hidden;
        }

        .signature-seal {
            position: absolute;
            z-index: 1;
            object-fit: contain;
        }

        .signature-seal.left {
            width: 92px;
            height: 92px;
            top: 0;
            left: 50%;
            margin-left: -46px;
        }

        .signature-seal.right {
            width: 142px;
            height: 88px;
            top: 1px;
            left: 50%;
            margin-left: -71px;
        }

        .signature-data {
            position: absolute;
            z-index: 2;
            left: 0;
            right: 0;
            bottom: 0;
            text-align: center;
        }

        .signature-name {
            font-size: 7px;
            font-weight: bold;
            text-transform: uppercase;
            line-height: 1.15;
        }

        .signature-role {
            font-size: 6px;
            line-height: 1.2;
        }
    </style>
</head>

<body>
    @php
        $firmanteDirector = data_get($institucional, 'firmantes.director', []);
        $firmanteJefe = data_get($institucional, 'firmantes.jefe_registro', []);
        $selloBachillerato = public_path('imagenes/sello_bachillerato.png');
        $selloChilpancingo = public_path('imagenes/sello_chilpancingo.jpg');
    @endphp
    <table class="header">
        <tr>
            <td style="width:25%;"><img class="logo-seg" src="{{ $institucional['logo_seg'] }}"></td>
            <td class="title" style="width:50%;">CERTIFICACIÓN DE ESTUDIOS<br>KARDEX DEL ALUMNO</td>
            <td style="width:25%;text-align:right;"><img class="logo-cum" src="{{ $institucional['logo_plantel'] }}"></td>
        </tr>
    </table>
    <table class="student">
        <tr>
            <td><strong>{{ mb_strtoupper($institucional['plantel']) }}</strong></td>
            <td><strong>NÚM. DE ACUERDO:</strong> {{ $institucional['numero_acuerdo'] ?: 'PENDIENTE' }}</td>
            <td><strong>C.C.T.:</strong> {{ $institucional['cct'] }}</td>
        </tr>
        <tr>
            <td><strong>DIRECCIÓN:</strong> {{ mb_strtoupper($institucional['direccion']) }}</td>
            <td><strong>MUNICIPIO:</strong> {{ mb_strtoupper($institucional['municipio']) }}</td>
            <td><strong>MODALIDAD:</strong> {{ mb_strtoupper($institucional['modalidad']) }}</td>
        </tr>
        <tr>
            <td><strong>PRIMER APELLIDO:</strong> {{ mb_strtoupper($alumno->apellido_paterno) }}</td>
            <td><strong>SEGUNDO APELLIDO:</strong> {{ mb_strtoupper($alumno->apellido_materno) }}</td>
            <td><strong>NOMBRE(S):</strong> {{ mb_strtoupper($alumno->nombre) }}</td>
        </tr>
        <tr>
            <td><strong>CURP:</strong> {{ $alumno->curp }}</td>
            <td><strong>MATRÍCULA:</strong> {{ $alumno->matricula }}</td>
            <td><strong>GENERACIÓN:</strong> {{ $alumno->generacion?->etiqueta }}</td>
        </tr>
    </table>

    @forelse($semestres as $semestre)
        <div class="semester-title">{{ $semestre['numero'] }}° SEMESTRE &nbsp; | &nbsp; CICLO ESCOLAR:
            {{ $semestre['ciclo']?->nombre ?: '—' }}</div>
        <table class="subjects">
            <thead>
                <tr>
                    <th style="width:15%;">CLAVE</th>
                    <th>ASIGNATURA</th>
                    <th style="width:12%;">CAL. FINAL</th>
                    <th style="width:12%;">% ASIST.</th>
                    <th class="regular">PERIODOS DE REGULARIZACIÓN<br><span style="font-weight:normal;">FECHA / CALIF.
                            &nbsp;&nbsp; FECHA / CALIF. &nbsp;&nbsp; FECHA / CALIF.</span></th>
                </tr>
            </thead>
            <tbody>
                @foreach ($semestre['oficiales'] as $materia)
                    <tr>
                        <td>{{ $materia['clave'] }}</td>
                        <td class="name">{{ mb_strtoupper($materia['nombre']) }}</td>
                        <td>{{ $materia['valor'] }}</td>
                        <td>{{ $materia['asistencia'] !== null ? number_format((float) $materia['asistencia'], 0) . '%' : '' }}
                        </td>
                        <td></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        @if ($institucional['mostrar_materias_extra'] && $semestre['extras']->isNotEmpty())
            <div class="extra-title">MATERIAS EXTRA INFORMATIVAS · NO INTERVIENEN EN EL PROMEDIO</div>
            <table class="subjects">
                <thead>
                    <tr>
                        <th style="width:15%;">CLAVE</th>
                        <th>ASIGNATURA EXTRA</th>
                        <th style="width:14%;">CALIFICACIÓN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($semestre['extras'] as $materia)
                        <tr>
                            <td>{{ $materia['clave'] }}</td>
                            <td class="name">{{ mb_strtoupper($materia['nombre']) }}</td>
                            <td>{{ $materia['valor'] }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    @empty
        <p style="text-align:center;margin-top:30px;"><strong>SIN HISTORIAL ACADÉMICO DISPONIBLE</strong></p>
    @endforelse
    <div class="summary"><strong>PROMEDIO GENERAL DE MATERIAS OFICIALES:</strong> {{ $promedio_general }}</div>

    <table class="signatures">
        <tr>
            <td>
                <div class="signature-box">
                    @if (is_file($selloBachillerato))
                        <img class="signature-seal left" src="{{ $selloBachillerato }}" alt="Sello del plantel">
                    @endif
                    <div class="signature-data">
                        <div class="signature-name">{{ data_get($firmanteDirector, 'nombre', 'SIN CONFIGURAR') }}</div>
                        <div class="signature-role">
                            {{ data_get($firmanteDirector, 'cargo', 'DIRECTOR(A) DEL PLANTEL') }}</div>
                    </div>
                </div>
            </td>
            <td>
                <div class="signature-box">
                    @if (is_file($selloChilpancingo))
                        <img class="signature-seal right" src="{{ $selloChilpancingo }}"
                            alt="Sello y firma de registro y certificación">
                    @endif
                    <div class="signature-data">
                        <div class="signature-name">{{ data_get($firmanteJefe, 'nombre', 'SIN CONFIGURAR') }}</div>
                        <div class="signature-role">
                            {{ data_get($firmanteJefe, 'cargo', 'JEFE DEL DEPARTAMENTO DE REGISTRO Y CERTIFICACIÓN') }}
                        </div>
                    </div>
                </div>
            </td>
        </tr>
    </table>
</body>

</html>
