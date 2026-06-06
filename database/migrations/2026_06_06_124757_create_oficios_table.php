<?php

use App\Models\Director;
use App\Models\Inscripcion;
use App\Models\Nivel;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Crea la tabla para guardar los oficios de altas y bajas.
     */
    public function up(): void
    {
        Schema::create('oficios', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Inscripcion::class)
                ->constrained('inscripciones')
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignIdFor(Nivel::class)
                ->constrained('niveles')
                ->restrictOnDelete()
                ->cascadeOnUpdate();

            $table->foreignIdFor(Director::class)
                ->nullable()
                ->constrained('directores')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->string('folio')->unique();
            $table->enum('tipo_oficio', ['Alta', 'Baja']);

            $table->string('seccion')->nullable();
            $table->string('fecha_lugar')->nullable();
            $table->string('asunto')->nullable();

            $table->string('dirigido_1_nombre')->nullable();
            $table->string('dirigido_1_cargo')->nullable();
            $table->string('dirigido_1_lugar')->nullable();

            $table->string('dirigido_2_nombre')->nullable();
            $table->string('dirigido_2_cargo')->nullable();
            $table->string('dirigido_2_lugar')->nullable();

            $table->json('periodos_calificaciones')->nullable();

            $table->longText('descripcion_html')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Elimina la tabla de oficios.
     */
    public function down(): void
    {
        Schema::dropIfExists('oficios');
    }
};
