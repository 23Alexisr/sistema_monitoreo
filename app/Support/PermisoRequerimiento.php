<?php

namespace App\Support;

use App\Models\EspecialidadMaterial;
use App\Models\Material;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quién puede crear un requerimiento de materiales para una obra, y qué
 * catálogo le corresponde ver. No es un rol de Spatie: se verifica en
 * tiempo real, combinando la condición dinámica de encargado del día
 * (App\Support\EncargadoDelDia) con el permiso por especialidad
 * (tabla especialidades_material).
 */
final class PermisoRequerimiento
{
    /**
     * Roles de jefatura que pueden crear requerimientos directamente (sin
     * ser operario), habilitando la auto-aprobación de señalética descrita
     * en Requerimiento — ver CrearRequerimiento::enviar().
     */
    private const ROLES_JEFATURA_CREADORA = ['jefe_proyectos', 'jefe_ssoma'];

    public static function puedeCrear(?User $user, int $obraId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('administrador') || $user->hasAnyRole(self::ROLES_JEFATURA_CREADORA)) {
            return true;
        }

        if (! $user->hasRole('operario')) {
            return false;
        }

        if (EncargadoDelDia::activoPara($user, $obraId)) {
            return true;
        }

        return static::tieneEspecialidadPermitida($user);
    }

    /**
     * true = ve todo el catálogo activo (admin, jefatura creadora, o
     * encargado-hoy). false = el catálogo se filtra a su especialidad.
     */
    public static function catalogoCompleto(?User $user, int $obraId): bool
    {
        if (! $user) {
            return false;
        }

        if ($user->hasRole('administrador') || $user->hasAnyRole(self::ROLES_JEFATURA_CREADORA)) {
            return true;
        }

        return EncargadoDelDia::activoPara($user, $obraId);
    }

    public static function materialesVisibles(?User $user, int $obraId): Builder
    {
        $clienteId = Obra::find($obraId)?->cliente_id;

        $query = Material::query()
            ->where('activo', true)
            ->where(fn (Builder $q) => $q->whereNull('cliente_id')->when($clienteId, fn (Builder $q2) => $q2->orWhere('cliente_id', $clienteId)));

        if (static::catalogoCompleto($user, $obraId)) {
            return $query;
        }

        $especialidad = $user?->empleado?->especialidad;

        return $query->whereHas('especialidadesPermitidas', fn (Builder $q) => $q->where('especialidad', $especialidad));
    }

    public static function esVinilero(?User $user): bool
    {
        return ($user?->hasRole('operario') ?? false) && $user->empleado?->especialidad === 'vinilero';
    }

    protected static function tieneEspecialidadPermitida(User $user): bool
    {
        $especialidad = $user->empleado?->especialidad;

        if (! $especialidad) {
            return false;
        }

        return EspecialidadMaterial::query()->where('especialidad', $especialidad)->exists();
    }
}
