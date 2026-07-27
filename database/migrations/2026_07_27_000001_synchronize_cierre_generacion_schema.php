<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->synchronizeProcesosCierreCiclo();
        $this->synchronizeProcesosCierreCicloDetalles();
    }

    /**
     * Esta migración repara instalaciones que ya ejecutaron las migraciones
     * antiguas del cierre, pero cuya tabla quedó con una estructura parcial.
     */
    private function synchronizeProcesosCierreCiclo(): void
    {
        if (! Schema::hasTable('procesos_cierre_ciclo')) {
            return;
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->unsignedBigInteger('ciclo_destino_id')->nullable()->after('ciclo_escolar_id');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'grupo_origen_id')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->unsignedBigInteger('grupo_origen_id')->nullable()->after('generacion_id');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'alcance')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->string('alcance', 20)->default('generacion')->after('tipo');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'fecha_efectiva')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->date('fecha_efectiva')->nullable()->after('fecha_egreso');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'vista_previa_hash')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->string('vista_previa_hash', 64)->nullable()->after('resumen');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'estado_anterior_generacion')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->json('estado_anterior_generacion')->nullable()->after('vista_previa_hash');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'confirmacion_at')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->timestamp('confirmacion_at')->nullable()->after('realizado_at');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo', 'motivo_reversion')) {
            Schema::table('procesos_cierre_ciclo', function (Blueprint $table): void {
                $table->text('motivo_reversion')->nullable()->after('revertido_por');
            });
        }

        $this->addForeignIfMissing(
            'procesos_cierre_ciclo',
            'ciclo_destino_id',
            'ciclo_escolares',
            'pcc_ciclo_destino_fk',
            'set null'
        );

        $this->addForeignIfMissing(
            'procesos_cierre_ciclo',
            'grupo_origen_id',
            'grupos',
            'pcc_grupo_origen_fk',
            'set null'
        );
    }

    private function synchronizeProcesosCierreCicloDetalles(): void
    {
        if (! Schema::hasTable('procesos_cierre_ciclo_detalles')) {
            return;
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_origen_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_origen_id')->nullable()->after('inscripcion_id');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'inscripcion_ciclo_destino_id')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->unsignedBigInteger('inscripcion_ciclo_destino_id')->nullable()->after('inscripcion_ciclo_origen_id');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'resultado_propuesto')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->string('resultado_propuesto', 40)->nullable()->after('resultado');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'destino_propuesto')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->json('destino_propuesto')->nullable()->after('resultado_propuesto');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'revertido_at')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->timestamp('revertido_at')->nullable()->after('estado_nuevo');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'revertido_por')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->unsignedBigInteger('revertido_por')->nullable()->after('revertido_at');
            });
        }

        if (! Schema::hasColumn('procesos_cierre_ciclo_detalles', 'motivo_reversion')) {
            Schema::table('procesos_cierre_ciclo_detalles', function (Blueprint $table): void {
                $table->text('motivo_reversion')->nullable()->after('revertido_por');
            });
        }

        $this->addForeignIfMissing(
            'procesos_cierre_ciclo_detalles',
            'inscripcion_ciclo_origen_id',
            'inscripcion_ciclos',
            'pccd_inscc_origen_fk',
            'set null'
        );

        $this->addForeignIfMissing(
            'procesos_cierre_ciclo_detalles',
            'inscripcion_ciclo_destino_id',
            'inscripcion_ciclos',
            'pccd_inscc_destino_fk',
            'set null'
        );

        $this->addForeignIfMissing(
            'procesos_cierre_ciclo_detalles',
            'revertido_por',
            'users',
            'pccd_revertido_por_fk',
            'set null'
        );
    }

    private function addForeignIfMissing(
        string $table,
        string $column,
        string $referencesTable,
        string $constraint,
        string $onDelete = 'restrict'
    ): void {
        if (! Schema::hasColumn($table, $column) || $this->foreignKeyExists($table, $column)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use (
            $column,
            $referencesTable,
            $constraint,
            $onDelete
        ): void {
            $foreign = $blueprint->foreign($column, $constraint)
                ->references('id')
                ->on($referencesTable)
                ->cascadeOnUpdate();

            if ($onDelete === 'set null') {
                $foreign->nullOnDelete();
            } elseif ($onDelete === 'cascade') {
                $foreign->cascadeOnDelete();
            } else {
                $foreign->restrictOnDelete();
            }
        });
    }

    private function foreignKeyExists(string $table, string $column): bool
    {
        return DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('TABLE_SCHEMA', DB::connection()->getDatabaseName())
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->exists();
    }

    public function down(): void
    {
        // No se eliminan columnas al revertir porque esta es una migración de
        // reparación y podría estar normalizando columnas creadas previamente.
    }
};
