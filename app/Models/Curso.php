<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Curso extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'sigla',
        'nombre_curso',
        'creditos',
    ];

    /** Un curso puede tener varios grupos (0..*). */
    public function grupos(): HasMany
    {
        return $this->hasMany(Grupo::class);
    }
}