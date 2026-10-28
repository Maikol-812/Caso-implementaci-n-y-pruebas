<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('docentes', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('cod_empleado')->unique();
            $table->string('nombre_empleado', 100);
            $table->string('apellidos_empleado', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('docentes');
    }
};