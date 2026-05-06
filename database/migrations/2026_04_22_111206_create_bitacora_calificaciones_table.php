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
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para guardar el historial de cambios de calificaciones.
     */
    public function up(): void
    {
        Schema::create('bitacora_calificaciones', function (Blueprint $table) {
            $table->id();

            /*
             * Contexto académico histórico.
             * Se guarda para que la bitácora conserve el contexto aunque después cambien relaciones.
             */
            $table->foreignId('nivel_id')
                ->nullable()
                ->constrained('niveles')
                ->nullOnDelete();

            $table->foreignId('grado_id')
                ->nullable()
                ->constrained('grados')
                ->nullOnDelete();

            $table->foreignId('grupo_id')
                ->nullable()
                ->constrained('grupos')
                ->nullOnDelete();

            $table->foreignId('generacion_id')
                ->nullable()
                ->constrained('generaciones')
                ->nullOnDelete();

            $table->foreignId('semestre_id')
                ->nullable()
                ->constrained('semestres')
                ->nullOnDelete();

            $table->foreignId('ciclo_escolar_id')
                ->nullable()
                ->constrained('ciclo_escolares')
                ->nullOnDelete();

            $table->foreignId('periodo_id')
                ->nullable()
                ->constrained('periodos')
                ->nullOnDelete();

            /*
             * Relaciones principales del movimiento.
             */
            $table->foreignId('inscripcion_id')
                ->nullable()
                ->constrained('inscripciones')
                ->nullOnDelete();

            $table->foreignId('asignacion_materia_id')
                ->nullable()
                ->constrained('asignacion_materias')
                ->nullOnDelete();

            /*
             * Usuario autenticado que hizo el movimiento.
             */
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /*
             * Acción realizada.
             */
            $table->enum('accion', ['crear', 'editar', 'eliminar']);

            /*
             * Valores antes y después.
             * Se deja string porque puede guardar números o claves como AC, ED, RA, NP, SD.
             */
            $table->string('calificacion_anterior', 10)->nullable();
            $table->string('calificacion_nueva', 10)->nullable();

            /*
             * Valores numéricos normalizados para reportes.
             */
            $table->decimal('valor_anterior_numerico', 5, 2)->nullable();
            $table->decimal('valor_nuevo_numerico', 5, 2)->nullable();

            /*
             * Tipo de valor registrado.
             * Ejemplos: numerico, especial, vacio.
             */
            $table->string('tipo_valor', 20)->nullable();

            /*
             * Información adicional del movimiento.
             */
            $table->text('observacion')->nullable();
            $table->text('motivo')->nullable();
            $table->string('ip', 45)->nullable();

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de bitácora de calificaciones.
     */
    public function down(): void
    {
        Schema::dropIfExists('bitacora_calificaciones');
    }
};
