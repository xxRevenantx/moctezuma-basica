<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('acciones')->where('slug', 'asignacion-de-materias')->update(['orden' => 4]);
        DB::table('acciones')->where('slug', 'horarios')->update(['orden' => 5]);
        DB::table('acciones')->where('slug', 'calificaciones')->update(['orden' => 6]);
        DB::table('acciones')->where('slug', 'fichas')->update(['orden' => 7]);
        DB::table('acciones')->where('slug', 'bajas')->update(['orden' => 8]);

        DB::table('acciones')->updateOrInsert(
            ['slug' => 'alumnos-no-vigentes'],
            [
                'accion' => 'Alumnos no vigentes',
                'orden' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }

    public function down(): void
    {
        DB::table('acciones')->where('slug', 'alumnos-no-vigentes')->delete();

        DB::table('acciones')->where('slug', 'asignacion-de-materias')->update(['orden' => 3]);
        DB::table('acciones')->where('slug', 'horarios')->update(['orden' => 4]);
        DB::table('acciones')->where('slug', 'calificaciones')->update(['orden' => 5]);
        DB::table('acciones')->where('slug', 'fichas')->update(['orden' => 6]);
        DB::table('acciones')->where('slug', 'bajas')->update(['orden' => 7]);
    }
};
