<?php

namespace App\Enums;

enum EstadoRequerimiento: string
{
    case Pendiente = 'pendiente';
    case Aprobado = 'aprobado';
    case Rechazado = 'rechazado';
    case EnAlistamiento = 'en_alistamiento';
    case Entregado = 'entregado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Aprobado => 'Aprobado',
            self::Rechazado => 'Rechazado',
            self::EnAlistamiento => 'En alistamiento',
            self::Entregado => 'Entregado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => '#9CA3AF',
            self::Aprobado => '#0EA5E9',
            self::Rechazado => '#DC2626',
            self::EnAlistamiento => '#F59E0B',
            self::Entregado => '#10B981',
        };
    }
}
