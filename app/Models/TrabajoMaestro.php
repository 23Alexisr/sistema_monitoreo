<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TrabajoMaestro extends Model
{
    use HasFactory;

    protected $table = 'trabajos_maestro';

    protected $fillable = [
        'categoria_id',
        'subcategoria_id',
        'cliente_id',
        'codigo',
        'descripcion',
        'dias_estimados',
        'requiere_foto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'dias_estimados' => 'decimal:2',
            'requiere_foto' => 'boolean',
            'activo' => 'boolean',
        ];
    }

    public function checklistItems(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'trabajo_maestro_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaTrabajo::class, 'categoria_id');
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(SubcategoriaTrabajo::class, 'subcategoria_id');
    }

    public function categoriaEfectiva(): ?CategoriaTrabajo
    {
        return $this->subcategoria_id ? $this->subcategoria?->categoria : $this->categoria;
    }

    protected static function booted(): void
    {
        static::saving(function (TrabajoMaestro $trabajo) {
            if (filled($trabajo->categoria_id) === filled($trabajo->subcategoria_id)) {
                throw new \RuntimeException('Un trabajo maestro debe tener exactamente una de las dos: categoría o subcategoría.');
            }
        });
    }
}
