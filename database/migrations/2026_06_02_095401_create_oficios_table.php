<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Compatibilidad con instalaciones cuya tabla migrations conserva este
     * nombre histórico. La creación real y actualizada se encuentra en
     * 2026_06_06_124757_create_oficios_table.php.
     */
    public function up(): void
    {
        // Intencionalmente vacío.
    }

    public function down(): void
    {
        // No elimina datos: la migración actual es la propietaria de la tabla.
    }
};
