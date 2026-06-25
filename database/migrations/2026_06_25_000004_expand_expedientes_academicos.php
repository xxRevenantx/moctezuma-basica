<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inscripciones', function (Blueprint $table) {
            $table->text('motivo_baja')->nullable()->change();
            $table->text('observaciones_baja')->nullable()->change();
        });

        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            $table->text('motivo_baja')->nullable()->change();
            $table->text('observaciones_baja')->nullable()->change();
        });

        Schema::table('documentos_alumnos', function (Blueprint $table) {
            $table->foreignId('grado_id')->nullable()->after('nivel_id')
                ->constrained('grados')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('grupo_id')->nullable()->after('grado_id')
                ->constrained('grupos')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('ciclo_escolar_id')->nullable()->after('grupo_id')
                ->constrained('ciclo_escolares')->nullOnDelete()->cascadeOnUpdate();
            $table->date('fecha_documento')->nullable()->after('trayectoria_academica_id');
            $table->string('folio')->nullable()->after('fecha_documento');
            $table->string('origen', 30)->default('subido')->after('folio');
            $table->string('tipo_movimiento', 40)->nullable()->after('origen');
            $table->text('motivo')->nullable()->after('tipo_movimiento');

            $table->index(
                ['inscripcion_id', 'tipo_documento_id', 'nivel_id', 'grado_id', 'ciclo_escolar_id', 'es_actual'],
                'documentos_academicos_actual_idx'
            );
        });

        Schema::create('movimientos_alumnos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inscripcion_id')->constrained('inscripciones')->restrictOnDelete()->cascadeOnUpdate();
            $table->foreignId('trayectoria_academica_id')->nullable()->constrained('trayectorias_academicas')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('documento_alumno_id')->nullable()->constrained('documentos_alumnos')->nullOnDelete()->cascadeOnUpdate();
            $table->string('tipo', 40);
            $table->date('fecha');
            $table->text('motivo')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('registrado_por')->constrained('users')->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->index(['inscripcion_id', 'fecha'], 'movimientos_alumno_fecha_idx');
        });

        Schema::table('constancias', function (Blueprint $table) {
            $table->string('estado_documento', 30)->default('emitida')->after('contenido_generado_html');
            $table->timestamp('cancelada_at')->nullable()->after('estado_documento');
            $table->foreignId('cancelada_por')->nullable()->after('cancelada_at')
                ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('documento_alumno_id')->nullable()->after('cancelada_por')
                ->constrained('documentos_alumnos')->nullOnDelete()->cascadeOnUpdate();
        });

        $ahora = now();

        DB::table('tipos_documentos')
            ->where('slug', 'ine-tutor')
            ->update([
                'nombre' => 'INE del tutor',
                'descripcion' => 'Identificación oficial del tutor legal.',
                'orden' => 7,
                'updated_at' => $ahora,
            ]);

        $tipos = [
            [
                'nombre' => 'INE del padre',
                'slug' => 'ine-padre',
                'descripcion' => 'Identificación oficial del padre. Documento opcional.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 5,
            ],
            [
                'nombre' => 'INE de la madre',
                'slug' => 'ine-madre',
                'descripcion' => 'Identificación oficial de la madre. Documento opcional.',
                'es_general' => true,
                'requiere_nivel' => false,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 6,
            ],
            [
                'nombre' => 'Boleta final de grado',
                'slug' => 'boleta-final-grado',
                'descripcion' => 'Boleta final externa de primaria o secundaria, vinculada a nivel, grado y ciclo escolar.',
                'es_general' => false,
                'requiere_nivel' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 20,
            ],
            [
                'nombre' => 'Constancia de estudios',
                'slug' => 'constancia-estudios',
                'descripcion' => 'Constancia de estudios generada por el sistema o subida manualmente.',
                'es_general' => false,
                'requiere_nivel' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 21,
            ],
            [
                'nombre' => 'Constancia de baja o traslado',
                'slug' => 'constancia-baja-traslado',
                'descripcion' => 'Constancia de baja, traslado o movimiento académico, generada o externa.',
                'es_general' => false,
                'requiere_nivel' => true,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 22,
            ],
        ];

        foreach ($tipos as $tipo) {
            DB::table('tipos_documentos')->updateOrInsert(
                ['slug' => $tipo['slug']],
                array_merge($tipo, ['created_at' => $ahora, 'updated_at' => $ahora])
            );
        }

        DB::table('constancia_plantillas')->updateOrInsert(
            ['clave' => 'baja-traslado'],
            [
                'titulo' => 'Constancia de baja o traslado',
                'contenido_html' => '<p style="text-align: justify;">Por medio de la presente se hace constar que <strong>@nombre_completo</strong>, con matrícula <strong>@matricula</strong>, estuvo inscrito(a) en <strong>@nivel</strong>, grado <strong>@grado</strong>, grupo <strong>@grupo</strong>.</p><p style="text-align: justify;">Se registra el movimiento de <strong>@tipo_movimiento</strong> con fecha <strong>@fecha_baja</strong>, por el siguiente motivo: <strong>@motivo_baja</strong>.</p><p style="text-align: justify;">Se extiende la presente para los fines administrativos que correspondan.</p>',
                'variables' => json_encode([
                    '@nombre_completo', '@matricula', '@curp', '@nivel', '@grado', '@grupo',
                    '@fecha_baja', '@tipo_movimiento', '@motivo_baja', '@folio'
                ]),
                'activo' => true,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_alumnos');

        $slugs = [
            'ine-padre',
            'ine-madre',
            'boleta-final-grado',
            'constancia-estudios',
            'constancia-baja-traslado',
        ];

        $tipoIds = DB::table('tipos_documentos')
            ->whereIn('slug', $slugs)
            ->pluck('id');

        if ($tipoIds->isNotEmpty()) {
            // Elimina primero los registros dependientes para respetar la FK restrictiva.
            DB::table('documentos_alumnos')
                ->whereIn('tipo_documento_id', $tipoIds)
                ->delete();
        }

        DB::table('tipos_documentos')->whereIn('slug', $slugs)->delete();
        DB::table('constancia_plantillas')->where('clave', 'baja-traslado')->delete();

        DB::table('tipos_documentos')
            ->where('slug', 'ine-tutor')
            ->update([
                'descripcion' => 'Identificación oficial del tutor.',
                'orden' => 5,
                'updated_at' => now(),
            ]);

        Schema::table('constancias', function (Blueprint $table) {
            $table->dropForeign(['documento_alumno_id']);
            $table->dropForeign(['cancelada_por']);
            $table->dropColumn([
                'estado_documento',
                'cancelada_at',
                'cancelada_por',
                'documento_alumno_id',
            ]);
        });

        Schema::table('documentos_alumnos', function (Blueprint $table) {
            $table->dropIndex('documentos_academicos_actual_idx');
            $table->dropForeign(['grado_id']);
            $table->dropForeign(['grupo_id']);
            $table->dropForeign(['ciclo_escolar_id']);
            $table->dropColumn([
                'grado_id',
                'grupo_id',
                'ciclo_escolar_id',
                'fecha_documento',
                'folio',
                'origen',
                'tipo_movimiento',
                'motivo',
            ]);
        });

        Schema::table('trayectorias_academicas', function (Blueprint $table) {
            $table->string('motivo_baja')->nullable()->change();
            $table->string('observaciones_baja')->nullable()->change();
        });

        Schema::table('inscripciones', function (Blueprint $table) {
            $table->string('motivo_baja')->nullable()->change();
            $table->string('observaciones_baja')->nullable()->change();
        });
    }
};
