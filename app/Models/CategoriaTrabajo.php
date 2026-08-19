<?php

namespace App\Models;

use App\Support\PrefijoGenerator;
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

    protected static function booted(): void
    {
        static::creating(function (CategoriaTrabajo $categoria) {
            if (blank($categoria->orden)) {
                $categoria->orden = static::max('orden') + 1;
            }

            if (blank($categoria->prefijo)) {
                $categoria->prefijo = PrefijoGenerator::generar($categoria->nombre);
            }
        });

        static::saving(function (CategoriaTrabajo $categoria) {
            if ($categoria->orden !== null && $categoria->orden < 1) {
                throw new \RuntimeException('El campo orden debe ser un entero mayor a 0.');
            }
        });
    }

    public function trabajosMaestro(): HasMany
    {
        return $this->hasMany(TrabajoMaestro::class, 'categoria_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(SubcategoriaTrabajo::class, 'categoria_id');
    }
}
