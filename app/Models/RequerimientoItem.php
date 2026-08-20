<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class RequerimientoItem extends Model
{
    protected $fillable = [
        'requerimiento_id',
        'material_id',
        'descripcion_manual',
        'medidas',
        'foto_referencia',
        'cantidad',
        'es_sugerido',
        'preparado',
        'verificado_despacho',
        'observaciones',
    ];

    protected function casts(): array
    {
        return [
            'cantidad' => 'decimal:2',
            'es_sugerido' => 'boolean',
            'preparado' => 'boolean',
            'verificado_despacho' => 'boolean',
        ];
    }

    public function requerimiento(): BelongsTo
    {
        return $this->belongsTo(Requerimiento::class);
    }

    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    public function esCatalogado(): bool
    {
        return $this->material_id !== null;
    }

    public function nombreParaMostrar(): string
    {
        return $this->esCatalogado() ? ($this->material?->nombre ?? '—') : ($this->descripcion_manual ?? 'Item sin descripción');
    }

    public function fotoReferenciaUrl(): ?string
    {
        return $this->foto_referencia ? Storage::disk('public')->url($this->foto_referencia) : null;
    }

    /**
     * Marcado por el operario con especialidad "vinilero" a medida que
     * fabrica cada pieza de un pedido de señalética.
     */
    public function marcarPreparado(): void
    {
        $this->update(['preparado' => true]);
        $this->requerimiento->sincronizarEstadoSenaletica();
    }

    public function desmarcarPreparado(): void
    {
        $this->update(['preparado' => false, 'verificado_despacho' => false]);
        $this->requerimiento->sincronizarEstadoSenaletica();
    }

    /**
     * Marcado por el rol "despacho" al verificar que lo preparado coincide
     * con lo pedido.
     */
    public function verificarDespacho(User $usuario): void
    {
        $this->update(['verificado_despacho' => true]);
        $this->requerimiento->sincronizarEstadoSenaletica($usuario);
    }

    /**
     * Rechazo puntual de un item por despacho: vuelve a manos del vinilero
     * sin rechazar todo el requerimiento.
     */
    public function rechazarDespacho(string $motivo): void
    {
        $this->update(['preparado' => false, 'verificado_despacho' => false, 'observaciones' => $motivo]);
        $this->requerimiento->sincronizarEstadoSenaletica();
    }
}
