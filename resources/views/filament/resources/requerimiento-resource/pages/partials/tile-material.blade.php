<button
    type="button"
    wire:click="elegirMaterial({{ $material->id }})"
    style="cursor: pointer; text-align: left; border: 1px solid {{ $this->carritoTieneMaterial($material->id) ? '#F59E0B' : '#e5e7eb' }}; border-radius: 10px; padding: 8px; background: #ffffff;"
>
    <div style="width: 100%; aspect-ratio: 1; border-radius: 8px; overflow: hidden; background: #F3F4F6; margin-bottom: 6px; display: flex; align-items: center; justify-content: center;">
        @if ($material->fotoUrl())
            <img src="{{ $material->fotoUrl() }}" alt="{{ $material->nombre }}" style="width: 100%; height: 100%; object-fit: contain; object-position: center; display: block;" />
        @else
            <x-heroicon-o-cube style="width: 28px; height: 28px; color: #9CA3AF;" />
        @endif
    </div>
    <p style="margin: 0; font-size: 12px; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
        {{ $material->nombre }}
    </p>
    <p style="margin: 2px 0 0; font-size: 10.5px; color: #9ca3af;">{{ $material->categoriaEfectiva()?->nombre }}</p>
    @if ($material->dimensiones())
        <p style="margin: 2px 0 0; font-size: 10.5px; font-weight: 600; color: #6b7280;">{{ $material->dimensiones() }}</p>
    @endif
    @if ($this->carritoTieneMaterial($material->id))
        <p style="margin: 4px 0 0; font-size: 11px; font-weight: 700; color: #F59E0B;">
            En el pedido: {{ $this->carritoCantidadMaterial($material->id) }} {{ $material->unidad_medida }}
        </p>
    @endif
</button>
