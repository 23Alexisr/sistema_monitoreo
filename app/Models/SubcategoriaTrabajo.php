<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubcategoriaTrabajo extends Model
{
    use HasFactory;

    protected $table = 'subcategorias_trabajo';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'orden',
    ];

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaTrabajo::class, 'categoria_id');
    }

    public function trabajosMaestro(): HasMany
    {
        return $this->hasMany(TrabajoMaestro::class, 'subcategoria_id');
    }
}
