<?php

namespace Tests\Feature;

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\Grupo;
use DomainException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;


class CasosPruebaGrupoTest extends TestCase
{
    use RefreshDatabase;

    private function grupo01(bool $abierto = false): Grupo
    {
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        return Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'anio' => 2026,
            'numero_grupo' => 1,
            'abierto' => $abierto,
        ]);
    }

    private function docente(int $codEmpleado, string $nombre, string $apellidos): Docente
    {
        return Docente::create([
            'cod_empleado' => $codEmpleado,
            'nombre_empleado' => $nombre,
            'apellidos_empleado' => $apellidos,
        ]);
    }

    private function docente1001(): Docente
    {
        return $this->docente(1001, 'Ana', 'Mora Vargas');
    }

    private function docente1002(): Docente
    {
        return $this->docente(1002, 'Luis', 'Rojas Solano');
    }

    private function docente1003(): Docente
    {
        return $this->docente(1003, 'Carla', 'Jiménez Araya');
    }

    private function estudianteC12345(Docente $consejero): Estudiante
    {
        return Estudiante::create([
            'carne' => 'C12345',
            'nombre' => 'María',
            'apellidos' => 'Solís Quesada',
            'consejero_id' => $consejero->id,
        ]);
    }

    /** Matricula N estudiantes generados con factory en el grupo. */
    private function matricularEstudiantes(Grupo $grupo, Docente $consejero, int $cantidad): void
    {
        $estudiantes = Estudiante::factory()
            ->count($cantidad)
            ->create(['consejero_id' => $consejero->id]);

        $grupo->estudiantes()->attach($estudiantes->pluck('id')->all());
    }


    public function test_cp01_registrar_un_grupo_con_toda_su_informacion_y_su_curso(): void
    {
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        $creado = Grupo::create([
            'curso_id' => $curso->id,
            'numero_ciclo' => 2,
            'anio' => 2026,
            'numero_grupo' => 1,
            'abierto' => true,
        ]);

        $grupo = Grupo::find($creado->id);

        $this->assertNotNull($grupo);
        $this->assertEquals(2, $grupo->numero_ciclo);
        $this->assertEquals(2026, $grupo->anio);
        $this->assertEquals(1, $grupo->numero_grupo);
        $this->assertIsBool($grupo->abierto); // llega como booleano, no como 1
        $this->assertTrue($grupo->abierto);

        $this->assertSame('IF6100', $grupo->curso->sigla);
        $this->assertEquals(4, $grupo->curso->creditos);

        // Poscondición: tabla grupos con 1 registro
        $this->assertDatabaseCount('grupos', 1);
    }

    public function test_cp02_un_curso_tiene_varios_grupos_y_se_distinguen_los_abiertos(): void
    {
        $curso = Curso::create([
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ]);

        // Grupos 1 y 2 abiertos; grupo 3 cerrado (ciclo 2-2026)
        foreach ([1 => true, 2 => true, 3 => false] as $numero => $abierto) {
            Grupo::create([
                'curso_id' => $curso->id,
                'numero_ciclo' => 2,
                'anio' => 2026,
                'numero_grupo' => $numero,
                'abierto' => $abierto,
            ]);
        }

        $grupos = $curso->fresh()->grupos;
        $this->assertCount(3, $grupos);

        $abiertos = $grupos->filter(fn (Grupo $g) => $g->abierto === true);
        $this->assertCount(2, $abiertos);
        $this->assertEqualsCanonicalizing([1, 2], $abiertos->pluck('numero_grupo')->map(fn ($n) => (int) $n)->all());

        // Poscondición: los 3 grupos apuntan al mismo curso_id
        $this->assertSame(1, Grupo::distinct()->count('curso_id'));
        $this->assertSame(3, Grupo::where('curso_id', $curso->id)->count());
    }

    public function test_cp03_un_grupo_acepta_dos_docentes(): void
    {
        $grupo = $this->grupo01();
        $docente1001 = $this->docente1001();
        $docente1002 = $this->docente1002();

        $grupo->asignarDocente($docente1001);
        $grupo->asignarDocente($docente1002);

        $docentes = $grupo->fresh()->docentes;
        $this->assertCount(2, $docentes);
        $this->assertEqualsCanonicalizing([1001, 1002], $docentes->pluck('cod_empleado')->all());

        // Desde el docente 1001, grupos() incluye el grupo 01
        $this->assertTrue($docente1001->grupos->contains($grupo));

        // Poscondición: 2 registros en la tabla intermedia
        $this->assertDatabaseCount('docente_grupo', 2);
    }

    public function test_cp04_un_grupo_rechaza_un_tercer_docente(): void
    {
        $grupo = $this->grupo01();
        $grupo->asignarDocente($this->docente1001());
        $grupo->asignarDocente($this->docente1002());
        $docente1003 = $this->docente1003();

        $lanzada = false;
        try {
            $grupo->asignarDocente($docente1003);
        } catch (DomainException) {
            $lanzada = true;
        }
        $this->assertTrue($lanzada, 'Se esperaba DomainException al asignar un tercer docente.');

        // Poscondiciones: sigue con 2 docentes y 1003 no aparece en la tabla intermedia
        $this->assertCount(2, $grupo->fresh()->docentes);
        $this->assertDatabaseMissing('docente_grupo', ['docente_id' => $docente1003->id]);
    }

    public function test_cp05_un_grupo_con_14_estudiantes_no_puede_abrirse(): void
    {
        $grupo = $this->grupo01();
        $docente1001 = $this->docente1001();
        $grupo->asignarDocente($docente1001);
        $this->matricularEstudiantes($grupo, $docente1001, 14);

        $this->assertFalse($grupo->puedeAbrirse());

        $lanzada = false;
        try {
            $grupo->abrir();
        } catch (DomainException) {
            $lanzada = true;
        }
        $this->assertTrue($lanzada, 'Se esperaba DomainException al abrir con 14 estudiantes.');

        // Poscondición: en la base de datos el grupo sigue cerrado
        $this->assertFalse($grupo->fresh()->abierto);
        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'abierto' => false]);
    }


    public function test_cp06_un_grupo_con_15_estudiantes_y_un_docente_se_abre(): void
    {
        $grupo = $this->grupo01();
        $docente1001 = $this->docente1001();
        $grupo->asignarDocente($docente1001);
        $this->matricularEstudiantes($grupo, $docente1001, 15);

        $this->assertTrue($grupo->puedeAbrirse());

        $grupo->abrir(); // no debe lanzar excepción

        // Poscondición: recargado con fresh(), el grupo queda abierto
        $this->assertTrue($grupo->fresh()->abierto);
        $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'abierto' => true]);
    }
}