<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('email');
        });

        // Habilita únicamente la cuenta administrativa principal existente.
        $adminId = DB::table('users')
            ->where('email', 'moctezuma@basica.com')
            ->value('id')
            ?? DB::table('users')->orderBy('id')->value('id');

        if ($adminId) {
            DB::table('users')
                ->where('id', $adminId)
                ->update(['is_admin' => true]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
