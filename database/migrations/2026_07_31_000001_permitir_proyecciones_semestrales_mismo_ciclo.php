<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TABLA = 'proyecciones_continuidad';

    private const INDICE_ANTERIOR = 'proyeccion_alumno_ciclo_nivel_unica';

    private const INDICE_NUEVO = 'proyeccion_alumno_destino_academico_unica';

    /**
     * Índice de apoyo para la llave foránea de inscripcion_id.
     *
     * La tabla original dependía del índice UNIQUE anterior para respaldar
     * dicha llave foránea. MySQL no permite eliminarlo mientras no exista
     * otro índice cuyo primer campo sea inscripcion_id.
     */
    private const INDICE_SOPORTE_INSCRIPCION = 'proyecciones_continuidad_inscripcion_soporte_idx';

    public function up(): void
    {
        if (! Schema::hasTable(self::TABLA)) {
            return;
        }

        // La migración puede haber quedado aplicada parcialmente: MySQL
        // confirma cada ALTER TABLE aunque Laravel todavía no haya registrado
        // la migración. Por eso todas las operaciones son reanudables.
        if (! Schema::hasColumn(self::TABLA, 'semestre_destino_clave')) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                // El valor 0 representa destinos sin semestre. Se usa una
                // columna no nula porque los índices UNIQUE permiten varios
                // NULL y perderían la protección contra dobles proyecciones.
                $table->unsignedBigInteger('semestre_destino_clave')
                    ->default(0)
                    ->after('semestre_destino_id');
            });
        }

        DB::table(self::TABLA)->update([
            'semestre_destino_clave' => DB::raw('COALESCE(semestre_destino_id, 0)'),
        ]);

        // El índice UNIQUE viejo era también el único índice que empezaba por
        // inscripcion_id y, por tanto, MySQL lo utilizaba para sostener la FK.
        // Se crea primero un índice independiente para poder reemplazarlo.
        if (! $this->existeIndice(self::INDICE_SOPORTE_INSCRIPCION)) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                $table->index(
                    'inscripcion_id',
                    self::INDICE_SOPORTE_INSCRIPCION,
                );
            });
        }

        if ($this->existeIndice(self::INDICE_ANTERIOR)) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                $table->dropUnique(self::INDICE_ANTERIOR);
            });
        }

        if (! $this->existeIndice(self::INDICE_NUEVO)) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                $table->unique(
                    [
                        'inscripcion_id',
                        'ciclo_destino_id',
                        'nivel_destino_id',
                        'grado_destino_id',
                        'semestre_destino_clave',
                    ],
                    self::INDICE_NUEVO,
                );
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::TABLA)
            || ! Schema::hasColumn(self::TABLA, 'semestre_destino_clave')) {
            return;
        }

        $hayConflictos = DB::table(self::TABLA)
            ->select(['inscripcion_id', 'ciclo_destino_id', 'nivel_destino_id'])
            ->groupBy(['inscripcion_id', 'ciclo_destino_id', 'nivel_destino_id'])
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($hayConflictos) {
            throw new RuntimeException(
                'No se puede revertir la migración porque ya existen varias proyecciones semestrales del mismo alumno dentro de un ciclo escolar.'
            );
        }

        if ($this->existeIndice(self::INDICE_NUEVO)) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                $table->dropUnique(self::INDICE_NUEVO);
            });
        }

        if (! $this->existeIndice(self::INDICE_ANTERIOR)) {
            Schema::table(self::TABLA, function (Blueprint $table): void {
                $table->unique(
                    ['inscripcion_id', 'ciclo_destino_id', 'nivel_destino_id'],
                    self::INDICE_ANTERIOR,
                );
            });
        }

        Schema::table(self::TABLA, function (Blueprint $table): void {
            $table->dropColumn('semestre_destino_clave');
        });

        // Se conserva el índice de soporte. Es válido, pequeño y evita que la
        // llave foránea vuelva a depender accidentalmente de un índice UNIQUE.
    }

    private function existeIndice(string $nombre): bool
    {
        return DB::table('information_schema.statistics')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', self::TABLA)
            ->where('index_name', $nombre)
            ->exists();
    }
};
