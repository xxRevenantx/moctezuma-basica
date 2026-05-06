<?php

use App\Models\Grado;
use App\Models\Nivel;
use App\Models\Semestre;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('materias', function (Blueprint $table) {
            $table->id();

            $table->foreignIdFor(Nivel::class)
                ->constrained('niveles')
                ->cascadeOnDelete();

            $table->foreignIdFor(Grado::class)
                ->constrained('grados')
                ->cascadeOnDelete();

            $table->foreignIdFor(Semestre::class)
                ->nullable()
                ->constrained('semestres')
                ->nullOnDelete();

            $table->string('materia');
            $table->string('clave')->nullable();
            $table->string('slug');

            $table->boolean('calificable')->default(true);
            $table->boolean('extra')->default(false);

            $table->unsignedInteger('orden')->default(0);

            $table->timestamps();


        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('materias');
    }
};
