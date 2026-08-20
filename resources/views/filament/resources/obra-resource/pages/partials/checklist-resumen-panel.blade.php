@php
    $puedePedirMateriales = \App\Support\PermisoRequerimiento::puedeCrear(auth()->user(), $obra->id);
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
        @if ($cliente?->logoUrl())
            <span style="display: inline-flex; align-items: center; justify-content: center; max-width: 100%; box-sizing: border-box; border-radius: 8px; background: #ffffff; border: 1px solid #e5e7eb; padding: 4px 10px;">
                <img src="{{ $cliente->logoUrl() }}" alt="{{ $cliente->nombre }}" style="display: block; max-width: 100%; max-height: 20px; object-fit: contain;" />
            </span>
        @else
            <span style="display: inline-block; max-width: 100%; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-radius: 999px; background: {{ $colorCliente }}; color: {{ $colorClienteOscuro }}; padding: 4px 11px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                {{ $cliente?->nombre ?? 'Sin cliente' }}
            </span>
        @endif

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
        <p style="margin: 2px 0 0; font-size: 10.5px; color: #9ca3af;">
            Ponderado por días estimados de cada trabajo
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

                @if ($seccion['subgrupos'])
                    @foreach ($seccion['subgrupos'] as $subgrupo)
                        @continue(! $subgrupo['nombre'])
                        <div style="display: flex; align-items: center; gap: 8px; padding-left: 16px;">
                            <span style="flex: 1; min-width: 0; font-size: 11.5px; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $subgrupo['nombre'] }}
                            </span>
                            <span style="flex-shrink: 0; font-size: 11px; font-weight: 600; color: #9ca3af;">
                                {{ $subgrupo['completadosCount'] }}/{{ $subgrupo['total'] }}
                            </span>
                        </div>
                    @endforeach
                @endif
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
                @if ($contador = ($contadoresRepeticion[$siguientePendiente->id] ?? null))
                    <span style="color: #9ca3af; font-weight: 700; font-size: 11.5px;">{{ $contador }}</span>
                @endif
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

    @if ($puedePedirMateriales)
        <a
            href="{{ \App\Filament\Resources\RequerimientoResource::getUrl('create', ['obra_id' => $obra->id, 'tipo' => 'material']) }}"
            style="display: flex; align-items: center; justify-content: center; gap: 6px; border-radius: 14px; border: none; background: #F59E0B; color: #ffffff; padding: 12px; font-size: 13px; font-weight: 700; text-decoration: none;"
        >
            <x-heroicon-o-cube style="width: 16px; height: 16px;" />
            Pedir materiales
        </a>
    @endif

    {{-- Documentos y OT (solo lectura) --}}
    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
        <p style="margin: 0 0 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
            Orden de trabajo
        </p>

        @if ($obra->ordenTrabajo)
            <p style="margin: 0; font-size: 12.5px; font-weight: 600; color: #374151;">
                N.° {{ $obra->ordenTrabajo->numero_ot }} · {{ ucfirst(str_replace('_', ' ', $obra->ordenTrabajo->estado)) }}
            </p>
            @if ($obra->ordenTrabajo->alcance)
                <p style="margin: 6px 0 0; font-size: 12px; color: #6b7280; overflow-wrap: break-word;">
                    {{ $obra->ordenTrabajo->alcance }}
                </p>
            @endif
        @else
            <p style="margin: 0; font-size: 12.5px; color: #9ca3af;">Sin OT registrada.</p>
        @endif
    </div>

    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
        <p style="margin: 0 0 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
            Documentos
        </p>

        @forelse ($obra->documentos as $documento)
            <a
                href="{{ $documento->urlPublica() }}"
                target="_blank"
                style="display: flex; align-items: center; gap: 6px; margin-bottom: 6px; font-size: 12.5px; color: #374151; text-decoration: none;"
            >
                <x-heroicon-o-document style="width: 14px; height: 14px; flex-shrink: 0; color: #9ca3af;" />
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $documento->tipoLabel() }}</span>
            </a>
        @empty
            <p style="margin: 0; font-size: 12.5px; color: #9ca3af;">Sin documentos.</p>
        @endforelse
    </div>
</div>
