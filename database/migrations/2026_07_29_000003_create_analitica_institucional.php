<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analitica_institucional_configuraciones', function (Blueprint $table): void {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->json('valor');
            $table->text('descripcion')->nullable();
            $table->foreignId('actualizado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('analitica_institucional_snapshots', function (Blueprint $table): void {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->string('alcance', 30)->default('institucional');
            $table->json('filtros')->nullable();
            $table->json('datos');
            $table->string('hash_integridad', 64);
            $table->string('origen', 25)->default('manual');
            $table->timestamp('generado_at');
            $table->foreignId('generado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['ciclo_escolar_id', 'nivel_id', 'generado_at'], 'analitica_snapshot_contexto_idx');
            $table->index(['alcance', 'generado_at'], 'analitica_snapshot_alcance_idx');
        });

        Schema::create('analitica_institucional_alertas', function (Blueprint $table): void {
            $table->id();
            $table->string('fingerprint', 64)->unique();
            $table->foreignId('snapshot_id')->nullable()->constrained('analitica_institucional_snapshots')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete();
            $table->foreignId('nivel_id')->nullable()->constrained('niveles')->nullOnDelete();
            $table->string('categoria', 40);
            $table->string('severidad', 20)->default('advertencia');
            $table->string('estado', 20)->default('activa');
            $table->string('titulo');
            $table->text('mensaje');
            $table->json('evidencia')->nullable();
            $table->timestamp('detectada_at');
            $table->timestamp('resuelta_at')->nullable();
            $table->foreignId('resuelta_por')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_resolucion')->nullable();
            $table->timestamps();

            $table->index(['estado', 'severidad', 'detectada_at'], 'analitica_alertas_bandeja_idx');
            $table->index(['ciclo_escolar_id', 'nivel_id', 'categoria'], 'analitica_alertas_contexto_idx');
        });

        $ahora = now();
        DB::table('analitica_institucional_configuraciones')->insert([
            [
                'clave' => 'umbrales_alertas',
                'valor' => json_encode([
                    'matricula_caida_porcentaje' => 5,
                    'riesgo_alto_porcentaje' => 10,
                    'documentacion_minima_porcentaje' => 85,
                    'permanencia_minima_porcentaje' => 90,
                ], JSON_UNESCAPED_UNICODE),
                'descripcion' => 'Umbrales utilizados para generar alertas directivas.',
                'actualizado_por' => null,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('analitica_institucional_alertas');
        Schema::dropIfExists('analitica_institucional_snapshots');
        Schema::dropIfExists('analitica_institucional_configuraciones');
    }
};
