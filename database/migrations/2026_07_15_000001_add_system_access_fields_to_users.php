<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'rol_sistema')) {
                $table->string('rol_sistema', 40)->default('consulta')->after('is_admin')->index();
            }
            if (! Schema::hasColumn('users', 'permisos')) {
                $table->json('permisos')->nullable()->after('rol_sistema');
            }
            if (! Schema::hasColumn('users', 'activo')) {
                $table->boolean('activo')->default(true)->after('permisos')->index();
            }
            if (! Schema::hasColumn('users', 'ultimo_acceso_at')) {
                $table->timestamp('ultimo_acceso_at')->nullable()->after('activo');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            foreach (['ultimo_acceso_at', 'activo', 'permisos', 'rol_sistema'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
