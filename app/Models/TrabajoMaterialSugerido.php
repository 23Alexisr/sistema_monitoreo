<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrabajoMaterialSugerido extends Model
{
    protected $table = 'trabajo_materiales_sugeridos';

    protected $fillable = [
        'trabajo_maestro_id',
        'material_id',
        'cantidad_sugerida',
    ];

    protected function casts(): array
    {
        return [
            'cantidad_sugerida' => 'decimal:2',
        ];
    }

    public function trabajoMaestro(): BelongsTo
    {
        return $this->belongsTo(TrabajoMaestro::class, 'trabajo_maestro_id');
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
