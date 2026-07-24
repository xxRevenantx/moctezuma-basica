<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('inscripciones')) {
            return;
        }

        foreach ([
            'estatus',
            'activo',
            'fecha_baja',
            'motivo_baja',
            'matricula',
            'curp',
        ] as $columna) {
            if (! Schema::hasColumn('inscripciones', $columna)) {
                return;
            }
        }

        // Un egreso conserva su evidencia en fecha_estatus y motivo_estatus,
        // pero nunca debe contabilizarse ni mostrarse como una baja.
        DB::table('inscripciones')
            ->where('estatus', 'egresado')
            ->update([
                'activo' => false,
                'fecha_baja' => null,
                'motivo_baja' => null,
                'updated_at' => now(),
            ]);

        // Corrección puntual confirmada en el respaldo proporcionado:
        // el motivo indica traslado, por lo que el estatus correcto es trasladado.
        DB::table('inscripciones')
            ->where(function ($query): void {
                $query->where('matricula', '231200710001')
                    ->orWhere('curp', 'GOCL080823HGRNNSA1');
            })
            ->where('estatus', 'baja_definitiva')
            ->update([
                'estatus' => 'trasladado',
                'activo' => false,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        if (! Schema::hasTable('inscripciones')
            || ! Schema::hasColumn('inscripciones', 'estatus')
            || ! Schema::hasColumn('inscripciones', 'matricula')
            || ! Schema::hasColumn('inscripciones', 'curp')) {
            return;
        }

        DB::table('inscripciones')
            ->where(function ($query): void {
                $query->where('matricula', '231200710001')
                    ->orWhere('curp', 'GOCL080823HGRNNSA1');
            })
            ->where('estatus', 'trasladado')
            ->update([
                'estatus' => 'baja_definitiva',
                'updated_at' => now(),
            ]);

        // Los datos históricos eliminados de fecha_baja y motivo_baja para egresados
        // no se reconstruyen, porque el egreso no es una baja administrativa.
    }
};
