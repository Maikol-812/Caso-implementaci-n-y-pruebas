<?php

use App\Http\Controllers\CursoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Crear curso -> POST /cursos
Route::post('/cursos', [CursoController::class, 'store'])->name('cursos.store');