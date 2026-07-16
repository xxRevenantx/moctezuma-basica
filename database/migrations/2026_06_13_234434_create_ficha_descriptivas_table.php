<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Compatibilidad con historiales de migración anteriores. La tabla se crea
     * con su estructura vigente en 2026_06_14_000644_create_ficha_descriptivas_table.php.
     */
    public function up(): void
    {
        // Intencionalmente vacío.
    }

    public function down(): void
    {
        // No elimina datos.
    }
};
