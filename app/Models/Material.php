<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Material extends Model
{
    use HasFactory;

    protected $table = 'materiales';

    protected $fillable = [
        'categoria_id',
        'subcategoria_id',
        'cliente_id',
        'codigo',
        'nombre',
        'descripcion',
        'unidad_medida',
        'ancho',
        'largo',
        'foto',
        'activo',
    ];

    protected function casts(): array
    {
        return [
            'activo' => 'boolean',
            'ancho' => 'decimal:2',
            'largo' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Material $material) {
            if (filled($material->categoria_id) === filled($material->subcategoria_id)) {
                throw new \RuntimeException('Un material debe tener exactamente una de las dos: categoría o subcategoría.');
            }
        });

        static::creating(function (Material $material) {
            if (blank($material->codigo)) {
                $material->codigo = static::generarCodigo($material);
            }
        });
    }

    protected static function generarCodigo(Material $material): string
    {
        $prefijo = $material->subcategoria_id
            ? $material->subcategoria?->prefijo
            : $material->categoria?->prefijo;

        $prefijo ??= 'GEN';

        $offset = mb_strlen($prefijo) + 2;

        $ultimoCodigo = static::query()
            ->where('codigo', 'like', "{$prefijo}-%")
            ->orderByRaw('CAST(SUBSTRING(codigo, ?) AS UNSIGNED) DESC', [$offset])
            ->value('codigo');

        $siguiente = $ultimoCodigo ? ((int) substr($ultimoCodigo, mb_strlen($prefijo) + 1)) + 1 : 1;

        return $prefijo.'-'.str_pad((string) $siguiente, 3, '0', STR_PAD_LEFT);
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaMaterial::class, 'categoria_id');
    }

    public function subcategoria(): BelongsTo
    {
        return $this->belongsTo(SubcategoriaMaterial::class, 'subcategoria_id');
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function categoriaEfectiva(): ?CategoriaMaterial
    {
        return $this->subcategoria_id ? $this->subcategoria?->categoria : $this->categoria;
    }

    public function dimensiones(): ?string
    {
        if (blank($this->ancho) || blank($this->largo)) {
            return null;
        }

        return number_format((float) $this->ancho, 2).' x '.number_format((float) $this->largo, 2).' m';
    }

    public function sugerenciasTrabajo(): HasMany
    {
        return $this->hasMany(TrabajoMaterialSugerido::class);
    }

    public function fotoUrl(): ?string
    {
        return $this->foto ? Storage::disk('public')->url($this->foto) : null;
    }
}
