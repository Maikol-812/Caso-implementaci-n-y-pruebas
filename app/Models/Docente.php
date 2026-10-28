<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Docente extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'cod_empleado',
        'nombre_empleado',
        'apellidos_empleado',
    ];

    /** Estudiantes de los que es consejero (0..*). */
    public function estudiantesAconsejados(): HasMany
    {
        return $this->hasMany(Estudiante::class, 'consejero_id');
    }

    /** Grupos que imparte. */
    public function grupos(): BelongsToMany
    {
        return $this->belongsToMany(Grupo::class, 'docente_grupo');
    }
}