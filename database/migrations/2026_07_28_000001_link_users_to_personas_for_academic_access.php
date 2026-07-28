<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('personas') || Schema::hasColumn('users', 'persona_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->foreignId('persona_id')
                ->nullable()
                ->after('id')
                ->constrained('personas')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->unique('persona_id', 'users_persona_unique');
            $table->index(['rol_sistema', 'persona_id'], 'users_role_persona_idx');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'persona_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropIndex('users_role_persona_idx');
            $table->dropUnique('users_persona_unique');
            $table->dropConstrainedForeignId('persona_id');
        });
    }
};
