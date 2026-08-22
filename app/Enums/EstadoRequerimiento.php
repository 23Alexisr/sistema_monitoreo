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
            self::EnAlistamiento => 'En proceso',
            self::Entregado => 'Entregado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Aprobado => 'info',
            self::Rechazado => 'danger',
            self::EnAlistamiento => 'warning',
            self::Entregado => 'success',
        };
    }
}
