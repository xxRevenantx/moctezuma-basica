<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('lugares_preescolar', function (Blueprint $table) {
            $table->id();

            $table->foreignId('inscripcion_id')->constrained('inscripciones')->cascadeOnDelete();
            $table->foreignId('nivel_id')->constrained('niveles')->cascadeOnDelete();
            $table->foreignId('grado_id')->nullable()->constrained('grados')->nullOnDelete();
            $table->foreignId('grupo_id')->nullable()->constrained('grupos')->nullOnDelete();
            $table->foreignId('generacion_id')->nullable()->constrained('generaciones')->nullOnDelete();
            $table->foreignId('ciclo_escolar_id')->constrained('ciclo_escolares')->cascadeOnDelete();

            /*
             * tipo_reconocimiento:
             * periodo = 1er, 2do o 3er periodo
             * anual = reconocimiento anual
             */
            $table->enum('tipo_reconocimiento', ['periodo', 'anual'])->default('periodo');

            /*
             * Para anual se guarda 0.
             * Para periodo se guarda 1, 2 o 3.
             */
            $table->unsignedTinyInteger('periodo')->default(0);

            /*
             * Solo se permiten 1, 2, 3 o null.
             * Null significa que el alumno tendrá reconocimiento general sin lugar.
             * Se permiten empates porque NO hay unique sobre lugar.
             */
            $table->unsignedTinyInteger('lugar')->nullable();

            $table->string('texto_lugar')->nullable();

            $table->text('motivo')->nullable();

            $table->foreignId('asignado_por')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('fecha_asignacion')->nullable();

            $table->timestamps();


        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lugares_preescolar');
    }
};
