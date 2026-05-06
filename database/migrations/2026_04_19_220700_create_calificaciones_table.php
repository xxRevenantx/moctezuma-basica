<?php

use App\Models\AsignacionMateria;
use App\Models\CicloEscolar;
use App\Models\Generacion;
use App\Models\Grado;
use App\Models\Grupo;
use App\Models\Inscripcion;
use App\Models\Nivel;
use App\Models\Periodos;
use App\Models\Semestre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla de calificaciones.
     */
    public function up(): void
    {
        Schema::create('calificaciones', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscripcion_id')
                ->constrained('inscripciones')
                ->cascadeOnDelete();

            $table->foreignId('asignacion_materia_id')
                ->constrained('asignacion_materias')
                ->cascadeOnDelete();

            $table->foreignId('nivel_id')
                ->constrained('niveles')
                ->cascadeOnDelete();

            $table->foreignId('grado_id')
                ->constrained('grados')
                ->cascadeOnDelete();

            $table->foreignId('grupo_id')
                ->constrained('grupos')
                ->cascadeOnDelete();

            $table->foreignId('ciclo_escolar_id')
                ->constrained('ciclo_escolares')
                ->cascadeOnDelete();

            $table->foreignId('generacion_id')
                ->constrained('generaciones')
                ->cascadeOnDelete();

            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->nullOnDelete();

            $table->foreignId('periodo_id')
                ->nullable()
                ->constrained('periodos')
                ->cascadeOnDelete();

            // Se usa string porque permite números y claves como AC, ED, RA.
            $table->string('calificacion', 5)->nullable();

            // Datos normalizados para reportes, promedios y validaciones.
            $table->decimal('valor_numerico', 5, 2)->nullable();
            $table->boolean('es_numerica')->default(false);
            $table->string('clave_especial', 10)->nullable();
            $table->text('observacion')->nullable();

            // Usuario autenticado que capturó la calificación.
            $table->foreignId('capturado_por')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamp('fecha_captura')->nullable();
            $table->string('ip_captura', 45)->nullable();

            $table->timestamps();

            $table->unique(
                ['periodo_id', 'inscripcion_id', 'asignacion_materia_id'],
                'calificacion_unica_por_periodo'
            );

            $table->index(['nivel_id', 'grado_id', 'grupo_id'], 'calificaciones_contexto_index');
            $table->index(['ciclo_escolar_id', 'generacion_id', 'semestre_id'], 'calificaciones_ciclo_index');
        });
    }

    /**
     * Elimina la tabla de calificaciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('calificaciones');
    }
};
