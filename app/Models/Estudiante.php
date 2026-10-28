<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Estudiante extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'carne',
        'nombre',
        'apellidos',
        'consejero_id',
    ];

    /** Su profesor consejero (exactamente 1). */
    public function consejero(): BelongsTo
    {
        return $this->belongsTo(Docente::class, 'consejero_id');
    }

    /** Grupos en los que está matriculado; la nota vive en EstudianteMatriculado. */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'estudiante_matriculado')
            ->using(EstudianteMatriculado::class)
            ->withPivot('nota');
    }
}