<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('configuraciones_media_superior', function (Blueprint $table): void {
            $table->boolean('mostrar_foto_historial')
                ->default(false)
                ->after('mostrar_materias_extra');
        });

        Schema::table('emisiones_documentos_media_superior', function (Blueprint $table): void {
            // Los documentos oficiales conservan cada emisión como una versión histórica.
            $table->dropUnique('emision_ms_contexto_formato_unique');
            $table->index(
                ['tipo', 'clave_contexto', 'formato'],
                'emision_ms_contexto_formato_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('emisiones_documentos_media_superior', function (Blueprint $table): void {
            $table->dropIndex('emision_ms_contexto_formato_idx');
            $table->unique(
                ['tipo', 'clave_contexto', 'formato'],
                'emision_ms_contexto_formato_unique'
            );
        });

        Schema::table('configuraciones_media_superior', function (Blueprint $table): void {
            $table->dropColumn('mostrar_foto_historial');
        });
    }
};
