<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonaNivelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        /**
         * Config:
         *  - total: personas a asignar (sin contar el directivo)
         *  - grupos: nombres de grupos destino
         *
         * Si tus slugs son diferentes, cámbialos aquí.
         */
        $nivelesConfig = [
            'preescolar' => ['total' => 8,  'grupos' => ['A', 'B']],
            'primaria'   => ['total' => 18, 'grupos' => ['A', 'B']],
            'secundaria' => ['total' => 18, 'grupos' => ['A', 'B']],
        ];

        // =========================
        // POOL DE PERSONAS (personal)
        // =========================
        // Tomamos personas que tengan rol en persona_role.
        $personaIds = DB::table('persona_role')
            ->distinct()
            ->pluck('persona_id')
            ->all();

        if (empty($personaIds)) {
            return;
        }

        // =========================
        // ROLES DIRECTIVOS
        // =========================
        // Ajusta si quieres otro criterio. Aquí buscamos roles directivos por slug.
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

        // De ese pool de personas, tomamos las que sean directivos (según persona_role).
        $directivoPersonaIds = [];
        if (!empty($directivoRoleIds)) {
            $directivoPersonaIds = DB::table('persona_role')
                ->whereIn('role_persona_id', $directivoRoleIds)
                ->pluck('persona_id')
                ->unique()
                ->values()
                ->all();
        }

        // =========================
        // ASIGNACIÓN POR NIVEL
        // =========================
        foreach ($nivelesConfig as $nivelSlug => $cfg) {

            $nivelId = DB::table('niveles')->where('slug', $nivelSlug)->value('id');
            if (!$nivelId) {
                // Si no existe el nivel por slug, lo saltamos
                continue;
            }

            // Obtener grupos A/B de ese nivel
            $grupos = DB::table('grupos')
                ->select('id', 'grado_id', 'nombre')
                ->where('nivel_id', $nivelId)
                ->whereIn('nombre', $cfg['grupos'])
                ->orderBy('grado_id')
                ->orderBy('nombre')
                ->get();

            if ($grupos->isEmpty()) {
                continue;
            }

            // 1) Asignar 1 DIRECTIVO por nivel (al grupo A si existe, si no al primero)
            $grupoA = $grupos->firstWhere('nombre', 'A') ?? $grupos->first();
            $directivoPersonaId = null;

            if (!empty($directivoPersonaIds)) {
                // Toma un directivo disponible aleatorio
                $directivoPersonaId = $directivoPersonaIds[array_rand($directivoPersonaIds)];
            } else {
                // Si no hay directivos en persona_role, toma cualquiera
                $directivoPersonaId = $personaIds[array_rand($personaIds)];
            }

            // Insert/Update directivo
            $this->upsertPersonaNivel(
                personaId: $directivoPersonaId,
                nivelId: $nivelId,
                gradoId: $grupoA->grado_id,
                grupoId: $grupoA->id,
                orden: 1
            );

            // 2) Repartir el PERSONAL del nivel por A/B
            //    (total definido por cfg, sin contar el directivo)
            $total = (int) $cfg['total'];
            $gruposDestino = $grupos->values()->all();

            // Quitamos el directivo del pool para que no se repita en este nivel
            $pool = array_values(array_diff($personaIds, [$directivoPersonaId]));

            // Si no hay suficientes, reciclamos (pero intentamos evitar)
            if (count($pool) < $total) {
                $pool = $personaIds; // fallback
            }

            shuffle($pool);
            $seleccionados = array_slice($pool, 0, $total);

            // Distribución pareja por A/B:
            // ej: 8 => 4 y 4, 18 => 9 y 9
            $porGrupo = intdiv($total, count($gruposDestino));
            $sobrantes = $total % count($gruposDestino);

            $idx = 0;
            foreach ($gruposDestino as $i => $g) {

                $cantidad = $porGrupo + ($i < $sobrantes ? 1 : 0);

                // orden: empieza en 2 porque el 1 lo usó el directivo en grupo A.
                // Si el grupo NO es A, puede empezar en 1.
                $orden = ($g->id === $grupoA->id) ? 2 : 1;

                for ($k = 0; $k < $cantidad; $k++) {
                    if (!isset($seleccionados[$idx])) break;

                    $this->upsertPersonaNivel(
                        personaId: $seleccionados[$idx],
                        nivelId: $nivelId,
                        gradoId: $g->grado_id,
                        grupoId: $g->id,
                        orden: $orden
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
        // updateOrCreate por combinación (persona + nivel + grado + grupo)
        // si quieres 1 sola fila por persona/nivel sin importar grupo, dímelo y lo cambio.
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
