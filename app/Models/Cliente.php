<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Cliente extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre',
        'ruc',
        'logo',
        'color_marca',
    ];

    public function obras(): HasMany
    {
        return $this->hasMany(Obra::class);
    }

    public function iniciales(): string
    {
        $palabras = collect(preg_split('/\s+/', trim($this->nombre)))->filter();

        $iniciales = $palabras->take(2)->map(fn ($p) => mb_strtoupper(mb_substr($p, 0, 1)))->implode('');

        return $iniciales !== '' ? $iniciales : '?';
    }

    public function colorAvatar(): string
    {
        $paleta = ['#F59E0B', '#3B82F6', '#10B981', '#EF4444', '#8B5CF6', '#EC4899', '#14B8A6', '#F97316'];

        return $paleta[crc32($this->nombre) % count($paleta)];
    }

    public const COLOR_MARCA_DEFECTO = '#6B7280';

    public function colorMarca(): string
    {
        return $this->color_marca ?: self::COLOR_MARCA_DEFECTO;
    }

    public function colorMarcaOscuro(float $factor = 0.55): string
    {
        $hex = ltrim($this->colorMarca(), '#');

        if (strlen($hex) !== 6) {
            return '#374151';
        }

        [$r, $g, $b] = [
            (int) hexdec(substr($hex, 0, 2)),
            (int) hexdec(substr($hex, 2, 2)),
            (int) hexdec(substr($hex, 4, 2)),
        ];

        return sprintf(
            '#%02x%02x%02x',
            (int) round($r * $factor),
            (int) round($g * $factor),
            (int) round($b * $factor),
        );
    }

    public function avatarUrl(): string
    {
        if ($this->logo) {
            return Storage::disk('public')->url($this->logo);
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
