<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table): void {
            $table->decimal('creditos_certificados', 6, 2)
                ->nullable()
                ->after('clave');
        });

        Schema::table('configuraciones_media_superior', function (Blueprint $table): void {
            $table->date('fecha_acuerdo')->nullable()->after('numero_acuerdo');
            $table->decimal('calificacion_minima', 4, 2)->default(5)->after('turno');
            $table->decimal('calificacion_maxima', 4, 2)->default(10)->after('calificacion_minima');
            $table->decimal('minima_aprobatoria', 4, 2)->default(6)->after('calificacion_maxima');
            $table->text('texto_certificado')->nullable()->after('logo_plantel_path');
            $table->text('leyenda_certificado')->nullable()->after('texto_certificado');
        });
    }

    public function down(): void
    {
        Schema::table('configuraciones_media_superior', function (Blueprint $table): void {
            $table->dropColumn([
                'fecha_acuerdo',
                'calificacion_minima',
                'calificacion_maxima',
                'minima_aprobatoria',
                'texto_certificado',
                'leyenda_certificado',
            ]);
        });

        Schema::table('materias', function (Blueprint $table): void {
            $table->dropColumn('creditos_certificados');
        });
    }
};
