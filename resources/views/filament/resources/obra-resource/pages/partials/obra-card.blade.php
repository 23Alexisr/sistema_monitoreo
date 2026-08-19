@php
    $cliente = $obra->cliente;
    $color = $cliente?->colorMarca() ?? \App\Models\Cliente::COLOR_MARCA_DEFECTO;
    $colorOscuro = $cliente?->colorMarcaOscuro() ?? '#374151';
    $avance = $obra->avance_pct;
    $resumen = $obra->resumenChecklist();
    $personal = $obra->personalHoy();
    $visibles = $personal->take(4);
    $restantes = $personal->count() - $visibles->count();
    $puedeVerDetalle = \App\Filament\Resources\ObraResource::canEdit($obra);

    $estadoLabels = [
        'pendiente' => 'Pendiente',
        'en_progreso' => 'En ejecución',
        'finalizada' => 'Terminada',
        'cancelada' => 'Cancelada',
    ];

    $rolColores = [
        'supervisor' => '#F59E0B',
        'jefe_cuadrilla' => '#0EA5E9',
        'operario' => '#10B981',
    ];
    $rolLabels = [
        'supervisor' => 'Supervisor',
        'jefe_cuadrilla' => 'Jefe de cuadrilla',
        'operario' => 'Operario',
    ];

    $radio = 26;
    $circunferencia = 2 * M_PI * $radio;
    $offset = $circunferencia * (1 - min(100, max(0, $avance)) / 100);
@endphp

<div style="overflow: hidden; border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; box-shadow: 0 1px 2px rgba(0,0,0,0.05);">
    <div style="position: relative; padding: 18px 16px 22px; background-color: {{ $color }};">
        <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 8px;">
            <span style="display: inline-block; flex: 1 1 auto; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; border-radius: 999px; background: #ffffff; color: {{ $colorOscuro }}; padding: 4px 11px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em;">
                {{ $cliente?->nombre ?? 'Sin cliente' }}
            </span>
            <span style="flex-shrink: 0; border-radius: 999px; background: rgba(255,255,255,0.28); color: #ffffff; padding: 4px 11px; font-size: 11px; font-weight: 600;">
                {{ $estadoLabels[$obra->estado] ?? $obra->estado }}
            </span>
        </div>

        <h3 style="margin: 16px 0 0; font-size: 17px; font-weight: 700; line-height: 1.3; color: #ffffff; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
            {{ $obra->nombre }}
        </h3>

        @if ($obra->ubicacion)
            <div style="margin-top: 6px; display: flex; align-items: center; gap: 5px; font-size: 12.5px; color: rgba(255,255,255,0.85);">
                <x-heroicon-o-map-pin style="width: 14px; height: 14px; flex-shrink: 0;" />
                <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $obra->ubicacion }}</span>
            </div>
        @endif
    </div>

    <div style="display: flex; align-items: center; gap: 14px; padding: 16px;">
        <div style="position: relative; flex-shrink: 0; width: 64px; height: 64px;">
            <svg viewBox="0 0 64 64" width="64" height="64" style="transform: rotate(-90deg); transform-origin: 50% 50%;">
                <circle cx="32" cy="32" r="{{ $radio }}" fill="none" stroke="#f1f1f4" stroke-width="6" />
                <circle
                    cx="32" cy="32" r="{{ $radio }}" fill="none" stroke-width="6" stroke-linecap="round"
                    stroke="{{ $color }}"
                    style="stroke-dasharray: {{ $circunferencia }}; stroke-dashoffset: {{ $offset }}; transition: stroke-dashoffset .4s;"
                />
            </svg>
            <div style="position: absolute; inset: 0; display: flex; align-items: center; justify-content: center;">
                <span style="font-size: 13px; font-weight: 700; color: #111827;">{{ $avance }}%</span>
            </div>
        </div>

        <div style="min-width: 0; flex: 1;">
            <p style="margin: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-size: 11px; color: #9ca3af;">
                {{ $obra->ordenTrabajo?->numero_ot ?? $obra->codigo }}
            </p>

            <div style="margin-top: 8px; display: flex; align-items: center;">
                @forelse ($visibles as $empleado)
                    @php $rolEmpleado = $empleado->rolPrincipal(); @endphp
                    <div
                        title="{{ $empleado->nombre_completo }}{{ $rolEmpleado ? ' · '.$rolLabels[$rolEmpleado] : '' }}"
                        style="width: 26px; height: 26px; flex-shrink: 0; overflow: hidden; border-radius: 999px; border: 2px solid {{ $rolColores[$rolEmpleado] ?? '#ffffff' }}; margin-left: {{ $loop->first ? '0' : '-9px' }};"
                    >
                        <img src="{{ $empleado->avatarUrl() }}" alt="{{ $empleado->nombre_completo }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
                    </div>
                @empty
                    <span style="font-size: 11px; color: #9ca3af;">Sin personal hoy</span>
                @endforelse

                @if ($restantes > 0)
                    <div style="margin-left: -9px; display: flex; width: 26px; height: 26px; flex-shrink: 0; align-items: center; justify-content: center; border-radius: 999px; border: 2px solid #ffffff; background: #e5e7eb; font-size: 10px; font-weight: 700; color: #4b5563;">
                        +{{ $restantes }}
                    </div>
                @endif
            </div>
        </div>
    </div>

    <div style="display: flex; border-top: 1px solid #f0f0f0;">
        <div style="flex: 1; padding: 10px 8px; text-align: center; border-right: 1px solid #f0f0f0;">
            <p style="margin: 0; font-size: 15px; font-weight: 700; color: #059669;">{{ $resumen['listos'] }}</p>
            <p style="margin: 2px 0 0; font-size: 10.5px; color: #9ca3af;">listos</p>
        </div>
        <div style="flex: 1; padding: 10px 8px; text-align: center;">
            <p style="margin: 0; font-size: 15px; font-weight: 700; color: #6b7280;">{{ $resumen['pendientes'] }}</p>
            <p style="margin: 2px 0 0; font-size: 10.5px; color: #9ca3af;">pendientes</p>
        </div>
    </div>

    @if ($puedeVerDetalle)
        <a
            href="{{ \App\Filament\Resources\ObraResource::getUrl('checklist-ejecutar', ['record' => $obra]) }}"
            style="display: block; border-top: 1px solid #f0f0f0; padding: 10px 16px; text-align: center; font-size: 12.5px; font-weight: 600; color: #374151; text-decoration: none;"
        >
            Ver detalle
        </a>
    @endif
</div>
