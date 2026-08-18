<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Vehiculo extends Model
{
    use HasFactory;

    protected $fillable = [
        'placa',
        'modelo',
        'tipo',
        'estado',
        'motivo_no_disponible',
        'observaciones',
        'activo',
        'empleado_responsable_id',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
        ];
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(Programacion::class);
    }

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'empleado_responsable_id');
    }
}
