<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ChecklistItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_id',
        'trabajo_maestro_id',
        'parent_id',
        'descripcion',
        'dias_estimados_override',
        'orden',
        'completado',
        'requiere_foto',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'dias_estimados_override' => 'decimal:2',
            'completado' => 'boolean',
            'requiere_foto' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (ChecklistItem $item) {
            if ($item->trabajo_maestro_id && ! $item->descripcion) {
                $maestro = TrabajoMaestro::find($item->trabajo_maestro_id);

                if ($maestro) {
                    $item->descripcion = $maestro->descripcion;
                    $item->requiere_foto = $maestro->requiere_foto;

                    if ($item->dias_estimados_override === null) {
                        $item->dias_estimados_override = $maestro->dias_estimados;
                    }
                }
            }
        });
    }

    public function checklist(): BelongsTo
    {
        return $this->belongsTo(Checklist::class);
    }

    public function trabajoMaestro(): BelongsTo
    {
        return $this->belongsTo(TrabajoMaestro::class, 'trabajo_maestro_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChecklistItem::class, 'parent_id')->orderBy('orden');
    }

    public function fotos(): HasMany
    {
        return $this->hasMany(Foto::class);
    }

    public function dias(): float
    {
        return (float) ($this->dias_estimados_override ?? $this->trabajoMaestro?->dias_estimados ?? 0);
    }
}
