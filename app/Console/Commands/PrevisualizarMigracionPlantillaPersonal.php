<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PrevisualizarMigracionPlantillaPersonal extends Command
{
    protected $signature = 'plantilla-personal:previsualizar-migracion
        {--ciclo=2025-2026 : Ciclo al que se asignará inicialmente la plantilla existente}
        {--sin-archivo : No generar el reporte CSV}';

    protected $description = 'Previsualiza la migración de la plantilla actual hacia plantillas históricas por ciclo escolar.';

    public function handle(): int
    {
        [$inicio, $fin] = array_pad(explode('-', (string) $this->option('ciclo'), 2), 2, null);
        $ciclo = DB::table('ciclo_escolares')
            ->where('inicio_anio', (int) $inicio)
            ->where('fin_anio', (int) $fin)
            ->first();

        if (!$ciclo) {
            $this->error('No se encontró el ciclo indicado. Ejemplo: --ciclo=2025-2026');

            return self::FAILURE;
        }

        $filas = DB::table('persona_nivel_detalles as d')
            ->join('persona_nivel as pn', 'pn.id', '=', 'd.persona_nivel_id')
            ->join('personas as p', 'p.id', '=', 'pn.persona_id')
            ->join('niveles as n', 'n.id', '=', 'pn.nivel_id')
            ->leftJoin('persona_role as pr', 'pr.id', '=', 'd.persona_role_id')
            ->leftJoin('role_personas as rp', 'rp.id', '=', 'pr.role_persona_id')
            ->leftJoin('grados as gr', 'gr.id', '=', 'd.grado_id')
            ->leftJoin('grupos as g', 'g.id', '=', 'd.grupo_id')
            ->leftJoin('asignacion_grupos as ag', 'ag.id', '=', 'g.asignacion_grupo_id')
            ->select([
                'd.id', 'pn.id as persona_nivel_id', 'p.nombre', 'p.apellido_paterno', 'p.apellido_materno',
                'n.nombre as nivel', 'rp.nombre as funcion', 'gr.nombre as grado', 'ag.nombre as grupo',
                'g.ciclo_escolar_id as grupo_ciclo_id', 'd.estado', 'pn.ingreso_seg', 'pn.ingreso_sep', 'pn.ingreso_ct',
            ])
            ->orderBy('pn.nivel_id')
            ->orderBy('d.orden')
            ->orderBy('d.id')
            ->get();

        $cabecerasDuplicadas = DB::table('persona_nivel')
            ->select('persona_id', 'nivel_id', DB::raw('COUNT(*) as total'))
            ->groupBy('persona_id', 'nivel_id')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        $conGrupo = $filas->whereNotNull('grupo');
        $grupoOtroCiclo = $conGrupo->filter(fn ($fila) => (int) $fila->grupo_ciclo_id !== (int) $ciclo->id);
        $sinFechas = $filas->filter(fn ($fila) => !$fila->ingreso_seg || !$fila->ingreso_sep || !$fila->ingreso_ct);
        $duplicados = $filas
            ->groupBy(fn ($fila) => implode('|', [
                $fila->persona_nivel_id,
                mb_strtolower((string) $fila->funcion),
                $fila->grado ?: '0',
                $fila->grupo ?: '0',
            ]))
            ->filter(fn ($items) => $items->count() > 1)
            ->sum(fn ($items) => $items->count() - 1);

        $this->newLine();
        $this->components->info('Vista previa de plantilla de personal por ciclo');
        $this->table(['Concepto', 'Cantidad'], [
            ['Ciclo histórico inicial', "{$inicio}-{$fin}"],
            ['Relaciones persona + nivel', DB::table('persona_nivel')->count()],
            ['Relaciones persona + nivel duplicadas', $cabecerasDuplicadas->sum(fn ($fila) => max(0, (int) $fila->total - 1))],
            ['Asignaciones / funciones', $filas->count()],
            ['Asignaciones con grupo', $conGrupo->count()],
            ['Grupos de otro ciclo o sin ciclo', $grupoOtroCiclo->count()],
            ['Asignaciones con fechas incompletas', $sinFechas->count()],
            ['Duplicados exactos detectados', $duplicados],
        ]);

        if ($cabecerasDuplicadas->isNotEmpty()) {
            $this->components->error('La migración no debe ejecutarse hasta consolidar las relaciones persona + nivel duplicadas.');
        }

        if ($this->option('sin-archivo')) {
            return self::SUCCESS;
        }

        $nombre = 'reportes/plantilla-personal/previsualizacion_' . now()->format('Ymd_His') . '.csv';
        $stream = fopen('php://temp', 'w+');
        fputcsv($stream, [
            'detalle_id', 'persona_nivel_id', 'personal', 'nivel', 'funcion', 'grado', 'grupo',
            'grupo_ciclo_id', 'ciclo_destino_id', 'estado', 'ingreso_seg', 'ingreso_sep', 'ingreso_ct', 'observacion',
        ]);

        foreach ($filas as $fila) {
            $observaciones = [];
            if ($fila->grupo && (int) $fila->grupo_ciclo_id !== (int) $ciclo->id) {
                $observaciones[] = 'Grupo de otro ciclo o sin ciclo';
            }
            if (!$fila->ingreso_seg || !$fila->ingreso_sep || !$fila->ingreso_ct) {
                $observaciones[] = 'Fechas laborales incompletas';
            }

            fputcsv($stream, [
                $fila->id,
                $fila->persona_nivel_id,
                trim("{$fila->nombre} {$fila->apellido_paterno} {$fila->apellido_materno}"),
                $fila->nivel,
                $fila->funcion,
                $fila->grado,
                $fila->grupo,
                $fila->grupo_ciclo_id,
                $ciclo->id,
                $fila->estado,
                $fila->ingreso_seg,
                $fila->ingreso_sep,
                $fila->ingreso_ct,
                implode('; ', $observaciones),
            ]);
        }

        rewind($stream);
        Storage::disk('local')->put($nombre, stream_get_contents($stream));
        fclose($stream);

        $this->info('Reporte generado en: storage/app/private/' . $nombre);

        return self::SUCCESS;
    }
}
