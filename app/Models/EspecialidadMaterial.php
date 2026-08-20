<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EspecialidadMaterial extends Model
{
    protected $table = 'especialidades_material';

    protected $fillable = [
        'material_id',
        'especialidad',
    ];

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }
}
