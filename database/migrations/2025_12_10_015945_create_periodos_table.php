
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('nivel_id')->nullable();

            $table->unsignedBigInteger('generacion_id')->nullable();

            // Semestre (1..6). Puede ser null si quieres manejar periodos sólo por generación/mes.
            $table->unsignedBigInteger('semestre_id')->nullable();

            // Ciclo escolar al que pertenece el periodo
            $table->unsignedBigInteger('ciclo_escolar_id');

            // Meses de Bachillerato
            $table->unsignedBigInteger('mes_bachillerato_id')->nullable();


            // Fechas del periodo
            $table->date('fecha_inicio')->nullable();
            $table->date('fecha_fin')->nullable();


            // Relaciones foráneas
            $table->foreign('nivel_id')->references('id')->on('niveles')->onDelete('cascade');

            $table->foreign('generacion_id')->references('id')->on('generaciones')->onDelete('cascade');
            $table->foreign('semestre_id')->references('id')->on('semestres')->onDelete('cascade');
            $table->foreign('ciclo_escolar_id')->references('id')->on('ciclo_escolares')->onDelete('cascade');
            $table->foreign('mes_bachillerato_id')->references('id')->on('meses_bachilleratos')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('periodos');
    }
};
