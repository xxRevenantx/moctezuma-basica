<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        $agregarActivo = ! Schema::hasColumn('tutores', 'activo');
        $agregarArchivadoAt = ! Schema::hasColumn('tutores', 'archivado_at');
        $agregarArchivadoPor = ! Schema::hasColumn('tutores', 'archivado_por');
        $agregarIdentificador = ! Schema::hasColumn('tutores', 'identificador_alternativo');
        $agregarMotivoSinCurp = ! Schema::hasColumn('tutores', 'motivo_sin_curp');

        if ($agregarActivo || $agregarArchivadoAt || $agregarArchivadoPor || $agregarIdentificador || $agregarMotivoSinCurp) {
            Schema::table('tutores', function (Blueprint $table) use (
                $agregarActivo,
                $agregarArchivadoAt,
                $agregarArchivadoPor,
                $agregarIdentificador,
                $agregarMotivoSinCurp,
            ): void {
                if ($agregarActivo) {
                    $table->boolean('activo')->default(true)->after('correo_electronico');
                }

                if ($agregarArchivadoAt) {
                    $table->timestamp('archivado_at')->nullable()->after('activo');
                }

                if ($agregarArchivadoPor) {
                    $table->foreignId('archivado_por')->nullable()->after('archivado_at')
                        ->constrained('users')->nullOnDelete()->cascadeOnUpdate();
                }

                if ($agregarIdentificador) {
                    $table->string('identificador_alternativo', 80)->nullable()->after('curp');
                }

                if ($agregarMotivoSinCurp) {
                    $table->string('motivo_sin_curp', 255)->nullable()->after('identificador_alternativo');
                }
            });
        }

        if (! $this->indiceExiste('tutores', 'tutores_activo_index')) {
            Schema::table('tutores', fn (Blueprint $table) => $table->index('activo'));
        }

        if (! $this->indiceExiste('tutores', 'tutores_identificador_alternativo_unique')) {
            $duplicadosAlternativos = DB::table('tutores')
                ->selectRaw('UPPER(TRIM(identificador_alternativo)) AS identificador_normalizado, COUNT(*) AS total')
                ->whereNotNull('identificador_alternativo')
                ->whereRaw("TRIM(identificador_alternativo) <> ''")
                ->groupByRaw('UPPER(TRIM(identificador_alternativo))')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($duplicadosAlternativos) {
                throw new \RuntimeException(
                    'Existen identificadores alternativos de tutores duplicados. '
                    . 'Ejecuta database/diagnostics/diagnosticar_relacion_tutores.sql y corrígelos antes de migrar.'
                );
            }

            DB::table('tutores')
                ->whereNotNull('identificador_alternativo')
                ->update([
                    'identificador_alternativo' => DB::raw('NULLIF(UPPER(TRIM(identificador_alternativo)), \'\')'),
                ]);

            Schema::table('tutores', fn (Blueprint $table) => $table->unique('identificador_alternativo'));
        }

        // MySQL permite múltiples NULL en una llave UNIQUE. Se conserva la
        // unicidad de las CURP reales y se admiten responsables extranjeros.
        if (Schema::hasColumn('tutores', 'curp')) {
            DB::table('tutores')->whereNotNull('curp')->update([
                'curp' => DB::raw("NULLIF(UPPER(REPLACE(TRIM(curp), ' ', '')), '')"),
            ]);

            Schema::table('tutores', function (Blueprint $table): void {
                $table->string('curp', 18)->nullable()->change();
            });
        }

        if (! Schema::hasTable('inscripcion_tutor')) {
            Schema::create('inscripcion_tutor', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('inscripcion_id')->constrained('inscripciones')
                    ->cascadeOnUpdate()->restrictOnDelete();
                $table->foreignId('tutor_id')->constrained('tutores')
                    ->cascadeOnUpdate()->restrictOnDelete();

                $table->string('parentesco', 50)->default('OTRO');
                $table->boolean('es_principal')->default(false)->index();
                $table->unsignedSmallInteger('orden_contacto')->default(1);
                $table->boolean('es_tutor_legal')->default(false);
                $table->string('estado_tutela', 30)->default('no_aplica');
                $table->boolean('vive_con_alumno')->default(false);
                $table->boolean('recibe_avisos')->default(true);
                $table->boolean('recibe_calificaciones')->default(true);
                $table->boolean('contacto_emergencia')->default(false);
                $table->boolean('autorizado_recoger')->default(false);
                $table->boolean('responsable_economico')->default(false);
                $table->boolean('activo')->default(true)->index();
                $table->date('fecha_inicio')->nullable();
                $table->date('fecha_fin')->nullable();
                $table->string('motivo_fin', 255)->nullable();
                $table->text('observaciones')->nullable();
                $table->foreignId('created_by')->nullable()->constrained('users')
                    ->nullOnDelete()->cascadeOnUpdate();
                $table->foreignId('updated_by')->nullable()->constrained('users')
                    ->nullOnDelete()->cascadeOnUpdate();
                $table->timestamps();

                $table->unique(['inscripcion_id', 'tutor_id'], 'inscripcion_tutor_alumno_tutor_unico');
                $table->index(
                    ['inscripcion_id', 'activo', 'es_principal'],
                    'inscripcion_tutor_principal_activo_idx'
                );
                $table->index(
                    ['tutor_id', 'activo'],
                    'inscripcion_tutor_tutor_activo_idx'
                );
            });
        }

        $this->migrarRelacionLegada();
    }

    public function down(): void
    {
        if (Schema::hasTable('inscripcion_tutor')) {
            $relacionesMultiples = DB::table('inscripcion_tutor')
                ->select('inscripcion_id')
                ->groupBy('inscripcion_id')
                ->havingRaw('COUNT(*) > 1')
                ->exists();

            if ($relacionesMultiples) {
                throw new \RuntimeException(
                    'No se puede revertir porque ya existen alumnos con varios responsables. '
                    . 'Conserva la tabla inscripcion_tutor o realiza un respaldo y una consolidación manual.'
                );
            }

            $principales = DB::table('inscripcion_tutor')
                ->where('activo', true)
                ->orderByDesc('es_principal')
                ->orderBy('orden_contacto')
                ->get()
                ->unique('inscripcion_id');

            foreach ($principales as $relacion) {
                DB::table('inscripciones')
                    ->where('id', $relacion->inscripcion_id)
                    ->update(['tutor_id' => $relacion->tutor_id]);
            }

            Schema::dropIfExists('inscripcion_tutor');
        }

        if ($this->indiceExiste('tutores', 'tutores_identificador_alternativo_unique')) {
            Schema::table('tutores', fn (Blueprint $table) => $table->dropUnique('tutores_identificador_alternativo_unique'));
        }

        if ($this->indiceExiste('tutores', 'tutores_activo_index')) {
            Schema::table('tutores', fn (Blueprint $table) => $table->dropIndex('tutores_activo_index'));
        }

        if (Schema::hasColumn('tutores', 'archivado_por')) {
            Schema::table('tutores', fn (Blueprint $table) => $table->dropConstrainedForeignId('archivado_por'));
        }

        $columnas = collect([
            'activo',
            'archivado_at',
            'identificador_alternativo',
            'motivo_sin_curp',
        ])->filter(fn (string $columna): bool => Schema::hasColumn('tutores', $columna))->all();

        if ($columnas !== []) {
            Schema::table('tutores', fn (Blueprint $table) => $table->dropColumn($columnas));
        }

        if (Schema::hasColumn('tutores', 'curp') && ! DB::table('tutores')->whereNull('curp')->exists()) {
            Schema::table('tutores', function (Blueprint $table): void {
                $table->string('curp', 18)->nullable(false)->change();
            });
        }
    }

    private function migrarRelacionLegada(): void
    {
        DB::table('inscripciones')
            ->join('tutores', 'tutores.id', '=', 'inscripciones.tutor_id')
            ->whereNotNull('inscripciones.tutor_id')
            ->select([
                'inscripciones.id as inscripcion_id',
                'inscripciones.tutor_id',
                'inscripciones.fecha_inscripcion',
                'inscripciones.created_at as inscripcion_created_at',
                'tutores.parentesco',
            ])
            ->orderBy('inscripciones.id')
            ->chunkById(250, function ($filas): void {
                foreach ($filas as $fila) {
                    $parentesco = mb_strtoupper(trim((string) $fila->parentesco));

                    $clave = [
                        'inscripcion_id' => (int) $fila->inscripcion_id,
                        'tutor_id' => (int) $fila->tutor_id,
                    ];
                    $valores = [
                        'parentesco' => $parentesco !== '' ? $parentesco : 'OTRO',
                        'es_principal' => true,
                        'orden_contacto' => 1,
                        'es_tutor_legal' => false,
                        'estado_tutela' => 'no_aplica',
                        'vive_con_alumno' => false,
                        'recibe_avisos' => true,
                        'recibe_calificaciones' => true,
                        'contacto_emergencia' => false,
                        'autorizado_recoger' => false,
                        'responsable_economico' => false,
                        'activo' => true,
                        'fecha_inicio' => $fila->fecha_inscripcion
                            ?: ($fila->inscripcion_created_at ? substr((string) $fila->inscripcion_created_at, 0, 10) : null),
                        'fecha_fin' => null,
                        'motivo_fin' => null,
                        'updated_at' => now(),
                    ];

                    if (DB::table('inscripcion_tutor')->where($clave)->exists()) {
                        // Idempotente: actualiza la relación legado sin alterar su
                        // fecha original de creación ni duplicar el vínculo.
                        DB::table('inscripcion_tutor')->where($clave)->update($valores);
                    } else {
                        DB::table('inscripcion_tutor')->insert([
                            ...$clave,
                            ...$valores,
                            'created_at' => now(),
                        ]);
                    }
                }
            }, 'inscripciones.id', 'inscripcion_id');
    }

    private function indiceExiste(string $tabla, string $indice): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            return collect(DB::select("PRAGMA index_list('{$tabla}')"))
                ->contains(fn (object $item): bool => (string) ($item->name ?? '') === $indice);
        }

        $base = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $base)
            ->where('table_name', $tabla)
            ->where('index_name', $indice)
            ->exists();
    }
};
