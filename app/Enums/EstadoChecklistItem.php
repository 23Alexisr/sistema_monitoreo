<?php

namespace App\Enums;

enum EstadoChecklistItem: string
{
    case Pendiente = 'pendiente';
    case PendienteAprobacion = 'pendiente_aprobacion';
    case Completado = 'completado';
    case Rechazado = 'rechazado';

    public function label(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::PendienteAprobacion => 'Pendiente de aprobación',
            self::Completado => 'Completado',
            self::Rechazado => 'Rechazado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => '#9CA3AF',
            self::PendienteAprobacion => '#0EA5E9',
            self::Completado => '#10B981',
            self::Rechazado => '#DC2626',
        };
    }
}
