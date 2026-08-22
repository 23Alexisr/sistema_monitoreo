<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'email',
        'password',
        'email_verified_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function empleado(): HasOne
    {
        return $this->hasOne(Empleado::class);
    }

    public function fotosSubidas(): HasMany
    {
        return $this->hasMany(Foto::class, 'subido_por');
    }

    public function obrasComoJefeProyecto(): BelongsToMany
    {
        return $this->belongsToMany(Obra::class, 'obra_jefe_proyecto', 'jefe_proyecto_id', 'obra_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->hasAnyRole(['administrador', 'jefe_planta', 'jefe_ssoma', 'jefe_proyectos', 'almacen', 'despacho', 'acabados', 'operario']);
    }

    public function getFilamentName(): string
    {
        return $this->empleado?->nombre_completo ?? $this->email;
    }

    public function getFilamentAvatarUrl(): ?string
    {
        return $this->empleado?->avatarUrl();
    }
}
