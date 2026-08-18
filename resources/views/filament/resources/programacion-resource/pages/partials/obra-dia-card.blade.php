@php
    $obra = $item->obra;
    $cliente = $obra?->cliente;
    $color = $cliente?->colorMarca() ?? \App\Models\Cliente::COLOR_MARCA_DEFECTO;
    $visibles = $item->registros->take(4);
    $restantes = $item->registros->count() - $visibles->count();
    $puedeVerDetalle = $obra && ! auth()->user()?->hasRole('operario');

    $rolColores = [
        'supervisor' => '#F59E0B',
        'jefe_cuadrilla' => '#0EA5E9',
        'operario' => '#10B981',
    ];
@endphp

<{{ $puedeVerDetalle ? 'a' : 'div' }}
    @if ($puedeVerDetalle) href="{{ \App\Filament\Resources\ProgramacionResource::getUrl('detalle', ['obra' => $obra->id, 'fecha' => $fecha]) }}" @endif
    style="display: flex; overflow: hidden; border-radius: 12px; border: 1px solid #e5e7eb; background: #ffffff; text-decoration: none; box-shadow: 0 1px 2px rgba(0,0,0,0.04);"
>
    <div style="width: 6px; flex-shrink: 0; background-color: {{ $color }};"></div>

    <div style="flex: 1; min-width: 0; padding: 14px 16px;">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;">
            <h4 style="margin: 0; font-size: 14.5px; font-weight: 700; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                {{ $obra?->nombre ?? 'Obra eliminada' }}
            </h4>

            <div style="flex-shrink: 0; display: flex; align-items: center; gap: 6px;">
                @if ($item->horaMin)
                    <span style="font-size: 12px; font-weight: 600; color: #374151;">
                        {{ $item->horaMin->format('H:i') }}
                    </span>
                @endif

                @if ($item->tieneViaje)
                    <span style="display: inline-block; border-radius: 999px; background: #EEF2FF; color: #4338CA; padding: 2px 9px; font-size: 10.5px; font-weight: 700;">
                        Viaje
                    </span>
                @endif
            </div>
        </div>

        <div style="margin-top: 10px; display: flex; align-items: center;">
            @foreach ($visibles as $registro)
                @php $rol = $registro->empleado?->rolPrincipal(); @endphp
                <div
                    title="{{ $registro->empleado?->nombre_completo }}"
                    style="width: 26px; height: 26px; flex-shrink: 0; overflow: hidden; border-radius: 999px; border: 2px solid {{ $rolColores[$rol] ?? '#ffffff' }}; margin-left: {{ $loop->first ? '0' : '-9px' }};"
                >
                    <img src="{{ $registro->empleado?->avatarUrl() }}" alt="{{ $registro->empleado?->nombre_completo }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
                </div>
            @endforeach

            @if ($restantes > 0)
                <div style="margin-left: -9px; display: flex; width: 26px; height: 26px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 999px; border: 2px solid #ffffff; background: #e5e7eb; font-size: 10px; font-weight: 700; color: #4b5563;">
                    +{{ $restantes }}
                </div>
            @endif
        </div>

        <div style="margin-top: 8px;">
            @if ($item->encargado)
                <p style="margin: 0; font-size: 12px; color: #6b7280;">
                    A cargo: <strong style="color: #374151;">{{ $item->encargado->nombre_completo }}</strong>
                </p>
            @else
                <p style="margin: 0; display: flex; align-items: center; gap: 4px; font-size: 12px; font-weight: 600; color: #92400E;">
                    <x-heroicon-o-exclamation-triangle style="width: 14px; height: 14px; flex-shrink: 0;" />
                    Sin encargado asignado
                </p>
            @endif
        </div>

        @if ($item->vehiculo)
            <div style="margin-top: 6px; display: flex; align-items: center; gap: 4px; font-size: 11.5px; color: #9ca3af;">
                <x-heroicon-o-truck style="width: 14px; height: 14px; flex-shrink: 0;" />
                <span>{{ collect([$item->vehiculo->placa, $item->vehiculo->modelo])->filter()->implode(' · ') }}</span>
            </div>
        @endif
    </div>
</{{ $puedeVerDetalle ? 'a' : 'div' }}>
