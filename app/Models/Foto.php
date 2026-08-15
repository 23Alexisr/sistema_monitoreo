<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        $sincronizar = fn (Foto $foto) => $foto->checklistItem?->sincronizarCompletadoAutomatico();

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
}
