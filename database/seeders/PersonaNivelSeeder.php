<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonaNivelSeeder extends Seeder
{
    public function run(): void
    {
        /**
         * Config:
         *  - total: personas a asignar (sin contar el directivo)
         *  - grupos: nombres de grupos permitidos (A/B)
         *
         * ✅ REGLA NUEVA:
         *  - En cada nivel NO se repite grado: se toma SOLO 1 grupo por grado (prioriza A).
         */
        $nivelesConfig = [
            'preescolar' => ['total' => 8,  'grupos' => ['A', 'B']],
            'primaria'   => ['total' => 18, 'grupos' => ['A', 'B']],
            'secundaria' => ['total' => 18, 'grupos' => ['A', 'B']],
        ];

        // POOL DE PERSONAS (personal)
        $personaIds = DB::table('persona_role')->distinct()->pluck('persona_id')->all();
        if (empty($personaIds)) return;

        // ROLES DIRECTIVOS
        $directivoRoleIds = DB::table('role_personas')
            ->whereIn('slug', [
                'director_sin_grupo',
                'director_con_grupo',
                'subdirector_escuela',
                'coordinador_academico',
                'mando_medio_directivo_administrativo',
            ])
            ->pluck('id')
            ->all();

        $directivoPersonaIds = [];
        if (!empty($directivoRoleIds)) {
            $directivoPersonaIds = DB::table('persona_role')
                ->whereIn('role_persona_id', $directivoRoleIds)
                ->pluck('persona_id')
                ->unique()
                ->values()
                ->all();
        }

        foreach ($nivelesConfig as $nivelSlug => $cfg) {

            $nivelId = DB::table('niveles')->where('slug', $nivelSlug)->value('id');
            if (!$nivelId) continue;

            /**
             * ✅ 1) Traemos todos los grupos A/B del nivel
             */
            $gruposRaw = DB::table('grupos')
                ->select('id', 'grado_id', 'nombre')
                ->where('nivel_id', $nivelId)
                ->whereIn('nombre', $cfg['grupos'])
                ->orderBy('grado_id')
                ->orderByRaw("FIELD(nombre, 'A','B')") // prioriza A sobre B
                ->get();

            if ($gruposRaw->isEmpty()) continue;

            /**
             * ✅ 2) Nos quedamos con SOLO 1 grupo por grado (no repetir grado)
             *     Regla: por cada grado, usa A si existe; si no, usa B.
             */
            $gruposPorGrado = $gruposRaw
                ->groupBy('grado_id')
                ->map(function ($items) {
                    return $items->firstWhere('nombre', 'A') ?? $items->first();
                })
                ->values();

            if ($gruposPorGrado->isEmpty()) continue;

            // 1) Asignar 1 DIRECTIVO por nivel (al primer grado disponible)
            $grupoDirectivo = $gruposPorGrado->first();
            $directivoPersonaId = !empty($directivoPersonaIds)
                ? $directivoPersonaIds[array_rand($directivoPersonaIds)]
                : $personaIds[array_rand($personaIds)];

            $this->upsertPersonaNivel(
                personaId: $directivoPersonaId,
                nivelId: $nivelId,
                gradoId: (int) $grupoDirectivo->grado_id,
                grupoId: (int) $grupoDirectivo->id,
                orden: 1
            );

            // 2) Asignar PERSONAL sin repetir grado
            $total = (int) $cfg['total'];

            // ✅ pool sin directivo
            $pool = array_values(array_diff($personaIds, [$directivoPersonaId]));
            if (count($pool) < $total) $pool = $personaIds;

            shuffle($pool);
            $seleccionados = array_slice($pool, 0, $total);

            /**
             * ✅ Distribución por grados disponibles (1 grupo por grado)
             */
            $gruposDestino = $gruposPorGrado->all();
            $numGrados = count($gruposDestino);

            $porGrupo = $numGrados > 0 ? intdiv($total, $numGrados) : 0;
            $sobrantes = $numGrados > 0 ? ($total % $numGrados) : 0;

            $idx = 0;

            foreach ($gruposDestino as $i => $g) {
                $cantidad = $porGrupo + ($i < $sobrantes ? 1 : 0);

                // ✅ orden por grupo_id (grado+grupo), independiente
                $orden = ($g->id == $grupoDirectivo->id) ? 2 : 1;

                for ($k = 0; $k < $cantidad; $k++) {
                    if (!isset($seleccionados[$idx])) break;

                    $this->upsertPersonaNivel(
                        personaId: (int) $seleccionados[$idx],
                        nivelId: (int) $nivelId,
                        gradoId: (int) $g->grado_id,
                        grupoId: (int) $g->id,
                        orden: (int) $orden
                    );

                    $orden++;
                    $idx++;
                }
            }
        }
    }

    private function upsertPersonaNivel(
        int $personaId,
        int $nivelId,
        int $gradoId,
        int $grupoId,
        int $orden
    ): void {
        DB::table('persona_nivel')->updateOrInsert(
            [
                'persona_id' => $personaId,
                'nivel_id'   => $nivelId,
                'grado_id'   => $gradoId,
                'grupo_id'   => $grupoId,
            ],
            [
                'ingreso_seg' => null,
                'ingreso_sep' => null,
                'orden'       => $orden,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );
    }
}
