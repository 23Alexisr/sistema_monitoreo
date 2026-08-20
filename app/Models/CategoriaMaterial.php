<?php

namespace App\Models;

use App\Support\PrefijoGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CategoriaMaterial extends Model
{
    use HasFactory;

    protected $table = 'categorias_material';

    protected $fillable = [
        'nombre',
        'color',
        'orden',
    ];

    protected static function booted(): void
    {
        static::creating(function (CategoriaMaterial $categoria) {
            if (blank($categoria->orden)) {
                $categoria->orden = static::max('orden') + 1;
            }

            if (blank($categoria->prefijo)) {
                $categoria->prefijo = PrefijoGenerator::generar($categoria->nombre);
            }
        });

        static::saving(function (CategoriaMaterial $categoria) {
            if ($categoria->orden !== null && $categoria->orden < 1) {
                throw new \RuntimeException('El campo orden debe ser un entero mayor a 0.');
            }
        });
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'categoria_id');
    }

    public function subcategorias(): HasMany
    {
        return $this->hasMany(SubcategoriaMaterial::class, 'categoria_id');
    }
}
