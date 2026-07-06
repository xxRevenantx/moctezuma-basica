<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('constancia_plantillas')
            ->where('clave', 'estudios_termino')
            ->where('titulo', 'Constancia de estudios de termino')
            ->update([
                'titulo' => 'Constancia de estudios de término',
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('constancia_plantillas')
            ->where('clave', 'estudios_termino')
            ->where('titulo', 'Constancia de estudios de término')
            ->update([
                'titulo' => 'Constancia de estudios de termino',
                'updated_at' => now(),
            ]);
    }
};
