<?php

namespace App\Support;

use App\Models\Programacion;
use App\Models\User;

/**
 * Condición dinámica (no es un rol de Spatie): un operario con un registro
 * en `programacion` marcado `es_encargado = true` para la obra y fecha
 * actual obtiene permisos temporales adicionales solo sobre esa obra
 * mientras dure la condición. Se verifica en tiempo real en cada request,
 * no se asigna como rol permanente.
 */
final class EncargadoDelDia
{
    public static function activoPara(?User $user, int $obraId): bool
    {
        $empleadoId = $user?->empleado?->id;

        if (! $empleadoId) {
            return false;
        }

        return Programacion::query()
            ->where('obra_id', $obraId)
            ->where('empleado_id', $empleadoId)
            ->whereDate('fecha', now()->toDateString())
            ->where('es_encargado', true)
            ->exists();
    }
}
