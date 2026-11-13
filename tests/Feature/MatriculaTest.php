<?php

use App\Models\Curso;
use App\Models\Docente;
use App\Models\Estudiante;
use App\Models\EstudianteMatriculado;
use App\Models\Grupo;
use Illuminate\Database\QueryException;

function grupo01(): Grupo
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
        'abierto' => false,
    ]);
}

function docente1001(): Docente
{
    return Docente::create([
        'cod_empleado' => 1001,
        'nombre_empleado' => 'Ana',
        'apellidos_empleado' => 'Mora Vargas',
    ]);
}

function docente1002(): Docente
{
    return Docente::create([
        'cod_empleado' => 1002,
        'nombre_empleado' => 'Luis',
        'apellidos_empleado' => 'Rojas Solano',
    ]);
}

function estudianteC12345(Docente $consejero): Estudiante
{
    return Estudiante::create([
        'carne' => 'C12345',
        'nombre' => 'María',
        'apellidos' => 'Solís Quesada',
        'consejero_id' => $consejero->id,
    ]);
}

/** Matricula en el grupo a $cantidad estudiantes generados con factory. */
function matricularConFactory(Grupo $grupo, Docente $consejero, int $cantidad): void
{
    $estudiantes = Estudiante::factory()
        ->count($cantidad)
        ->create(['consejero_id' => $consejero->id]);

    $grupo->estudiantes()->attach($estudiantes->pluck('id')->all());
}



test('CP-07 un grupo sin docente no se abre aunque tenga cupo completo', function () {
    $grupo = grupo01();
    $consejero = docente1001(); // solo es consejero de los estudiantes, NO imparte el grupo
    matricularConFactory($grupo, $consejero, 20);

    expect($grupo->docentes()->count())->toBe(0);
    expect($grupo->estudiantes()->count())->toBe(20);

    expect($grupo->puedeAbrirse())->toBeFalse();
    expect(fn () => $grupo->abrir())->toThrow(DomainException::class);

    // Poscondición: el grupo sigue cerrado en la base de datos
    expect($grupo->fresh()->abierto)->toBeFalse();
    $this->assertDatabaseHas('grupos', ['id' => $grupo->id, 'abierto' => false]);
});



test('CP-08 un estudiante queda ligado a su profesor consejero', function () {
    $docente = docente1001();
    expect($docente->estudiantes()->count())->toBe(0);

    $estudiante = estudianteC12345($docente);

    expect($estudiante->consejero->cod_empleado)->toBe(1001);

    $aconsejados = $docente->fresh()->estudiantes;
    expect($aconsejados)->toHaveCount(1);
    expect($aconsejados->first()->carne)->toBe('C12345');

    // Poscondición: consejero_id de C12345 apunta al docente 1001
    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente->id,
    ]);
});



test('CP-09 cambiar de consejero reemplaza al anterior, no lo suma', function () {
    $docente1001 = docente1001();
    $docente1002 = docente1002();
    $estudiante = estudianteC12345($docente1001);

    $estudiante->consejero()->associate($docente1002);
    $estudiante->save();

    expect($estudiante->fresh()->consejero->cod_empleado)->toBe(1002);
    expect($docente1001->fresh()->estudiantes)->toHaveCount(0);
    expect($docente1002->fresh()->estudiantes)->toHaveCount(1);

    // Poscondición: C12345 tiene un único consejero_id (1002)
    $this->assertDatabaseHas('estudiantes', [
        'carne' => 'C12345',
        'consejero_id' => $docente1002->id,
    ]);
    expect(Estudiante::where('carne', 'C12345')->count())->toBe(1);
});



test('CP-10 al matricular, la nota queda vacía', function () {
    $grupo = grupo01();
    $estudiante = estudianteC12345(docente1001());

    $grupo->estudiantes()->attach($estudiante);

    $pivote = $grupo->fresh()->estudiantes->first()->pivot;

    expect($pivote)->toBeInstanceOf(EstudianteMatriculado::class);
    expect($pivote->nota)->toBeNull();

    // Poscondición: 1 registro con grupo_id y estudiante_id correctos
    $this->assertDatabaseCount('estudiantes_matriculados', 1);
    $this->assertDatabaseHas('estudiantes_matriculados', [
        'grupo_id' => $grupo->id,
        'estudiante_id' => $estudiante->id,
    ]);
});


test('CP-11 registrar la nota al finalizar el curso', function () {
    $grupo = grupo01();
    $estudiante = estudianteC12345(docente1001());
    $grupo->estudiantes()->attach($estudiante);

    $grupo->registrarNota($estudiante, 8.5);

    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();

    expect($matricula)->not->toBeNull();
    expect($matricula->nota)->toBeFloat();
    expect($matricula->nota)->toBe(8.5);

    // Poscondición: solo cambió la nota; la matrícula sigue siendo la misma y única
    expect((int) $matricula->grupo_id)->toBe($grupo->id);
    expect((int) $matricula->estudiante_id)->toBe($estudiante->id);
    $this->assertDatabaseCount('estudiantes_matriculados', 1);
});



test('CP-12 rechaza notas menores que 0 o mayores que 10', function (float $nota) {
    $grupo = grupo01();
    $estudiante = estudianteC12345(docente1001());
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->registrarNota($estudiante, $nota))
        ->toThrow(InvalidArgumentException::class);

    // Poscondición: la nota de C12345 sigue en null
    $matricula = EstudianteMatriculado::where('grupo_id', $grupo->id)
        ->where('estudiante_id', $estudiante->id)
        ->first();
    expect($matricula->nota)->toBeNull();
})->with([-1, 10.5]);


test('CP-13 un estudiante no se matricula dos veces en el mismo grupo', function () {
    $grupo = grupo01();
    $estudiante = estudianteC12345(docente1001());
    $grupo->estudiantes()->attach($estudiante);

    expect(fn () => $grupo->estudiantes()->attach($estudiante))
        ->toThrow(QueryException::class);

    // Poscondición: hay un solo registro de C12345 en el grupo 01
    expect(
        EstudianteMatriculado::where('grupo_id', $grupo->id)
            ->where('estudiante_id', $estudiante->id)
            ->count()
    )->toBe(1);
    $this->assertDatabaseCount('estudiantes_matriculados', 1);
});