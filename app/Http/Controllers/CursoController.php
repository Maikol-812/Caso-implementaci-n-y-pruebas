<?php

namespace App\Http\Controllers;

use App\Models\Curso;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CursoController extends Controller
{
    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $datos = $request->validate([
            'sigla' => ['required', 'string', 'max:10', 'unique:cursos,sigla'],
            'nombre_curso' => ['required', 'string', 'max:100'],
            'creditos' => ['required', 'integer', 'min:1', 'max:255'],
        ]);

        $curso = Curso::create($datos);

        if ($request->expectsJson()) {
            return response()->json($curso, 201);
        }

        return redirect('/')->with('status', "Curso {$curso->sigla} creado correctamente.");
    }
}