<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaTrabajo extends Model
{
    use HasFactory;

    protected $table = 'categorias_trabajo';

    protected $fillable = [
        'nombre',
        'color',
        'orden',
    ];

    public function trabajosMaestro(): HasMany
    {
        return $this->hasMany(TrabajoMaestro::class, 'categoria_id');
    }
}
