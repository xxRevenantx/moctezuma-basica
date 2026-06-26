<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $ahora = now();

        DB::table('tipos_documentos')->updateOrInsert(
            ['slug' => 'certificado-terminacion'],
            [
                'nombre' => 'Certificado de terminación',
                'descripcion' => 'Certificado que acredita la terminación de estudios del alumno.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 8,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]
        );
    }

    public function down(): void
    {
        $tipoId = DB::table('tipos_documentos')
            ->where('slug', 'certificado-terminacion')
            ->value('id');

        if (!$tipoId) {
            return;
        }

        $tieneDocumentos = DB::table('documentos_alumnos')
            ->where('tipo_documento_id', $tipoId)
            ->exists();

        if ($tieneDocumentos) {
            DB::table('tipos_documentos')
                ->where('id', $tipoId)
                ->update([
                    'activo' => false,
                    'updated_at' => now(),
                ]);

            return;
        }

        DB::table('tipos_documentos')->where('id', $tipoId)->delete();
    }
};
