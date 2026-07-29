<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('simulaciones_cierre_ciclo')) {
            Schema::create('simulaciones_cierre_ciclo', function (Blueprint $table): void {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('nivel_id')->constrained('niveles')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('ciclo_origen_id')->constrained('ciclo_escolares')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('ciclo_destino_id')->nullable()->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('generacion_id')->constrained('generaciones')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('grupo_origen_id')->nullable()->constrained('grupos')->nullOnDelete()->cascadeOnUpdate();
                $table->string('estado', 30)->default('vigente');
                $table->json('contenido');
                $table->string('hash', 64);
                $table->json('resumen')->nullable();
                $table->timestamp('generado_at');
                $table->timestamp('expira_at');
                $table->timestamp('consumida_at')->nullable();
                $table->timestamp('cancelada_at')->nullable();
                $table->text('motivo_cancelacion')->nullable();
                $table->timestamps();

                $table->index(['usuario_id', 'estado', 'expira_at'], 'simulaciones_usuario_estado_idx');
                $table->index(['nivel_id', 'ciclo_origen_id', 'generacion_id'], 'simulaciones_contexto_idx');
            });
        }

        if (Schema::hasTable('procesos_cierre_ciclo')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'simulacion_cierre_ciclo_id')) {
                    $table->foreignId('simulacion_cierre_ciclo_id')->nullable()->after('id')
                        ->constrained('simulaciones_cierre_ciclo')
                        ->nullOnDelete()
                        ->cascadeOnUpdate();
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'simulacion')) {
                    $table->json('simulacion')->nullable()->after('vista_previa_hash');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'simulado_at')) {
                    $table->timestamp('simulado_at')->nullable()->after('simulacion');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'simulacion_expira_at')) {
                    $table->timestamp('simulacion_expira_at')->nullable()->after('simulado_at');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'respaldo_logico')) {
                    $table->json('respaldo_logico')->nullable()->after('estado_anterior_generacion');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'respaldo_hash')) {
                    $table->string('respaldo_hash', 64)->nullable()->after('respaldo_logico');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'respaldo_verificado_at')) {
                    $table->timestamp('respaldo_verificado_at')->nullable()->after('respaldo_hash');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'integridad_estado')) {
                    $table->string('integridad_estado', 30)->default('no_verificado')->after('respaldo_verificado_at');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo', 'reversion_resumen')) {
                    $table->json('reversion_resumen')->nullable()->after('motivo_reversion');
                }
            });
        }

        if (Schema::hasTable('procesos_cierre_ciclo_detalles')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'respaldo_origen')) {
                    $table->json('respaldo_origen')->nullable()->after('estado_anterior');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'respaldo_hash')) {
                    $table->string('respaldo_hash', 64)->nullable()->after('respaldo_origen');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'respaldo_verificado_at')) {
                    $table->timestamp('respaldo_verificado_at')->nullable()->after('respaldo_hash');
                }
                if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'reversion_estado')) {
                    $table->string('reversion_estado', 30)->nullable()->after('motivo_reversion');
                }
            });
        }
    }

    public function down(): void
    {
        // No se eliminan respaldos ni evidencias de integridad al revertir esta
        // migración. Son parte de la trazabilidad administrativa del cierre.
    }
};
