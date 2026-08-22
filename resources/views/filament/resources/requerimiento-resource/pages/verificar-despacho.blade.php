@php
    $requerimiento = $this->getRequerimiento();
    $items = $this->getItems();
    $total = $items->count();
    $verificados = $items->where('verificado_despacho', true)->count();
    $completo = $total > 0 && $verificados === $total;
    // Rechazar un ítem hace retroceder TODO el pedido a manos de acabados
    // (ver Requerimiento::sincronizarEstadoSenaletica) — si eso pasó
    // mientras esta pantalla estaba abierta, ya no hay nada que verificar
    // acá hasta que acabados lo vuelva a completar.
    $disponible = $requerimiento->puedeGestionarDespacho(auth()->user());
@endphp

<x-filament-panels::page>
    <div style="display: flex; flex-direction: column; gap: 14px;">
        <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
            <p style="margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">Obra</p>
            <p style="margin: 4px 0 0; font-size: 15px; font-weight: 700; color: #111827;">{{ $requerimiento->obra->nombre }}</p>
        </div>

        @if ($completo)
            <div style="border-radius: 14px; border: 1px solid #10B981; background: #ECFDF5; padding: 20px; text-align: center;">
                <x-heroicon-o-check-circle style="width: 32px; height: 32px; color: #059669; margin: 0 auto 8px;" />
                <p style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #065F46;">Todo verificado</p>
                <p style="margin: 0; font-size: 12.5px; color: #059669;">Este pedido ya quedó entregado.</p>
                <a href="{{ \App\Filament\Resources\RequerimientoResource::getUrl('index') }}" style="display: inline-block; margin-top: 14px; text-decoration: none; border-radius: 10px; background: #059669; color: #ffffff; padding: 10px 20px; font-size: 13px; font-weight: 700;">
                    Volver a la bandeja
                </a>
            </div>
        @elseif (! $disponible)
            <div style="border-radius: 14px; border: 1px solid #F59E0B; background: #FFFBEB; padding: 20px; text-align: center;">
                <x-heroicon-o-arrow-uturn-left style="width: 32px; height: 32px; color: #92400E; margin: 0 auto 8px;" />
                <p style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #92400E;">Este pedido volvió a preparación</p>
                <p style="margin: 0; font-size: 12.5px; color: #92400E;">Se rechazó un ítem, así que vuelve a manos de acabados. Ya no está disponible para verificar acá.</p>
                <a href="{{ \App\Filament\Resources\RequerimientoResource::getUrl('index') }}" style="display: inline-block; margin-top: 14px; text-decoration: none; border-radius: 10px; background: #92400E; color: #ffffff; padding: 10px 20px; font-size: 13px; font-weight: 700;">
                    Volver a la bandeja
                </a>
            </div>
        @else
            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <p style="margin: 0; font-size: 13px; font-weight: 700; color: #374151;">Checklist de verificación</p>
                    <span style="font-size: 12.5px; font-weight: 700; color: #9ca3af;">{{ $verificados }} / {{ $total }}</span>
                </div>
                <div style="height: 6px; border-radius: 999px; background: #F3F4F6; overflow: hidden;">
                    <div style="height: 100%; width: {{ $total > 0 ? round(($verificados / $total) * 100) : 0 }}%; background: #F59E0B; border-radius: 999px;"></div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($items as $item)
                    <div style="display: flex; align-items: center; gap: 10px; border-radius: 14px; border: 1.5px solid {{ $item->verificado_despacho ? '#10B981' : '#e5e7eb' }}; background: {{ $item->verificado_despacho ? '#ECFDF5' : '#ffffff' }}; padding: 14px 16px;">
                        <div style="flex: 1; min-width: 0;">
                            <p style="margin: 0; font-size: 14px; font-weight: 700; color: #111827;">
                                {{ $item->nombreParaMostrar() }}
                            </p>
                            <p style="margin: 2px 0 0; font-size: 12.5px; color: #6b7280;">
                                {{ $item->cantidad }} {{ $item->material?->unidad_medida }}
                                @if ($item->dimensionesEfectivas())
                                    · {{ $item->dimensionesEfectivas() }}
                                @endif
                            </p>
                            @if ($item->verificado_despacho)
                                <p style="margin: 4px 0 0; font-size: 11px; color: #059669; font-weight: 600;">
                                    Verificado{{ $item->verificadoPor?->empleado?->nombre_completo ? ' por '.$item->verificadoPor->empleado->nombre_completo : '' }}
                                </p>
                            @endif
                        </div>

                        @if (! $item->verificado_despacho)
                            <button type="button" wire:click="abrirRechazo({{ $item->id }})" style="flex: none; cursor: pointer; border: 1px solid #e5e7eb; background: #ffffff; color: #DC2626; border-radius: 10px; padding: 9px 12px; font-size: 12.5px; font-weight: 700;">
                                Rechazar
                            </button>
                            <button type="button" wire:click="verificarItem({{ $item->id }})" style="flex: none; cursor: pointer; border: none; background: #059669; color: #ffffff; border-radius: 10px; padding: 9px 16px; font-size: 12.5px; font-weight: 700;">
                                Verificar
                            </button>
                        @else
                            <x-heroicon-o-check-circle style="flex: none; width: 24px; height: 24px; color: #10B981;" />
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- Modal de rechazo --}}
    @if ($this->itemParaRechazarId)
        <div style="position: fixed; top: 0; right: 0; bottom: 0; left: 0; min-height: 100dvh; background: rgba(0, 0, 0, 0.55); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 24px 16px; z-index: 55;" wire:click.self="cancelarRechazo">
            <div style="width: 100%; max-width: 400px; background: #ffffff; border-radius: 16px; padding: 20px;">
                <p style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #111827;">Rechazar item</p>
                <p style="margin: 0 0 12px; font-size: 12px; color: #9ca3af;">Vuelve a manos de acabados para corregirse.</p>

                <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">¿Qué está mal?</label>
                <textarea
                    wire:model="motivoRechazoTexto"
                    rows="3"
                    style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13.5px; font-family: inherit; resize: vertical; margin-bottom: 14px;"
                ></textarea>

                <div style="display: flex; gap: 8px;">
                    <button type="button" wire:click="cancelarRechazo" style="flex: 1; cursor: pointer; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700; color: #374151;">
                        Cancelar
                    </button>
                    <button type="button" wire:click="confirmarRechazo" style="flex: 1; cursor: pointer; border: none; background: #DC2626; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700;">
                        Rechazar
                    </button>
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
