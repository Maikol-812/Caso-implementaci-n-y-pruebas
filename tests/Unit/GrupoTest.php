<?php

namespace Tests\Unit;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GrupoTest extends TestCase
{
    use RefreshDatabase;

    private function crearGrupoCompleto(): Grupo
    {
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        $docente1 = Docente::create([
            'cod_empleado' => 1001,
            'nombre_empleado' => 'Iván',
            'apellidos_empleado' => 'Chavarría Cubero',
        ]);
        $docente2 = Docente::create([
            'cod_empleado' => 1002,
            'nombre_empleado' => 'Ana',
            'apellidos_empleado' => 'Rojas Mora',
        ]);

        $grupo = Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'anio' => 2026,
            'numero_grupo' => 1,
            'abierto' => true,
        ]);
        $grupo->docentes()->attach([$docente1->id, $docente2->id]);

        foreach (range(1, 15) as $i) {
            $estudiante = Estudiante::create([
                'carne' => sprintf('C%05d', $i),
                'nombre' => "Estudiante$i",
                'apellidos' => 'Prueba Uno',
                'consejero_id' => $docente1->id,
            ]);
            $grupo->estudiantes()->attach($estudiante, ['nota' => 70 + $i]);
        }

        return $grupo;
    }

    public function test_se_instancia_un_grupo_con_sus_datos_propios(): void
    {
        $grupo = $this->crearGrupoCompleto();

        $this->assertTrue($grupo->exists);
        $this->assertSame(2, $grupo->numero_ciclo);
        $this->assertSame(2026, $grupo->anio);
        $this->assertSame(1, $grupo->numero_grupo);
        $this->assertTrue($grupo->abierto);
        $this->assertDatabaseHas('grupos', ['numero_grupo' => 1, 'anio' => 2026, 'abierto' => true]);
    }

    public function test_el_grupo_pertenece_a_un_curso(): void
    {
        $grupo = $this->crearGrupoCompleto();

        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertSame('Análisis y Diseño de Sistemas', $grupo->curso->nombre_curso);
        $this->assertSame(4, $grupo->curso->creditos);
        $this->assertCount(1, $grupo->curso->grupos);
    }

    public function test_el_grupo_es_impartido_por_uno_o_dos_docentes(): void
    {
        $grupo = $this->crearGrupoCompleto();

        $this->assertCount(2, $grupo->docentes);
        $this->assertEqualsCanonicalizing([1001, 1002], $grupo->docentes->pluck('cod_empleado')->all());
    }

    public function test_el_grupo_tiene_al_menos_15_estudiantes_con_nota_y_consejero(): void
    {
        $grupo = $this->crearGrupoCompleto();

        $this->assertCount(15, $grupo->estudiantes);

        $primero = $grupo->estudiantes->firstWhere('carne', 'C00001');
        $this->assertInstanceOf(EstudianteMatriculado::class, $primero->pivot);
        $this->assertSame(71.0, $primero->pivot->nota);
        $this->assertSame('Iván', $primero->consejero->nombre_empleado);

        $this->assertDatabaseHas('estudiante_matriculado', [
            'grupo_id' => $grupo->id,
            'estudiante_id' => $primero->id,
            'nota' => 71,
        ]);
    }
}