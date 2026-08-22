<?php

namespace App\Models;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Requerimiento extends Model
{
    protected $fillable = [
        'obra_id',
        'checklist_item_id',
        'requerimiento_original_id',
        'solicitado_por',
        'tipo',
        'estado',
        'fecha_solicitud',
        'aprobado_por',
        'fecha_aprobacion',
        'motivo_rechazo',
        'alistado_por',
        'fecha_alistamiento',
    ];

    protected function casts(): array
    {
        return [
            'tipo' => TipoRequerimiento::class,
            'estado' => EstadoRequerimiento::class,
            'fecha_solicitud' => 'datetime',
            'fecha_aprobacion' => 'datetime',
            'fecha_alistamiento' => 'datetime',
        ];
    }

    public function obra(): BelongsTo
    {
        return $this->belongsTo(Obra::class);
    }

    public function checklistItem(): BelongsTo
    {
        return $this->belongsTo(ChecklistItem::class);
    }

    public function requerimientoOriginal(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class, 'requerimiento_original_id');
    }

    public function solicitadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'solicitado_por');
    }

    public function aprobadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'aprobado_por');
    }

    public function alistadoPor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'alistado_por');
    }

    public function items(): HasMany
    {
        return $this->hasMany(RequerimientoItem::class);
    }

    public function puedeAprobar(?User $usuario): bool
    {
        if (! $usuario || $this->estado !== EstadoRequerimiento::Pendiente) {
            return false;
        }

        return $usuario->hasRole('administrador') || $usuario->hasRole($this->tipo->rolAprobador());
    }

    /**
     * Solo material/seguridad pasan por Almacén (marcar en alistamiento /
     * entregado a mano). Señalética tiene su propio flujo automático de 3
     * pasos vía RequerimientoItem (ver sincronizarEstadoSenaletica()).
     */
    public function puedeGestionarAlmacen(?User $usuario): bool
    {
        if ($this->tipo === TipoRequerimiento::Señaletica) {
            return false;
        }

        return $usuario?->hasAnyRole(['administrador', 'almacen']) ?? false;
    }

    public function puedeGestionarAcabados(?User $usuario): bool
    {
        if ($this->tipo !== TipoRequerimiento::Señaletica || $this->estado !== EstadoRequerimiento::Aprobado) {
            return false;
        }

        return $usuario?->hasAnyRole(['administrador', 'acabados']) ?? false;
    }

    public function puedeGestionarDespacho(?User $usuario): bool
    {
        if ($this->tipo !== TipoRequerimiento::Señaletica || $this->estado !== EstadoRequerimiento::EnAlistamiento) {
            return false;
        }

        return $usuario?->hasAnyRole(['administrador', 'despacho']) ?? false;
    }

    public function aprobar(User $usuario): void
    {
        $this->update([
            'estado' => EstadoRequerimiento::Aprobado,
            'aprobado_por' => $usuario->id,
            'fecha_aprobacion' => now(),
            'motivo_rechazo' => null,
        ]);
    }

    public function rechazar(string $motivo): void
    {
        $this->update([
            'estado' => EstadoRequerimiento::Rechazado,
            'motivo_rechazo' => $motivo,
            'aprobado_por' => null,
            'fecha_aprobacion' => null,
        ]);
    }

    public function marcarEnAlistamiento(User $usuario): void
    {
        $this->update([
            'estado' => EstadoRequerimiento::EnAlistamiento,
            'alistado_por' => $usuario->id,
        ]);
    }

    public function marcarEntregado(User $usuario): void
    {
        $this->update([
            'estado' => EstadoRequerimiento::Entregado,
            'alistado_por' => $usuario->id,
            'fecha_alistamiento' => now(),
        ]);
    }

    /**
     * Flujo especial de señalética (3 pasos, reutiliza los mismos estados
     * de EstadoRequerimiento pero alcanzados por un camino distinto al de
     * material/seguridad): aprobado -> [acabados prepara cada item] ->
     * en_alistamiento -> [despacho verifica cada item] -> entregado.
     * Bidireccional igual que ChecklistItem::sincronizarEstadoAutomatico():
     * si despacho rechaza un item ya en camino, el requerimiento retrocede
     * para que acabados lo vea de nuevo en su bandeja.
     */
    public function sincronizarEstadoSenaletica(?User $usuarioDespacho = null): void
    {
        if ($this->tipo !== TipoRequerimiento::Señaletica) {
            return;
        }

        // Fresco a propósito (no $this->items): evita operar sobre una
        // colección cacheada de una sincronización anterior en el mismo
        // objeto (ej. varias llamadas encadenadas sobre el mismo item).
        $items = $this->items()->get();

        if ($items->isEmpty()) {
            return;
        }

        if ($this->estado === EstadoRequerimiento::Aprobado) {
            if ($items->every(fn (RequerimientoItem $item) => $item->preparado)) {
                $this->update(['estado' => EstadoRequerimiento::EnAlistamiento]);
            }

            return;
        }

        if ($this->estado === EstadoRequerimiento::EnAlistamiento) {
            if ($items->contains(fn (RequerimientoItem $item) => ! $item->preparado)) {
                $this->update(['estado' => EstadoRequerimiento::Aprobado]);

                return;
            }

            if ($items->every(fn (RequerimientoItem $item) => $item->verificado_despacho)) {
                $this->update([
                    'estado' => EstadoRequerimiento::Entregado,
                    'alistado_por' => $usuarioDespacho?->id,
                    'fecha_alistamiento' => now(),
                ]);
            }
        }
    }
}
