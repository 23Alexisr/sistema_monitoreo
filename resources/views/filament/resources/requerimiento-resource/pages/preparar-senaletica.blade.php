@php
    $requerimiento = $this->getRequerimiento();
    $items = $this->getItems();
    $total = $items->count();
    $preparados = $items->where('preparado', true)->count();
    $completo = $total > 0 && $preparados === $total;
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
                <p style="margin: 0 0 4px; font-size: 14px; font-weight: 700; color: #065F46;">Todo preparado</p>
                <p style="margin: 0; font-size: 12.5px; color: #059669;">Este pedido ya pasó a despacho para su verificación.</p>
                <a href="{{ \App\Filament\Resources\RequerimientoResource::getUrl('index') }}" style="display: inline-block; margin-top: 14px; text-decoration: none; border-radius: 10px; background: #059669; color: #ffffff; padding: 10px 20px; font-size: 13px; font-weight: 700;">
                    Volver a la bandeja
                </a>
            </div>
        @else
            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 4px;">
                    <p style="margin: 0; font-size: 13px; font-weight: 700; color: #374151;">Checklist de preparación</p>
                    <span style="font-size: 12.5px; font-weight: 700; color: #9ca3af;">{{ $preparados }} / {{ $total }}</span>
                </div>
                <div style="height: 6px; border-radius: 999px; background: #F3F4F6; overflow: hidden;">
                    <div style="height: 100%; width: {{ $total > 0 ? round(($preparados / $total) * 100) : 0 }}%; background: #F59E0B; border-radius: 999px;"></div>
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach ($items as $item)
                    <button
                        type="button"
                        wire:click="alternarPreparado({{ $item->id }})"
                        style="cursor: pointer; text-align: left; display: flex; align-items: center; gap: 12px; border-radius: 14px; border: 1.5px solid {{ $item->preparado ? '#10B981' : '#e5e7eb' }}; background: {{ $item->preparado ? '#ECFDF5' : '#ffffff' }}; padding: 14px 16px;"
                    >
                        <div style="flex: none; width: 28px; height: 28px; border-radius: 50%; border: 2px solid {{ $item->preparado ? '#10B981' : '#d1d5db' }}; background: {{ $item->preparado ? '#10B981' : '#ffffff' }}; display: flex; align-items: center; justify-content: center;">
                            @if ($item->preparado)
                                <x-heroicon-o-check style="width: 16px; height: 16px; color: #ffffff;" />
                            @endif
                        </div>
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
                        </div>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
</x-filament-panels::page>
