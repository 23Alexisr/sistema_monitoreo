@php
    $registros = $this->getRegistros();
    $vehiculo = $this->getVehiculo();
    $horaMin = $this->getHoraMin();
    $fechaCarbon = \Illuminate\Support\Carbon::parse($fecha);
    $avance = $obraRecord->avance_pct;
    $pendientes = $obraRecord->checklistPendientes();
    $totalPendientes = $pendientes->count();
    $pendientesVisibles = $pendientes->take(3);
    $pendientesRestantes = $totalPendientes - $pendientesVisibles->count();

    $especialidadLabels = [
        'electricista' => 'Electricista',
        'conductor' => 'Conductor',
        'instalador' => 'Instalador',
        'pintor' => 'Pintor',
        'auxiliar' => 'Auxiliar',
    ];
@endphp

<style>
    .detalle-obra-dia-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 16px;
        align-items: start;
    }

    @media (min-width: 1024px) {
        .detalle-obra-dia-grid {
            grid-template-columns: 1.7fr 1fr;
        }
    }
</style>

<x-filament-panels::page>
    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 4px;">
            <a
                href="{{ \App\Filament\Resources\ProgramacionResource::getUrl('index', ['fecha' => $fecha]) }}"
                style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; padding: 8px 0; font-size: 14px; font-weight: 600; color: #6b7280;"
            >
                <x-heroicon-o-arrow-left style="width: 18px; height: 18px;" />
                Volver a programación
            </a>

            @if ($registros->isNotEmpty())
                <a
                    href="{{ \App\Filament\Resources\ProgramacionResource::getUrl('editar-grupo', ['obra' => $obraRecord->id, 'fecha' => $fecha]) }}"
                    style="cursor: pointer; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; border-radius: 8px; border: 1px solid #e5e7eb; padding: 7px 12px; font-size: 13px; font-weight: 600; color: #374151;"
                >
                    <x-heroicon-o-pencil-square style="width: 16px; height: 16px;" />
                    Editar personal del día
                </a>
            @endif
        </div>

        <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 20px; margin-bottom: 16px; display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; flex-wrap: wrap;">
            <div style="min-width: 0;">
                <h2 style="margin: 0; font-size: 19px; font-weight: 700; color: #111827;">
                    {{ $obraRecord->nombre }}
                </h2>

                @if ($obraRecord->ubicacion)
                    <div style="margin-top: 6px; display: flex; align-items: center; gap: 5px; font-size: 13px; color: #6b7280;">
                        <x-heroicon-o-map-pin style="width: 15px; height: 15px; flex-shrink: 0;" />
                        <span>{{ $obraRecord->ubicacion }}</span>
                    </div>
                @endif
            </div>

            <div style="text-align: right; flex-shrink: 0;">
                <div style="display: flex; align-items: center; justify-content: flex-end; gap: 5px; font-size: 13px; color: #6b7280;">
                    <x-heroicon-o-calendar-days style="width: 15px; height: 15px; flex-shrink: 0;" />
                    <span>{{ \Illuminate\Support\Str::ucfirst($fechaCarbon->locale('es')->translatedFormat('l d \d\e F, Y')) }}</span>
                </div>
                @if ($horaMin)
                    <div style="margin-top: 6px; display: flex; align-items: center; justify-content: flex-end; gap: 5px; font-size: 13px; color: #6b7280;">
                        <x-heroicon-o-clock style="width: 15px; height: 15px; flex-shrink: 0;" />
                        <span>{{ $horaMin->format('h:i A') }}</span>
                    </div>
                @endif
            </div>
        </div>

        <div class="detalle-obra-dia-grid">
            <div>
                <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 20px;">
                    <p style="margin: 0 0 14px; font-size: 13px; font-weight: 700; color: #111827;">
                        Personal asignado ({{ $registros->count() }})
                    </p>

                    @forelse ($registros as $registro)
                        @php $empleado = $registro->empleado; @endphp
                        <div style="display: flex; align-items: center; gap: 12px; padding: 10px 0; {{ ! $loop->last ? 'border-bottom: 1px solid #f3f4f6;' : '' }}">
                            <div style="width: 40px; height: 40px; flex-shrink: 0; overflow: hidden; border-radius: 999px;">
                                <img src="{{ $empleado?->avatarUrl() }}" alt="{{ $empleado?->nombre_completo }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
                            </div>

                            <div style="min-width: 0; flex: 1;">
                                <p style="margin: 0; font-size: 14px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $empleado?->nombre_completo ?? 'Empleado eliminado' }}
                                </p>
                                <div style="margin-top: 3px; display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                                    @if ($empleado?->especialidad)
                                        <span style="font-size: 12px; color: #6b7280;">{{ $especialidadLabels[$empleado->especialidad] ?? $empleado->especialidad }}</span>
                                    @endif

                                    @if ($registro->es_encargado)
                                        <span style="display: inline-block; border-radius: 999px; background: #FEF3C7; color: #92400E; padding: 2px 8px; font-size: 10.5px; font-weight: 700;">
                                            A cargo
                                        </span>
                                    @endif

                                    @if ($registro->es_conductor)
                                        <span style="display: inline-block; border-radius: 999px; background: #DBEAFE; color: #1E40AF; padding: 2px 8px; font-size: 10.5px; font-weight: 700;">
                                            Conductor
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <div style="flex-shrink: 0; display: flex; align-items: center; gap: 6px;">
                                @if ($empleado?->telefono)
                                    <a
                                        href="tel:{{ $empleado->telefono }}"
                                        style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 999px; background: #F3F4F6; color: #374151; text-decoration: none;"
                                    >
                                        <x-heroicon-o-phone style="width: 16px; height: 16px;" />
                                    </a>
                                @endif

                                <a
                                    href="{{ \App\Filament\Resources\ProgramacionResource::getUrl('edit', ['record' => $registro->id]) }}"
                                    title="Editar esta asignación"
                                    style="display: flex; align-items: center; justify-content: center; width: 34px; height: 34px; border-radius: 999px; background: #F3F4F6; color: #374151; text-decoration: none;"
                                >
                                    <x-heroicon-o-pencil-square style="width: 16px; height: 16px;" />
                                </a>
                            </div>
                        </div>
                    @empty
                        <p style="margin: 0; font-size: 13px; color: #9ca3af;">Sin personal asignado.</p>
                    @endforelse
                </div>
            </div>

            <div style="display: flex; flex-direction: column; gap: 16px;">
                @if ($vehiculo)
                    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 20px;">
                        <p style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #111827;">
                            Vehículo asignado
                        </p>
                        <div style="display: flex; align-items: center; gap: 8px; font-size: 13.5px; color: #374151;">
                            <x-heroicon-o-truck style="width: 18px; height: 18px; flex-shrink: 0; color: #6b7280;" />
                            <span>{{ collect([$vehiculo->placa, $vehiculo->modelo])->filter()->implode(' · ') }}</span>
                        </div>
                    </div>
                @endif

                <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 20px;">
                    <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 8px;">
                        <p style="margin: 0; font-size: 13px; font-weight: 700; color: #111827;">
                            Avance de la obra
                        </p>
                        <span style="font-size: 13px; font-weight: 700; color: #111827;">{{ $avance }}%</span>
                    </div>

                    <div style="height: 8px; border-radius: 999px; background: #f1f1f4; overflow: hidden;">
                        <div style="height: 100%; width: {{ min(100, max(0, $avance)) }}%; background: #F59E0B; border-radius: 999px;"></div>
                    </div>

                    <div style="margin-top: 14px;">
                        @if ($totalPendientes === 0)
                            <div style="display: flex; align-items: center; gap: 6px; font-size: 12.5px; font-weight: 600; color: #059669;">
                                <x-heroicon-o-check-circle style="width: 16px; height: 16px; flex-shrink: 0;" />
                                Checklist completo
                            </div>
                        @else
                            <p style="margin: 0 0 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                                Pendientes
                            </p>
                            <ul style="margin: 0; padding: 0; list-style: none; display: flex; flex-direction: column; gap: 6px;">
                                @foreach ($pendientesVisibles as $item)
                                    <li style="display: flex; align-items: flex-start; gap: 6px; font-size: 12.5px; color: #374151;">
                                        <span style="width: 5px; height: 5px; margin-top: 6px; border-radius: 999px; background: #d1d5db; flex-shrink: 0;"></span>
                                        <span style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical;">{{ $item->descripcion }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            @if ($pendientesRestantes > 0)
                                <p style="margin: 6px 0 0; font-size: 11.5px; font-weight: 600; color: #9ca3af;">
                                    +{{ $pendientesRestantes }} más
                                </p>
                            @endif
                        @endif
                    </div>

                    <a
                        href="{{ \App\Filament\Resources\ObraResource::getUrl('checklist-ejecutar', ['record' => $obraRecord]) }}"
                        style="margin-top: 16px; display: block; text-align: center; border-radius: 10px; background: #111827; color: #ffffff; text-decoration: none; padding: 10px; font-size: 13.5px; font-weight: 600;"
                    >
                        Ver checklist completo
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
