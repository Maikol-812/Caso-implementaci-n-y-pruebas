<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('estudiantes', function (Blueprint $table) {
            $table->id();
            $table->string('carne', 20)->unique();
            $table->string('nombre', 100);
            $table->string('apellidos', 100);
            // Cada estudiante tiene exactamente un profesor consejero
            $table->foreignId('consejero_id')->constrained('docentes')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('estudiantes');
    }
};