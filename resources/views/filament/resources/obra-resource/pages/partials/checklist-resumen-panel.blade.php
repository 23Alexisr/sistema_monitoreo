@php
    $cliente = $obra->cliente;
    $colorCliente = $cliente?->colorMarca() ?? \App\Models\Cliente::COLOR_MARCA_DEFECTO;
    $colorClienteOscuro = $cliente?->colorMarcaOscuro() ?? '#374151';
    $avance = $obra->avance_pct;

    $radio = 30;
    $circunferencia = 2 * M_PI * $radio;
    $offset = $circunferencia * (1 - min(100, max(0, $avance)) / 100);
@endphp

<div style="display: flex; flex-direction: column; gap: 14px;">
    {{-- Tarjeta de avance --}}
    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 18px; text-align: center;">
        <span style="display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-radius: 999px; background: {{ $colorCliente }}; color: {{ $colorClienteOscuro }}; padding: 4px 11px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
            {{ $cliente?->nombre ?? 'Sin cliente' }}
        </span>

        <h3 style="margin: 8px 0 16px; font-size: 15px; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            {{ $obra->nombre }}
        </h3>

        <div style="position: relative; width: 72px; height: 72px; margin: 0 auto;">
            <svg viewBox="0 0 72 72" width="72" height="72" style="transform: rotate(-90deg); transform-origin: 50% 50%;">
                <circle cx="36" cy="36" r="{{ $radio }}" fill="none" stroke="#f1f1f4" stroke-width="7" />
                <circle
                    cx="36" cy="36" r="{{ $radio }}" fill="none" stroke-width="7" stroke-linecap="round"
                    stroke="{{ $colorCliente }}"
                    style="stroke-dasharray: {{ $circunferencia }}; stroke-dashoffset: {{ $offset }}; transition: stroke-dashoffset .4s;"
                />
            </svg>
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 14px; font-weight: 700; color: #111827;">{{ $avance }}%</span>
            </div>
        </div>

        <p style="margin: 10px 0 0; font-size: 12.5px; color: #6b7280;">
            {{ $completadosGeneral }} de {{ $totalGeneral }} completados
        </p>
    </div>

    {{-- Tarjeta por categoría --}}
    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
        <p style="margin: 0 0 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
            Por categoría
        </p>

        <div style="display: flex; flex-direction: column; gap: 8px;">
            @forelse ($secciones as $seccion)
                <div style="display: flex; align-items: center; gap: 8px;">
                    <span style="width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0; background: {{ $seccion['color'] ?? '#9CA3AF' }};"></span>
                    <span style="flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        {{ $seccion['nombre'] }}
                    </span>
                    <span style="flex-shrink: 0; font-size: 12px; font-weight: 700; color: {{ $seccion['completadosCount'] === $seccion['total'] ? '#059669' : '#6b7280' }};">
                        {{ $seccion['completadosCount'] }}/{{ $seccion['total'] }}
                    </span>
                </div>
            @empty
                <p style="margin: 0; font-size: 12.5px; color: #9ca3af;">Sin categorías.</p>
            @endforelse
        </div>
    </div>

    {{-- Tarjeta siguiente pendiente --}}
    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
        <p style="margin: 0 0 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
            Siguiente pendiente
        </p>

        @if ($siguientePendiente)
            <p style="margin: 0 0 12px; font-size: 13.5px; font-weight: 600; color: #111827; overflow-wrap: break-word;">
                {{ $siguientePendiente->descripcion }}
            </p>

            <button
                type="button"
                wire:click="seleccionarItem({{ $siguientePendiente->id }})"
                style="cursor: pointer; width: 100%; box-sizing: border-box; display: flex; align-items: center; justify-content: center; gap: 6px; border: none; border-radius: 10px; background: #F59E0B; color: #ffffff; padding: 9px 14px; font-size: 12.5px; font-weight: 700;"
            >
                Ir al item
                <x-heroicon-o-arrow-right style="width: 14px; height: 14px;" />
            </button>
        @else
            <p style="margin: 0; font-size: 13px; color: #059669; font-weight: 600;">
                ✅ No quedan items pendientes.
            </p>
        @endif
    </div>
</div>
