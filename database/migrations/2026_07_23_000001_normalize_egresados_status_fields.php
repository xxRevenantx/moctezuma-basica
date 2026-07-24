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

        $columnasNecesarias = [
            'estatus',
            'activo',
            'fecha_baja',
            'motivo_baja',
        ];

        foreach ($columnasNecesarias as $columna) {
            if (! Schema::hasColumn('inscripciones', $columna)) {
                return;
            }
        }

        DB::table('inscripciones')
            ->where('estatus', 'egresado')
            ->update([
                'activo' => false,
                'fecha_baja' => null,
                'motivo_baja' => null,
            ]);
    }

    public function down(): void
    {
        // No se reconstruyen fecha_baja ni motivo_baja porque un egreso no debe tratarse como baja.
    }
};
