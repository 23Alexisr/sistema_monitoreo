<?php

namespace App\Support;

class OrdenValidator
{
    public static function sugerido(?int $maxActual): int
    {
        return ($maxActual ?? 0) + 1;
    }

    public static function advertencia(int $orden, ?int $maxOtros, ?string $duplicadoNombre): ?string
    {
        if ($duplicadoNombre !== null) {
            return "\"{$duplicadoNombre}\" ya usa el orden {$orden}.";
        }

        $maxOtros ??= 0;

        if ($maxOtros <= 0) {
            return null;
        }

        if ($orden > $maxOtros * 10) {
            return "El número {$orden} es mucho mayor al máximo actual ({$maxOtros}). ¿Fue un error de tipeo?";
        }

        if (($orden - $maxOtros) > 5) {
            return "Hay un salto grande respecto al máximo actual ({$maxOtros}). ¿Fue intencional?";
        }

        return null;
    }
}
