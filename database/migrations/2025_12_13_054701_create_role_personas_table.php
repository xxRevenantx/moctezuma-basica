<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('role_personas', function (Blueprint $table) {
             $table->id();
            $table->string('nombre');           // Docente, Director...
            $table->string('slug')->unique();   // docente, director, intendente...
            $table->text('descripcion')->nullable();
            $table->boolean('status')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('role_personas');
    }
};
