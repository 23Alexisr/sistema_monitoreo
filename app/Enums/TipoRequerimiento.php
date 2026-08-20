<?php

namespace App\Enums;

use Illuminate\Support\Str;

enum TipoRequerimiento: string
{
    case Material = 'material';
    case Seguridad = 'seguridad';
    case Señaletica = 'señaletica';

    public function label(): string
    {
        return match ($this) {
            self::Material => 'Material',
            self::Seguridad => 'Seguridad',
            self::Señaletica => 'Señalética',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Material => '#0EA5E9',
            self::Seguridad => '#DC2626',
            self::Señaletica => '#F59E0B',
        };
    }

    /**
     * Rol de Spatie que aprueba/rechaza requerimientos de este tipo
     * (administrador siempre puede, además de este rol).
     */
    public function rolAprobador(): string
    {
        return match ($this) {
            self::Material => 'jefe_planta',
            self::Seguridad => 'jefe_ssoma',
            self::Señaletica => 'jefe_proyectos',
        };
    }

    /**
     * Deriva el tipo de requerimiento a partir del nombre de la categoría
     * (efectiva) de un material. "seguridad"/"señaletica" mapean directo;
     * cualquier otra categoría (electricidad, etc.) o null (categoría no
     * determinada) cae en el tipo genérico "material".
     */
    public static function desdeCategoriaNombre(?string $nombreCategoria): self
    {
        $normalizada = Str::of($nombreCategoria ?? '')->ascii()->lower()->toString();

        return match (true) {
            $normalizada === 'seguridad' => self::Seguridad,
            $normalizada === 'senaletica' => self::Señaletica,
            default => self::Material,
        };
    }
}
