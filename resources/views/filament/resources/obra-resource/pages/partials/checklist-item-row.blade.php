@php
    use App\Enums\EstadoChecklistItem;

    $totalFotos = $item->fotos->count();
    $faltaFoto = $item->requiere_foto && $item->estado === EstadoChecklistItem::Pendiente && $totalFotos === 0;
    $colorCategoria = $color ?? '#9CA3AF';

    $bordeEstado = match (true) {
        $item->estado === EstadoChecklistItem::Rechazado => '#DC2626',
        $item->estado === EstadoChecklistItem::PendienteAprobacion => '#0EA5E9',
        $faltaFoto => '#F59E0B',
        default => '#e5e7eb',
    };
@endphp

<div
    id="item-{{ $item->id }}"
    wire:key="checklist-item-{{ $item->id }}"
    style="border-radius: 12px; border: 1px solid {{ $bordeEstado }}; background: #ffffff; overflow: hidden; scroll-margin-top: 16px; {{ $bordeEstado !== '#e5e7eb' ? 'box-shadow: 0 0 0 1px '.$bordeEstado.';' : '' }}"
>
    <button
        type="button"
        wire:click="seleccionarItem({{ $item->id }})"
        style="cursor: pointer; width: 100%; box-sizing: border-box; display: flex; align-items: center; gap: 10px; text-align: left; border: none; background: none; padding: 12px 14px 12px 0;"
    >
        <span style="width: 4px; align-self: stretch; flex-shrink: 0; background: {{ $colorCategoria }};"></span>

        @if ($item->requiere_foto && $totalFotos > 0)
            <span style="flex-shrink: 0; display: flex; flex-direction: column; align-items: center; gap: 1px; color: {{ $item->estado->color() }};">
                <x-heroicon-o-camera style="width: 22px; height: 22px;" />
                <span style="font-size: 10px; font-weight: 700;">{{ $totalFotos }}</span>
            </span>
        @elseif ($item->estado === EstadoChecklistItem::Completado)
            <x-heroicon-o-check-circle style="width: 26px; height: 26px; flex-shrink: 0; color: #10B981;" />
        @else
            <svg viewBox="0 0 24 24" fill="none" style="width: 26px; height: 26px; flex-shrink: 0;">
                <circle cx="12" cy="12" r="9" stroke="#d1d5db" stroke-width="2" />
            </svg>
        @endif

        @if ($contador = ($contadoresRepeticion[$item->id] ?? null))
            <span style="flex-shrink: 0; font-size: 11px; font-weight: 700; color: #9ca3af; white-space: nowrap;">
                {{ $contador }}
            </span>
        @endif

        <span style="flex: 1; min-width: 0; font-size: 14px; font-weight: 600; color: {{ $item->estado === EstadoChecklistItem::Completado ? '#9ca3af' : '#111827' }}; text-decoration: {{ $item->estado === EstadoChecklistItem::Completado ? 'line-through' : 'none' }}; overflow-wrap: break-word;">
            {{ $item->descripcion }}
        </span>

        @if ($item->requiere_foto && in_array($item->estado, [EstadoChecklistItem::PendienteAprobacion, EstadoChecklistItem::Rechazado], true))
            <span style="flex-shrink: 0; border-radius: 999px; background: {{ $item->estado === EstadoChecklistItem::Rechazado ? '#FEF2F2' : '#F0F9FF' }}; color: {{ $item->estado === EstadoChecklistItem::Rechazado ? '#991B1B' : '#0369A1' }}; padding: 3px 8px; font-size: 10.5px; font-weight: 700; white-space: nowrap;">
                {{ $item->estado === EstadoChecklistItem::Rechazado ? 'Rechazado' : 'En revisión' }}
            </span>
        @elseif ($item->requiere_foto && $item->estado === EstadoChecklistItem::Pendiente)
            <span style="flex-shrink: 0; border-radius: 999px; background: #FFFBEB; color: #92400E; padding: 3px 8px; font-size: 10.5px; font-weight: 700; white-space: nowrap;">
                Requiere foto
            </span>
        @endif

        <span
            wire:click.stop="seleccionarItem({{ $item->id }})"
            style="display: flex; align-items: center; flex-shrink: 0;"
        >
            <x-heroicon-o-chevron-right style="width: 16px; height: 16px; color: #9ca3af;" />
        </span>
    </button>
</div>
