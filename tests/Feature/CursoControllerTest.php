<?php

namespace Tests\Feature;

use App\Models\Curso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CursoControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_la_url_de_crear_curso_apunta_a_cursos(): void
    {
        $this->assertSame(url('/cursos'), route('cursos.store'));
    }

    public function test_store_crea_un_curso_y_lo_guarda_en_la_base_de_datos(): void
    {
        $datos = [
            'sigla' => 'IF6100',
            'nombre_curso' => 'Análisis y Diseño de Sistemas',
            'creditos' => 4,
        ];

        $respuesta = $this->postJson(route('cursos.store'), $datos);

        $respuesta->assertCreated()
            ->assertJsonFragment($datos);

        $this->assertDatabaseHas('cursos', $datos);
        $this->assertDatabaseCount('cursos', 1);
    }

    public function test_store_desde_formulario_web_guarda_y_redirige(): void
    {
        $datos = [
            'sigla' => 'IF5100',
            'nombre_curso' => 'Administración de Bases de Datos',
            'creditos' => 3,
        ];

        $this->post(route('cursos.store'), $datos)->assertRedirect('/');

        $this->assertDatabaseHas('cursos', $datos);
    }

    public function test_store_rechaza_datos_invalidos_y_no_guarda_nada(): void
    {
        $this->postJson(route('cursos.store'), [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['sigla', 'nombre_curso', 'creditos']);

        $this->assertDatabaseCount('cursos', 0);
    }

    public function test_store_rechaza_una_sigla_repetida(): void
    {
        Curso::create(['sigla' => 'IF6100', 'nombre_curso' => 'Análisis y Diseño', 'creditos' => 4]);

        $this->postJson(route('cursos.store'), [
            'sigla' => 'IF6100',
            'nombre_curso' => 'Otro curso',
            'creditos' => 3,
        ])->assertUnprocessable()
            ->assertJsonValidationErrors(['sigla']);

        $this->assertDatabaseCount('cursos', 1);
    }
}