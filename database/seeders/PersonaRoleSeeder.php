<?php

namespace Database\Seeders;

use App\Models\Persona;
use App\Models\PersonaRole;
use App\Models\RolePersona;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PersonaRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
 public function run(): void
    {
        // Roles activos
        $roleIds = RolePersona::where('status', 1)->pluck('id')->all();
        if (empty($roleIds)) return;

        // ID del rol Director(a) sin grupo
        $directorSinGrupoId = RolePersona::where('slug', 'director_sin_grupo')->value('id');

        // 1) Asignación general: 1 rol aleatorio por persona
        Persona::query()
            ->select('id')
            ->chunk(200, function ($personas) use ($roleIds) {
                foreach ($personas as $p) {
                    PersonaRole::updateOrCreate(
                        ['persona_id' => $p->id],
                        ['role_persona_id' => $roleIds[array_rand($roleIds)]]
                    );
                }
            });

        // 2) Forzar 3 directores sin grupo (si existe ese rol)
        if ($directorSinGrupoId) {
            $tresPersonas = Persona::query()
                ->inRandomOrder()
                ->limit(3)
                ->pluck('id');

            foreach ($tresPersonas as $personaId) {
                PersonaRole::updateOrCreate(
                    ['persona_id' => $personaId],
                    ['role_persona_id' => $directorSinGrupoId]
                );
            }
        }
    }

}
