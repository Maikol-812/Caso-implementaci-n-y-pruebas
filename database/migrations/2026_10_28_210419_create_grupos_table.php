<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('grupos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('curso_id')->constrained('cursos')->cascadeOnDelete();
            $table->unsignedTinyInteger('numero_ciclo');
            $table->unsignedSmallInteger('anio');
            $table->unsignedTinyInteger('numero_grupo');
            $table->boolean('abierto')->default(true);

            $table->unique(['curso_id', 'anio', 'numero_ciclo', 'numero_grupo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('grupos');
    }
};