<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Ficha descriptiva individual</title>

    @include('pdf.partials.ficha_descriptiva_css')
</head>

<body>
    @php
        $nombreAlumno = trim(
            ($alumno->nombre ?? '') . ' ' . ($alumno->apellido_paterno ?? '') . ' ' . ($alumno->apellido_materno ?? ''),
        );

        $grado = trim($alumno->grado?->nombre ?? '');
        $grupo = $alumno->grupo?->asignacionGrupo?->nombre ?? 'S/G';
        $gradoGrupo = trim($grado . ' "' . $grupo . '"');

        $cicloTexto = $cicloEscolar ? $cicloEscolar->inicio_anio . '-' . $cicloEscolar->fin_anio : '';

        $nombreEscuela = 'Centro Universitario Moctezuma A.C.';

        $cct = $alumno->nivel?->cct ?? '12PJN0226W';

        $recomendacion = $fichas->get('recomendaciones')?->descripcion ?? '';

        $footerFecha = now()->format('d/m/Y H:i');
    @endphp

    <div class="page">
        <table class="top-header">
            <tr>
                <td class="logo-cell">
                    @if (!empty($logoPrincipal))
                        <img class="logo-principal" src="{{ $logoPrincipal }}" alt="Centro Universitario Moctezuma">
                    @else
                        <strong style="font-size: 22px; color: #006492;">
                            Centro Universitario Moctezuma A.C.
                        </strong>
                    @endif
                </td>

                <td class="mascota-cell">
                    @if (!empty($logoPenacho))
                        <img class="mascota" src="{{ $logoPenacho }}" alt="Preescolar">
                    @endif
                </td>
            </tr>
        </table>

        <table class="datos-table">
            <tr>
                <td class="pink" style="width: 58%;">
                    <span class="label">Nombre de la escuela:</span>
                    <span class="dato">{{ $nombreEscuela }}</span>
                </td>

                <td style="width: 42%; text-align:center;">
                    <span class="label">C.C.T:</span>
                    <span class="dato">{{ $cct }}</span>
                </td>
            </tr>

            <tr>
                <td>
                    <span class="label">Nombre del alumno(a):</span>
                    <span class="dato">{{ $nombreAlumno }}</span>
                </td>

                <td style="padding:0;">
                    <table style="width:100%; border-collapse:collapse; table-layout:fixed;">
                        <tr>
                            <td class="yellow"
                                style="border:0; border-right:1px solid #000; padding:4px 5px; width:50%;">
                                <span class="label">Grado y Grupo:</span>
                                <span class="dato">{{ $gradoGrupo }}</span>
                            </td>

                            <td style="border:0; padding:4px 5px; width:50%;">
                                <span class="label">Ciclo Escolar:</span>
                                <span class="dato">{{ $cicloTexto }}</span>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>

            <tr>
                <td colspan="2" class="green">
                    <span class="label">Educadora:</span>
                    <span class="dato">{{ $educadoraNombre ?: 'EDUCADORA' }}</span>
                </td>
            </tr>
        </table>

        <div class="main-table-wrapper">
            @if (!empty($marcaAgua))
                <img class="watermark" src="{{ $marcaAgua }}" alt="Marca de agua">
            @endif

            <table class="ficha-table">
                <thead>
                    <tr>
                        <th style="width:132px;">Campo Formativo</th>
                        <th>{{ mb_strtoupper($periodoNombre) }}</th>
                    </tr>
                </thead>

                <tbody>
                    @foreach ($campos as $clave => $campo)
                        @continue($clave === 'recomendaciones')

                        @php
                            $texto = $fichas->get($clave)?->descripcion ?? '';
                        @endphp

                        <tr>
                            <td class="campo-col">
                                @if (!empty($campoImagenes[$clave]))
                                    <img class="campo-img" src="{{ $campoImagenes[$clave] }}"
                                        alt="{{ $campo['label'] }}">
                                @else
                                    <div class="campo-fallback">
                                        CAMPO FORMATIVO<br>{{ mb_strtoupper($campo['label']) }}
                                    </div>
                                @endif
                            </td>

                            <td class="descripcion-col">
                                <div class="texto-ficha">
                                    {!! $texto !!}
                                </div>
                            </td>
                        </tr>
                    @endforeach

                    <tr>
                        <td colspan="2" class="recomendaciones-title">
                            Recomendaciones
                        </td>
                    </tr>

                    <tr>
                        <td colspan="2" class="recomendaciones-body">
                            <div class="texto-ficha">
                                {!! $recomendacion !!}
                            </div>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <table class="firmas">
            <tr>
                <td>
                    <div class="linea"></div>
                    <div class="firma-nombre">
                        {{ $educadoraNombre ?: 'EDUCADORA' }}
                    </div>
                    <div class="firma-cargo">
                        Educadora
                    </div>
                </td>

                <td>
                    <div class="linea"></div>
                    <div class="firma-nombre">
                        {{ $directoraNombre ?: 'DIRECCIÓN' }}
                    </div>
                    <div class="firma-cargo">
                        Directora
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Centro Universitario Moctezuma · Francisco I. Madero Ote #800. Col. Esquipulas, Cd. Altamirano, Gro. ·
        {{ mb_strtoupper($periodoNombre) }} · {{ $footerFecha }}
    </div>
</body>

</html>
