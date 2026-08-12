<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Obra extends Model
{
    use HasFactory;

    protected $fillable = [
        'cliente_id',
        'codigo',
        'nombre',
        'ubicacion',
        'estado',
        'fecha_inicio',
        'fecha_fin_estimada',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin_estimada' => 'date',
        ];
    }

    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class);
    }

    public function ordenTrabajo(): HasOne
    {
        return $this->hasOne(OrdenTrabajo::class);
    }

    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class);
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(Programacion::class);
    }

    public function calcularAvance(): float
    {
        $checklist = $this->ordenTrabajo?->checklist;

        if (! $checklist) {
            return 0.0;
        }

        $items = $checklist->items()
            ->whereNull('parent_id')
            ->get()
            ->flatMap(fn (ChecklistItem $item) => $item->children->isNotEmpty() ? $item->children : collect([$item]));

        $diasTotales = $items->sum(fn (ChecklistItem $item) => $item->dias());

        if ($diasTotales <= 0) {
            return 0.0;
        }

        $diasCompletados = $items->where('completado', true)->sum(fn (ChecklistItem $item) => $item->dias());

        return round(($diasCompletados / $diasTotales) * 100, 2);
    }

    protected function avancePct(): Attribute
    {
        return Attribute::make(get: fn () => $this->calcularAvance());
    }
}
