<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrabajoMaestro extends Model
{
    use HasFactory;

    protected $table = 'trabajos_maestro';

    protected $fillable = [
        'categoria',
        'codigo',
        'descripcion',
        'dias_estimados',
        'requiere_foto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'requiere_foto' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'trabajo_maestro_id');
    }
}
