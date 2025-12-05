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
        Schema::create('niveles', function (Blueprint $table) {
            $table->id();
            $table->string('logo')->nullable();
            $table->string('nombre');
            $table->string('slug')->unique();
            $table->string('cct');
            $table->string('color');


            $table->unsignedBigInteger('director_id')->nullable();
            $table->unsignedBigInteger('supervisor_id')->nullable();

            $table->foreign('director_id')->references('id')->on('directores')->onDelete('cascade');
            $table->foreign('supervisor_id')->references('id')->on('directores')->onDelete('cascade');


            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nivels');
    }
};
