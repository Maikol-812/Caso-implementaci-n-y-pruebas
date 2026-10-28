<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Grupo extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'curso_id',
        'numero_ciclo',
        'anio',
        'numero_grupo',
        'abierto',
    ];

    protected $casts = [
        'abierto' => 'boolean',
    ];

    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    /** Impartido por 1 o 2 docentes. */
    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'docente_grupo');
    }

    /** Estudiantes matriculados (mínimo 15), cada uno con su nota. */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiante_matriculado')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }
}