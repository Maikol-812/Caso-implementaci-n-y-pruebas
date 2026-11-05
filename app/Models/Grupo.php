<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;

class Grupo extends Model
{
    public const MAX_DOCENTES = 2;

    public const MIN_ESTUDIANTES_PARA_ABRIR = 15;

    public const NOTA_MINIMA = 0;
    public const NOTA_MAXIMA = 10;

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

  
    public function asignarDocente(Docente $docente): void
    {
        if ($this->docentes()->count() >= self::MAX_DOCENTES) {
            throw new DomainException(
                'Un grupo no puede tener más de '.self::MAX_DOCENTES.' docentes.'
            );
        }

        $this->docentes()->attach($docente);
    }

    
    public function puedeAbrirse(): bool
    {
        return $this->docentes()->count() >= 1
            && $this->estudiantes()->count() >= self::MIN_ESTUDIANTES_PARA_ABRIR;
    }

   
    public function abrir(): void
    {
        if (! $this->puedeAbrirse()) {
            throw new DomainException(
                'El grupo necesita al menos 1 docente y '.self::MIN_ESTUDIANTES_PARA_ABRIR.' estudiantes para abrirse.'
            );
        }

        $this->update(['abierto' => true]);
    }

    public function registrarNota(Estudiante $estudiante, float $nota): void
    {
        if ($nota < self::NOTA_MINIMA || $nota > self::NOTA_MAXIMA) {
            throw new InvalidArgumentException(
                'La nota debe estar entre '.self::NOTA_MINIMA.' y '.self::NOTA_MAXIMA.'.'
            );
        }

        $actualizados = $this->estudiantes()->updateExistingPivot($estudiante->id, ['nota' => $nota]);

        if ($actualizados === 0) {
            throw new DomainException('El estudiante no está matriculado en este grupo.');
        }
    }
}