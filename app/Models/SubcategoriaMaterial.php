<?php

namespace App\Models;

use App\Support\PrefijoGenerator;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SubcategoriaMaterial extends Model
{
    use HasFactory;

    protected $table = 'subcategorias_material';

    protected $fillable = [
        'categoria_id',
        'nombre',
        'orden',
    ];

    protected static function booted(): void
    {
        static::creating(function (SubcategoriaMaterial $subcategoria) {
            if (blank($subcategoria->orden)) {
                $subcategoria->orden = static::where('categoria_id', $subcategoria->categoria_id)->max('orden') + 1;
            }

            if (blank($subcategoria->prefijo)) {
                $subcategoria->prefijo = PrefijoGenerator::generar($subcategoria->nombre);
            }
        });

        static::saving(function (SubcategoriaMaterial $subcategoria) {
            if ($subcategoria->orden !== null && $subcategoria->orden < 1) {
                throw new \RuntimeException('El campo orden debe ser un entero mayor a 0.');
            }
        });
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'categoria_id');
    }

    public function materiales(): HasMany
    {
        return $this->hasMany(Material::class, 'subcategoria_id');
    }
}
