<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('firmantes_media_superior', function (Blueprint $table): void {
            $table->string('firma_path')->nullable()->after('cargo_impresion');
            $table->string('sello_path')->nullable()->after('firma_path');
            $table->foreignId('archivos_actualizados_por')
                ->nullable()
                ->after('sello_path')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();
            $table->timestamp('archivos_actualizados_at')->nullable()->after('archivos_actualizados_por');
        });
    }

    public function down(): void
    {
        Schema::table('firmantes_media_superior', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('archivos_actualizados_por');
            $table->dropColumn(['firma_path', 'sello_path', 'archivos_actualizados_at']);
        });
    }
};
