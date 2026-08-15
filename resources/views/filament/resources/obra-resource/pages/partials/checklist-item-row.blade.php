@php
    $totalFotos = $item->fotos->count();
    $indent = 14 * $nivel;
@endphp

<button
    type="button"
    wire:click="seleccionarItem({{ $item->id }})"
    style="cursor: pointer; width: 100%; box-sizing: border-box; display: flex; align-items: center; gap: 10px; text-align: left; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 12px; padding: 12px 14px; margin-left: {{ $indent }}px; width: calc(100% - {{ $indent }}px);"
>
    @if ($item->completado)
        <x-heroicon-o-check-circle style="width: 26px; height: 26px; flex-shrink: 0; color: #10B981;" />
    @else
        <svg viewBox="0 0 24 24" fill="none" style="width: 26px; height: 26px; flex-shrink: 0;">
            <circle cx="12" cy="12" r="9" stroke="#d1d5db" stroke-width="2" />
        </svg>
    @endif

    <span style="flex: 1; min-width: 0; font-size: 14px; font-weight: 600; color: {{ $item->completado ? '#9ca3af' : '#111827' }}; text-decoration: {{ $item->completado ? 'line-through' : 'none' }}; overflow-wrap: break-word;">
        {{ $item->descripcion }}
    </span>

    @if ($totalFotos > 0)
        <span style="flex-shrink: 0; display: flex; align-items: center; gap: 3px; font-size: 11.5px; font-weight: 700; color: #6b7280;">
            <x-heroicon-o-camera style="width: 14px; height: 14px;" />
            {{ $totalFotos }}
        </span>
    @endif
</button>

@if ($item->children->isNotEmpty())
    @foreach ($item->children as $hijo)
        @include('filament.resources.obra-resource.pages.partials.checklist-item-row', ['item' => $hijo, 'nivel' => $nivel + 1])
    @endforeach
@endif
