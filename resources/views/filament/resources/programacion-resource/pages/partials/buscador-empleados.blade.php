@php
    $termino = trim($busqueda);
@endphp

<div style="border-radius: 10px; border: 1px solid var(--border-strong, #e5e7eb); padding: 10px 12px; margin-bottom: 4px;">
    <div style="display: flex; align-items: center; gap: 8px;">
        <x-heroicon-o-magnifying-glass style="width: 16px; height: 16px; color: #9ca3af; flex-shrink: 0;" />
        <input
            type="text"
            wire:model.live.debounce.350ms="busquedaEmpleado"
            placeholder="Buscar empleado..."
            style="flex: 1; min-width: 0; border: none; outline: none; font-size: 13.5px; background: transparent; color: inherit;"
        />
    </div>

    @if ($termino !== '')
        <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 4px; max-height: 220px; overflow-y: auto;">
            @forelse ($resultados as $empleado)
                <button
                    type="button"
                    wire:click="agregarEmpleado({{ $empleado->id }})"
                    style="cursor: pointer; display: flex; align-items: center; gap: 8px; border: none; background: var(--surface-1, #f9fafb); border-radius: 8px; padding: 6px 8px; text-align: left;"
                >
                    <img src="{{ $empleado->avatarUrl() }}" alt="" style="width: 22px; height: 22px; border-radius: 999px; object-fit: cover; flex-shrink: 0;" />
                    <span style="flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $empleado->nombre_completo }}
                    </span>
                    <x-heroicon-o-plus-circle style="width: 16px; height: 16px; color: #F59E0B; flex-shrink: 0;" />
                </button>
            @empty
                <p style="margin: 4px 0 0; font-size: 12px; color: #9ca3af;">Sin resultados.</p>
            @endforelse
        </div>
    @endif
</div>
