<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Empleado extends Model
{
    use HasFactory;

    public const ROLES_OBRA = ['supervisor', 'jefe_cuadrilla', 'operario'];

    protected $fillable = [
        'user_id',
        'nombre_completo',
        'dni',
        'telefono',
        'foto',
        'especialidad',
        'estado',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function programaciones(): HasMany
    {
        return $this->hasMany(Programacion::class);
    }

    public function rolPrincipal(): ?string
    {
        $roles = $this->user?->getRoleNames() ?? collect();

        foreach (static::ROLES_OBRA as $rol) {
            if ($roles->contains($rol)) {
                return $rol;
            }
        }

        return null;
    }

    public function iniciales(): string
    {
        $palabras = collect(preg_split('/\s+/', trim($this->nombre_completo)))->filter();

        $iniciales = $palabras->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $iniciales !== '' ? $iniciales : '?';
    }

    public function colorAvatar(): string
    {
        $paleta = ['#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];

        return $paleta[crc32($this->nombre_completo) % count($paleta)];
    }

    public function avatarUrl(): string
    {
        if ($this->foto) {
            return Storage::disk('public')->url($this->foto);
        }

        $color = $this->colorAvatar();
        $iniciales = $this->iniciales();

        $svg = <<<SVG
        <svg xmlns="http://www.w3.org/2000/svg" width="80" height="80">
            <rect width="80" height="80" rx="40" fill="{$color}" />
            <text x="50%" y="50%" dy=".1em" text-anchor="middle" dominant-baseline="middle" font-family="Arial, sans-serif" font-size="30" font-weight="bold" fill="#ffffff">{$iniciales}</text>
        </svg>
        SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }
}
