<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Documento extends Model
{
    use HasFactory;

    public const TIPOS = [
        'plano' => 'Plano',
        'fotomontaje' => 'Fotomontaje',
        'foto_avance' => 'Foto de avance',
        'otro' => 'Otro',
    ];

    protected $fillable = [
        'obra_id',
        'tipo',
        'url',
    ];

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function tipoLabel(): string
    {
        return static::TIPOS[$this->tipo] ?? $this->tipo;
    }

    public function esImagen(): bool
    {
        return in_array(strtolower(pathinfo($this->url, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
    }

    public function extension(): string
    {
        return strtoupper(pathinfo($this->url, PATHINFO_EXTENSION));
    }

    public function urlPublica(): string
    {
        return Storage::disk('public')->url($this->url);
    }
}
