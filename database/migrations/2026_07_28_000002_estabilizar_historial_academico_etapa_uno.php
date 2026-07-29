<?php

use App\Services\EstabilizacionHistorialCiclosService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Normaliza y vincula únicamente datos que pueden resolverse sin ambigüedad.
     * La operación es idempotente: puede ejecutarse nuevamente mediante el
     * comando academico:auditar-historial-ciclos --reparar.
     */
    public function up(): void
    {
        app(EstabilizacionHistorialCiclosService::class)->reparar();
    }

    /**
     * No se revierten datos históricos ya normalizados porque hacerlo volvería
     * a introducir estados y vínculos inconsistentes.
     */
    public function down(): void
    {
        // Intencionalmente vacío.
    }
};
