<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('escuela', function (Blueprint $table) {
            $table->string('regional', 120)->nullable()->after('nombre');
        });

        DB::table('escuela')
            ->whereNull('regional')
            ->update(['regional' => 'Tierra Caliente']);
    }

    public function down(): void
    {
        Schema::table('escuela', function (Blueprint $table) {
            $table->dropColumn('regional');
        });
    }
};
