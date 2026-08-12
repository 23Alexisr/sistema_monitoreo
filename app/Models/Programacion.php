<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Programacion extends Model
{
    use HasFactory;

    protected $table = 'programacion';

    protected $fillable = [
        'obra_id',
        'user_id',
        'rol_obra',
        'fecha_inicio',
        'fecha_fin',
    ];

    protected function casts(): array
    {
        return [
            'fecha_inicio' => 'date',
            'fecha_fin' => 'date',
        ];
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function tieneSolapamiento(int $userId, string $fechaInicio, string $fechaFin, ?int $ignorarId = null): bool
    {
        return static::query()
            ->where('user_id', $userId)
            ->when($ignorarId, fn ($query) => $query->where('id', '!=', $ignorarId))
            ->where('fecha_inicio', '<=', $fechaFin)
            ->where('fecha_fin', '>=', $fechaInicio)
            ->exists();
    }
}
