<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoRelacionesTest extends TestCase
{
    use RefreshDatabase;

    public function test_un_estudiante_no_puede_existir_sin_consejero(): void
    {
        $this->expectException(QueryException::class);

        Estudiante::create([
            'carne' => 'C00001',
            'nombre' => 'Ana',
            'apellidos' => 'Mora',
            'consejero_id' => 999, // no existe ese docente
        ]);
    }

    public function test_al_borrar_un_grupo_se_borran_sus_matriculas_y_docentes_pero_no_los_estudiantes(): void
    {
        $curso = Curso::create(['sigla' => 'IF6100', 'nombre_curso' => 'Análisis y Diseño', 'creditos' => 4]);
        $docente = Docente::create(['cod_empleado' => 1001, 'nombre_empleado' => 'Iván', 'apellidos_empleado' => 'Chavarría']);
        $estudiante = Estudiante::create(['carne' => 'C00001', 'nombre' => 'Ana', 'apellidos' => 'Mora', 'consejero_id' => $docente->id]);

        $grupo = Grupo::create(['curso_id' => $curso->id, 'numero_ciclo' => 2, 'anio' => 2026, 'numero_grupo' => 1]);
        $grupo->docentes()->attach($docente);
        $grupo->estudiantes()->attach($estudiante, ['nota' => 90]);

        $grupo->delete();

        $this->assertDatabaseCount('estudiante_matriculado', 0);
        $this->assertDatabaseCount('docente_grupo', 0);
        $this->assertDatabaseCount('estudiantes', 1);
        $this->assertDatabaseCount('docentes', 1);
    }
}