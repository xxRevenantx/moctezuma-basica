<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('tipos_documentos', 'nivel_aplica_id')) {
            Schema::table('tipos_documentos', function (Blueprint $table): void {
                $table->foreignId('nivel_aplica_id')
                    ->nullable()
                    ->after('requiere_nivel')
                    ->constrained('niveles')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->index(['activo', 'nivel_aplica_id', 'orden'], 'tipos_documentos_nivel_aplica_idx');
            });
        }

        $preescolarId = DB::table('niveles')
            ->where(function ($query): void {
                $query->whereRaw("LOWER(COALESCE(slug, '')) LIKE ?", ['%preescolar%'])
                    ->orWhereRaw("LOWER(COALESCE(nombre, '')) LIKE ?", ['%preescolar%']);
            })
            ->value('id');

        if (! $preescolarId) {
            throw new RuntimeException('No se encontró el nivel Preescolar; no es seguro registrar la cartilla de vacunación sin restringirla por nivel.');
        }

        DB::table('tipos_documentos')->updateOrInsert(
            ['slug' => 'cartilla-vacunacion'],
            [
                'nombre' => 'Cartilla de vacunación',
                'descripcion' => 'Cartilla de vacunación del alumno. Solo aplica para preescolar.',
                'es_general' => true,
                'requiere_nivel' => false,
                'nivel_aplica_id' => $preescolarId,
                'es_obligatorio' => false,
                'activo' => true,
                'orden' => 5,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        if (! Schema::hasTable('tipos_documentos_tutores')) {
            Schema::create('tipos_documentos_tutores', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre');
                $table->string('slug')->unique();
                $table->text('descripcion')->nullable();
                $table->boolean('es_obligatorio')->default(false);
                $table->json('obligatorio_parentescos')->nullable();
                $table->boolean('activo')->default(true);
                $table->unsignedSmallInteger('orden')->default(0);
                $table->timestamps();
    
                $table->index(['activo', 'orden'], 'tipos_docs_tutores_activo_orden_idx');
            });
        }

        $tiposTutor = [
            ['INE del responsable', 'ine-responsable', 'Identificación oficial vigente del responsable.', false, 1],
            ['CURP del responsable', 'curp-responsable', 'Constancia de CURP del responsable.', false, 2],
            ['Acta de nacimiento del responsable', 'acta-nacimiento-responsable', 'Acta de nacimiento del responsable.', false, 3],
            ['Comprobante de domicilio', 'comprobante-domicilio-responsable', 'Comprobante de domicilio del responsable.', false, 4],
            ['Carta de tutela o custodia', 'carta-tutela-custodia', 'Documento que acredita tutela, custodia o representación legal.', false, 5],
            ['Poder o autorización', 'poder-autorizacion', 'Poder, carta poder o autorización relacionada con el alumno.', false, 6],
            ['Otro documento del responsable', 'otro-documento-responsable', 'Documento adicional configurable del responsable.', false, 99],
        ];

        foreach ($tiposTutor as [$nombre, $slug, $descripcion, $obligatorio, $orden]) {
            DB::table('tipos_documentos_tutores')->updateOrInsert(
                ['slug' => $slug],
                [
                    'nombre' => $nombre,
                    'descripcion' => $descripcion,
                    'es_obligatorio' => $obligatorio,
                    'obligatorio_parentescos' => json_encode([], JSON_UNESCAPED_UNICODE),
                    'activo' => true,
                    'orden' => $orden,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }

        if (! Schema::hasTable('organizaciones_documentos_tutores')) {
            Schema::create('organizaciones_documentos_tutores', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
                $table->unsignedInteger('version')->default(1);
                $table->string('estado', 30)->default('borrador');
                $table->json('asignaciones');
                $table->json('fuentes_ids')->nullable();
                $table->json('retiros_confirmados')->nullable();
                $table->foreignId('confirmado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('confirmado_at')->nullable();
                $table->text('error')->nullable();
                $table->json('metadatos')->nullable();
                $table->timestamps();
    
                $table->unique(['tutor_id', 'version'], 'org_docs_tutor_version_unique');
                $table->index(['tutor_id', 'estado'], 'org_docs_tutor_estado_idx');
            });
        }

        if (! Schema::hasTable('documentos_tutores')) {
            Schema::create('documentos_tutores', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tutor_id')->constrained('tutores')->restrictOnDelete()->cascadeOnUpdate();
                $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones_documentos_tutores')->nullOnDelete();
                $table->foreignId('tipo_documento_tutor_id')->constrained('tipos_documentos_tutores')->restrictOnDelete()->cascadeOnUpdate();
                $table->date('fecha_documento')->nullable();
                $table->string('folio', 120)->nullable();
                $table->string('origen', 30)->default('subido');
                $table->string('disco', 50)->default('local');
                $table->string('ruta', 500)->nullable();
                $table->string('nombre_original', 255);
                $table->string('mime_type', 120)->default('application/pdf');
                $table->unsignedBigInteger('tamano_bytes')->default(0);
                $table->unsignedInteger('paginas_total')->default(1);
                $table->char('hash_sha256', 64)->nullable();
                $table->unsignedInteger('version')->default(1);
                $table->boolean('es_actual')->default(true);
                $table->boolean('es_fuente')->default(false);
                $table->boolean('es_organizado')->default(false);
                $table->string('estado', 30)->default('recibido');
                $table->text('observaciones')->nullable();
                $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('validado_por')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                $table->timestamp('validado_at')->nullable();
                $table->softDeletes();
                $table->timestamps();
    
                $table->index(['tutor_id', 'es_actual', 'es_fuente'], 'docs_tutor_actual_fuente_idx');
                $table->index(['tipo_documento_tutor_id', 'estado'], 'docs_tutor_tipo_estado_idx');
                $table->index(['tutor_id', 'hash_sha256'], 'docs_tutor_hash_idx');
            });
        }

        if (! Schema::hasTable('documentos_tutores_fuentes')) {
            Schema::create('documentos_tutores_fuentes', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
                $table->foreignId('documento_tutor_id')->nullable()->constrained('documentos_tutores')->nullOnDelete();
                $table->string('disco', 50)->default('local');
                $table->string('ruta', 500);
                $table->string('ruta_original', 500)->nullable();
                $table->string('nombre_original', 255);
                $table->string('nombre_almacenado', 255)->nullable();
                $table->string('mime_type', 120)->default('application/pdf');
                $table->string('mime_original', 120)->default('application/pdf');
                $table->unsignedBigInteger('tamano_bytes')->default(0);
                $table->char('hash_sha256', 64)->nullable();
                $table->unsignedInteger('paginas')->default(1);
                $table->string('estado', 30)->default('activo');
                $table->boolean('protegido')->default(false);
                $table->foreignId('subido_por')->nullable()->constrained('users')->nullOnDelete();
                $table->json('metadatos')->nullable();
                $table->timestamps();
    
                $table->index(['tutor_id', 'estado'], 'docs_fuentes_tutor_estado_idx');
                $table->index(['tutor_id', 'hash_sha256'], 'docs_fuentes_tutor_hash_idx');
                $table->unique('documento_tutor_id', 'docs_fuentes_tutor_documento_unique');
            });
        }

        if (! Schema::hasTable('documentos_tutores_no_aplica')) {
            Schema::create('documentos_tutores_no_aplica', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
                $table->foreignId('tipo_documento_tutor_id')->constrained('tipos_documentos_tutores')->restrictOnDelete();
                $table->text('motivo');
                $table->boolean('activo')->default(true);
                $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
    
                $table->index(['tutor_id', 'tipo_documento_tutor_id', 'activo'], 'docs_tutor_no_aplica_idx');
            });
        }

        if (! Schema::hasTable('eventos_documentos_tutores')) {
            Schema::create('eventos_documentos_tutores', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('tutor_id')->constrained('tutores')->cascadeOnDelete();
                $table->foreignId('documento_tutor_id')->nullable()->constrained('documentos_tutores')->nullOnDelete();
                $table->foreignId('organizacion_id')->nullable()->constrained('organizaciones_documentos_tutores')->nullOnDelete();
                $table->string('accion', 60);
                $table->text('descripcion')->nullable();
                $table->json('datos_anteriores')->nullable();
                $table->json('datos_nuevos')->nullable();
                $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
                $table->ipAddress('ip')->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamps();
    
                $table->index(['tutor_id', 'created_at'], 'eventos_docs_tutor_fecha_idx');
            });
        }

        if (! Schema::hasTable('documentos_tutores_pendientes_vincular')) {
            Schema::create('documentos_tutores_pendientes_vincular', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('documento_alumno_id');
                $table->foreignId('inscripcion_id');
                $table->foreignId('tutor_sugerido_id')->nullable();
                $table->string('tipo_origen_slug', 80);
                $table->string('tipo_destino_slug', 80)->default('ine-responsable');
                $table->string('estado', 30)->default('pendiente');
                $table->text('motivo')->nullable();
                $table->foreignId('resuelto_por')->nullable();
                $table->timestamp('resuelto_at')->nullable();
                $table->timestamps();
    
                $table->foreign('documento_alumno_id', 'fk_docs_tutor_pend_doc_alumno')
                    ->references('id')
                    ->on('documentos_alumnos')
                    ->cascadeOnDelete();
                $table->foreign('inscripcion_id', 'fk_docs_tutor_pend_inscripcion')
                    ->references('id')
                    ->on('inscripciones')
                    ->cascadeOnDelete();
                $table->foreign('tutor_sugerido_id', 'fk_docs_tutor_pend_tutor')
                    ->references('id')
                    ->on('tutores')
                    ->nullOnDelete();
                $table->foreign('resuelto_por', 'fk_docs_tutor_pend_usuario')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
    
                $table->unique('documento_alumno_id', 'docs_tutor_pendiente_documento_unique');
                $table->index(['inscripcion_id', 'estado'], 'docs_tutor_pendiente_alumno_idx');
            });
        }

        $this->registrarPendientesLegados();

        // Los tipos antiguos se conservan para leer el historial, pero dejan de
        // mostrarse como documentos que deben cargarse directamente al alumno.
        DB::table('tipos_documentos')
            ->whereIn('slug', ['ine-padre', 'ine-madre', 'ine-tutor'])
            ->update(['activo' => false, 'updated_at' => now()]);
    }

    private function registrarPendientesLegados(): void
    {
        if (! Schema::hasTable('documentos_alumnos') || ! Schema::hasTable('inscripcion_tutor')) {
            return;
        }

        $documentos = DB::table('documentos_alumnos as da')
            ->join('tipos_documentos as td', 'td.id', '=', 'da.tipo_documento_id')
            ->whereIn('td.slug', ['ine-padre', 'ine-madre', 'ine-tutor'])
            ->whereNull('da.deleted_at')
            ->where('da.es_actual', true)
            ->where('da.es_fuente', false)
            ->select('da.id', 'da.inscripcion_id', 'td.slug')
            ->orderBy('da.id')
            ->get();

        foreach ($documentos as $documento) {
            $relaciones = DB::table('inscripcion_tutor')
                ->where('inscripcion_id', $documento->inscripcion_id)
                ->where('activo', true)
                ->whereNull('fecha_fin')
                ->orderByDesc('es_principal')
                ->orderBy('orden_contacto')
                ->get(['tutor_id', 'parentesco', 'es_principal', 'es_tutor_legal']);

            $coincidencias = $relaciones->filter(function ($relacion) use ($documento): bool {
                $parentesco = Str::lower((string) $relacion->parentesco);

                return match ($documento->slug) {
                    'ine-padre' => Str::contains($parentesco, ['padre', 'papá', 'papa']),
                    'ine-madre' => Str::contains($parentesco, ['madre', 'mamá', 'mama']),
                    'ine-tutor' => (bool) $relacion->es_principal || (bool) $relacion->es_tutor_legal,
                    default => false,
                };
            })->values();

            $tutorSugeridoId = $coincidencias->count() === 1 ? $coincidencias->first()->tutor_id : null;
            $motivo = $coincidencias->count() === 1
                ? 'Coincidencia detectada automáticamente. Revisa y confirma la vinculación.'
                : 'No fue posible identificar de forma inequívoca al responsable del documento antiguo.';

            DB::table('documentos_tutores_pendientes_vincular')->updateOrInsert(
                ['documento_alumno_id' => $documento->id],
                [
                    'inscripcion_id' => $documento->inscripcion_id,
                    'tutor_sugerido_id' => $tutorSugeridoId,
                    'tipo_origen_slug' => $documento->slug,
                    'tipo_destino_slug' => 'ine-responsable',
                    'estado' => 'pendiente',
                    'motivo' => $motivo,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('documentos_tutores_pendientes_vincular');
        Schema::dropIfExists('eventos_documentos_tutores');
        Schema::dropIfExists('documentos_tutores_no_aplica');
        Schema::dropIfExists('documentos_tutores_fuentes');
        Schema::dropIfExists('documentos_tutores');
        Schema::dropIfExists('organizaciones_documentos_tutores');
        Schema::dropIfExists('tipos_documentos_tutores');

        DB::table('tipos_documentos')->where('slug', 'cartilla-vacunacion')->delete();
        DB::table('tipos_documentos')
            ->whereIn('slug', ['ine-padre', 'ine-madre', 'ine-tutor'])
            ->update(['activo' => true, 'updated_at' => now()]);

        if (Schema::hasColumn('tipos_documentos', 'nivel_aplica_id')) {
            Schema::table('tipos_documentos', function (Blueprint $table): void {
                $table->dropIndex('tipos_documentos_nivel_aplica_idx');
                $table->dropForeign(['nivel_aplica_id']);
                $table->dropColumn('nivel_aplica_id');
            });
        }
    }
};
