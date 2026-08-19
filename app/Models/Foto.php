<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Foto extends Model
{
    use HasFactory;

    protected $fillable = [
        'checklist_item_id',
        'momento',
        'url',
        'fecha_subida',
        'subido_por',
    ];

    protected function casts(): array
    {
        return [
            'fecha_subida' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        $sincronizar = fn (Foto $foto) => $foto->checklistItem?->sincronizarEstadoAutomatico();

        static::created($sincronizar);
        static::deleted($sincronizar);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function subidoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'subido_por');
    }

    /**
     * Nombre de archivo descriptivo para descarga/exhibición, ej.:
     * OBR-0000012_pegado-vinil-spreader_antes_2026-08-15.jpg
     * (o "..._antes_2026-08-15_2.jpg" si hay más de una foto del mismo
     * momento para el mismo item). Punto único de generación de nombre,
     * para reutilizar en cualquier vista que liste/descargue fotos.
     */
    public function nombreDescriptivo(): string
    {
        $item = $this->checklistItem;
        $obra = $item?->checklist?->ordenTrabajo?->obra;

        $codigoObra = $obra?->codigo ?? 'SIN-OBRA';
        $descripcion = Str::slug($item?->descripcion ?? 'item') ?: 'item';
        $fecha = ($this->fecha_subida ?? now())->format('Y-m-d');
        $extension = pathinfo($this->url, PATHINFO_EXTENSION) ?: 'jpg';

        $hermanas = static::query()
            ->where('checklist_item_id', $this->checklist_item_id)
            ->where('momento', $this->momento)
            ->orderBy('id')
            ->pluck('id');

        $sufijo = "{$this->momento}_{$fecha}";

        if ($hermanas->count() > 1) {
            $indice = $hermanas->search($this->id);
            $sufijo .= '_'.(($indice === false ? 0 : $indice) + 1);
        }

        return "{$codigoObra}_{$descripcion}_{$sufijo}.{$extension}";
    }
}
