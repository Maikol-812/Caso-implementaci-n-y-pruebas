<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiante_matriculado', function (Blueprint $table) {
            $table->foreignId('grupo_id')->constrained('grupos')->cascadeOnDelete();
            $table->foreignId('estudiante_id')->constrained('estudiantes')->cascadeOnDelete();
            // La nota se registra al finalizar el curso
            $table->decimal('nota', 5, 2)->nullable();

            $table->primary(['grupo_id', 'estudiante_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiante_matriculado');
    }
};