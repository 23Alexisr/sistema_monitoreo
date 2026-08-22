<?php

namespace App\Support;

use App\Models\Material;
use App\Models\Obra;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Quién puede crear un requerimiento de materiales para una obra. No es
 * un rol de Spatie: se verifica en tiempo real, combinando la condición
 * dinámica de encargado del día (App\Support\EncargadoDelDia) con la
 * especialidad electricista.
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

        return $user->empleado?->especialidad === 'electricista';
    }

    /**
     * Quien llega hasta acá ya pasó por puedeCrear() (electricista o
     * encargado del día); la separación material/señalética la resuelve
     * el flujo (modoFlujo en CrearRequerimiento), no un permiso por
     * categoría o material.
     */
    public static function materialesVisibles(?User $user, int $obraId): Builder
    {
        $clienteId = Obra::find($obraId)?->cliente_id;

        return Material::query()
            ->where('activo', true)
            ->where(fn (Builder $q) => $q->whereNull('cliente_id')->when($clienteId, fn (Builder $q2) => $q2->orWhere('cliente_id', $clienteId)));
    }
}
