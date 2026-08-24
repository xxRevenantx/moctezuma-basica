<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('acciones')
            ->where('slug', 'generales')
            ->update(['accion' => 'Resumen']);
    }

    public function down(): void
    {
        DB::table('acciones')
            ->where('slug', 'generales')
            ->update(['accion' => 'Generales']);
    }
};
