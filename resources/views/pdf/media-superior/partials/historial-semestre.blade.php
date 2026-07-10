<div class="semester">
    <div class="semester-head">
        <span>Semestre {{ $semestre['numero'] }} &nbsp; {{ $semestre['ciclo_texto'] ?? '—' }}</span>
        <span>PERIODO DE REGULARIZACIÓN</span>
    </div>
    <table class="subjects">
        <thead>
            <tr>
                <th class="key">CLAVE</th>
                <th class="name">NOMBRE ASIGNATURA</th>
                <th class="grade">CALIF.</th>
                <th class="assist">ASIST.</th>
                <th class="regular">
                    <table class="reg-grid"><tr><td>T CALIF.</td><td>FECHA</td><td>CALIF.</td><td>FECHA</td><td>CALIF.</td><td>FECHA</td><td>CALIF.</td></tr></table>
                </th>
            </tr>
        </thead>
        <tbody>
            @forelse($semestre['oficiales'] as $materia)
                <tr>
                    <td class="key">{{ $materia['clave'] }}</td>
                    <td class="name">{{ $materia['nombre'] }}</td>
                    <td class="grade">{{ $materia['valor'] !== '' ? $materia['valor'] : '—' }}</td>
                    <td class="assist">{{ $materia['asistencia'] !== null ? number_format((float) $materia['asistencia'], 0) : '' }}</td>
                    <td class="regular"></td>
                </tr>
            @empty
                <tr><td colspan="5" style="text-align:center;color:#999;padding:3px;">SIN MATERIAS CONFIGURADAS</td></tr>
            @endforelse
        </tbody>
    </table>
    @if(($institucional['mostrar_materias_extra'] ?? true) && $semestre['extras']->isNotEmpty())
        <div class="extra-label">MATERIAS EXTRA INFORMATIVAS · NO INTERVIENEN EN EL PROMEDIO</div>
        <table class="subjects">
            <tbody>
                @foreach($semestre['extras'] as $materia)
                    <tr><td class="key">{{ $materia['clave'] }}</td><td class="name">{{ $materia['nombre'] }}</td><td class="grade">{{ $materia['valor'] !== '' ? $materia['valor'] : '—' }}</td><td class="assist"></td><td class="regular"></td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
</div>
