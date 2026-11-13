<?php

namespace App\Models;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use InvalidArgumentException;



class Grupo extends Model
{
 public const MAX_DOCENTES = 2; // @pest-mutate-ignore

    public const MIN_ESTUDIANTES_PARA_ABRIR = 15; // @pest-mutate-ignore

    public const NOTA_MINIMA = 0; // @pest-mutate-ignore
    public const NOTA_MAXIMA = 10; // @pest-mutate-ignore

    public $timestamps = false; // @pest-mutate-ignore

    protected $fillable = [
        'curso_id', // @pest-mutate-ignore
        'numero_ciclo', // @pest-mutate-ignore
        'anio', // @pest-mutate-ignore
        'numero_grupo', // @pest-mutate-ignore
        'abierto', // @pest-mutate-ignore
    ];

    protected $casts = [
        'abierto' => 'boolean', // @pest-mutate-ignore
    ];
    
    public function curso(): BelongsTo
    {
        return $this->belongsTo(Curso::class);
    }

    public function docentes(): BelongsToMany
    {
        return $this->belongsToMany(Docente::class, 'grupo_docente');
    }

    /** Estudiantes matriculados (mínimo 15), cada uno con su nota. */
    public function estudiantes(): BelongsToMany
    {
        return $this->belongsToMany(Estudiante::class, 'estudiantes_matriculados')
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