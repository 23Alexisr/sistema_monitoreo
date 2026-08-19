<x-filament-panels::page>
    <style>
        :root {
            --text-secondary: #6b7280;
            --border-strong: #d1d5db;
        }

        .dark {
            --text-secondary: #9ca3af;
            --border-strong: rgba(255, 255, 255, 0.24);
        }
    </style>

    @if ($this->checklist)
        @php
            $avance = $this->record->avance_pct;
            $avanceAcotado = min(100, max(0, $avance));
            $colorAvance = $avance >= 100 ? '#10B981' : '#F59E0B';
        @endphp

        <div style="margin-bottom: 24px;">
            <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px;">
                <span style="font-size: 13px; font-weight: 700; color: #374151;">Avance de la obra</span>
                <span style="font-size: 13px; font-weight: 700; color: {{ $avance >= 100 ? '#059669' : '#374151' }};">{{ $avance }}%</span>
            </div>
            <div style="width: 100%; height: 10px; border-radius: 999px; background: #F3F4F6; overflow: hidden;">
                <div style="height: 100%; border-radius: 999px; width: {{ $avanceAcotado }}%; background: {{ $colorAvance }}; transition: width .4s;"></div>
            </div>
        </div>

        <x-filament-panels::form wire:submit="save">
            {{ $this->form }}

            <x-filament::button type="submit">
                Guardar checklist
            </x-filament::button>
        </x-filament-panels::form>
    @else
        <p class="text-sm text-gray-500">
            Esta obra todavía no tiene una Orden de Trabajo registrada. Se debe crear la OT primero desde la pestaña "Editar".
        </p>
    @endif

    @if ($panelBusquedaAbierto)
        <div
            wire:click="cerrarPanelBusqueda"
            style="position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,0.5); display: flex; align-items: flex-start; justify-content: center; padding: 48px 16px;"
        >
            <div wire:click.stop style="width: 100%; max-width: 560px; background: #ffffff; border-radius: 16px; box-shadow: 0 20px 40px rgba(0,0,0,0.25); max-height: 82vh; display: flex; flex-direction: column; overflow: hidden;">
                <div style="padding: 14px 18px; border-bottom: 1px solid #e5e7eb; display: flex; align-items: center; gap: 10px; flex-shrink: 0;">
                    <x-heroicon-o-magnifying-glass style="width: 19px; height: 19px; color: #9ca3af; flex-shrink: 0;" />
                    <input
                        type="text"
                        wire:model.live.debounce.350ms="busquedaCatalogo"
                        placeholder="Buscar trabajo del catálogo..."
                        autofocus
                        style="flex: 1; min-width: 0; border: none; outline: none; font-size: 14px; background: transparent;"
                    />
                    <button
                        type="button"
                        wire:click="cerrarPanelBusqueda"
                        style="cursor: pointer; border: none; background: none; color: #9ca3af; flex-shrink: 0; display: flex; padding: 2px;"
                    >
                        <x-heroicon-o-x-mark style="width: 20px; height: 20px;" />
                    </button>
                </div>

                <div style="overflow-y: auto; flex: 1; padding: 8px;">
                    @php $resultados = $this->resultadosBusqueda(); @endphp

                    @if (trim($busquedaCatalogo) === '')
                        <p style="margin: 8px 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                            Usados recientemente
                        </p>
                    @endif

                    @forelse ($resultados['items'] as $trabajo)
                        <div style="display: flex; align-items: center; gap: 10px; padding: 9px 10px; border-radius: 10px;">
                            <span style="width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0; background: {{ $trabajo->categoriaEfectiva()?->color ?? '#9CA3AF' }};"></span>

                            <div style="flex: 1; min-width: 0;">
                                <p style="margin: 0; font-size: 13.5px; font-weight: 600; color: #111827; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {!! $this->resaltarCoincidencia($trabajo->descripcion, $busquedaCatalogo) !!}
                                </p>
                                <p style="margin: 2px 0 0; font-size: 11.5px; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $trabajo->categoriaEfectiva()?->nombre ?? 'Sin categoría' }} · {{ $trabajo->dias_estimados }} días
                                </p>
                            </div>

                            <button
                                type="button"
                                wire:click="agregarDesdeBusqueda({{ $trabajo->id }})"
                                title="Agregar al checklist"
                                style="cursor: pointer; flex-shrink: 0; width: 28px; height: 28px; border-radius: 999px; border: none; background: #F59E0B; color: #ffffff; font-size: 16px; font-weight: 700; line-height: 1; display: flex; align-items: center; justify-content: center;"
                            >
                                +
                            </button>
                        </div>
                    @empty
                        <p style="margin: 28px 12px; text-align: center; font-size: 13px; color: #9ca3af;">
                            {{ trim($busquedaCatalogo) !== '' ? 'Sin resultados para "'.$busquedaCatalogo.'".' : 'Escribe para buscar en el catálogo.' }}
                        </p>
                    @endforelse

                    @if ($resultados['total'] !== null && $resultados['total'] > $resultados['items']->count())
                        <p style="margin: 8px 10px 4px; font-size: 11.5px; color: #9ca3af;">
                            Mostrando {{ $resultados['items']->count() }} de {{ $resultados['total'] }} resultados.
                        </p>
                    @endif
                </div>

                <div style="border-top: 1px solid #e5e7eb; padding: 12px 18px; flex-shrink: 0;">
                    @if (! $formularioManualAbierto)
                        <button
                            type="button"
                            wire:click="$set('formularioManualAbierto', true)"
                            style="cursor: pointer; border: none; background: none; color: #374151; font-size: 13px; font-weight: 600; padding: 0;"
                        >
                            + Agregar item manual (sin catálogo)
                        </button>
                    @else
                        <div style="display: flex; flex-direction: column; gap: 8px;">
                            <div>
                                <input
                                    type="text"
                                    wire:model="manualDescripcion"
                                    placeholder="Descripción"
                                    style="width: 100%; box-sizing: border-box; border-radius: 8px; border: 1px solid #e5e7eb; padding: 8px 10px; font-size: 13px;"
                                />
                                @error('manualDescripcion')
                                    <span style="display: block; margin-top: 4px; font-size: 11px; color: #DC2626;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <input
                                    type="number"
                                    step="0.01"
                                    wire:model="manualDiasEstimados"
                                    placeholder="Días estimados"
                                    style="width: 100%; box-sizing: border-box; border-radius: 8px; border: 1px solid #e5e7eb; padding: 8px 10px; font-size: 13px;"
                                />
                                @error('manualDiasEstimados')
                                    <span style="display: block; margin-top: 4px; font-size: 11px; color: #DC2626;">{{ $message }}</span>
                                @enderror
                            </div>

                            <div style="display: flex; gap: 8px;">
                                <button
                                    type="button"
                                    wire:click="agregarManual"
                                    style="cursor: pointer; flex: 1; border: none; border-radius: 8px; background: #F59E0B; color: #ffffff; padding: 8px; font-size: 12.5px; font-weight: 700;"
                                >
                                    Agregar
                                </button>
                                <button
                                    type="button"
                                    wire:click="$set('formularioManualAbierto', false)"
                                    style="cursor: pointer; border: 1px solid #e5e7eb; border-radius: 8px; background: #ffffff; color: #374151; padding: 8px 14px; font-size: 12.5px; font-weight: 600;"
                                >
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
