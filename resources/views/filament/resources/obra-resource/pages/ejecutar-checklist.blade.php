@php
    use Illuminate\Support\Facades\Storage;

    $itemSeleccionado = $this->getItemSeleccionado();
    $checklist = $this->getChecklist();
    $secciones = $this->getSeccionesAgrupadas();
    $totalGeneral = $secciones->sum('total');
    $completadosGeneral = $secciones->sum('completadosCount');
    $fotoAmpliada = $this->getFotoAmpliada();
@endphp

<x-filament-panels::page>
    @if (! $checklist)
        <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
            Esta obra todavía no tiene un checklist armado.
        </div>
    @elseif ($itemSeleccionado)
        {{-- Vista detalle --}}
        <div style="max-width: 480px; margin: 0 auto;">
            <button
                type="button"
                wire:click="volverALista"
                style="cursor: pointer; display: flex; align-items: center; gap: 6px; border: none; background: none; padding: 8px 0; margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #6b7280;"
            >
                <x-heroicon-o-arrow-left style="width: 18px; height: 18px;" />
                Volver a la lista
            </button>

            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 18px;">
                <p style="margin: 0 0 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                    Trabajo
                </p>
                <h2 style="margin: 0 0 18px; font-size: 18px; font-weight: 700; color: #111827; line-height: 1.35;">
                    {{ $itemSeleccionado->descripcion }}
                </h2>

                @if ($itemSeleccionado->requiere_foto)
                    <div style="margin-bottom: 8px; display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; background: {{ $itemSeleccionado->completado ? '#ECFDF5' : '#FFFBEB' }};">
                        <span style="font-size: 20px; line-height: 1;">{{ $itemSeleccionado->completado ? '✅' : '⭕' }}</span>
                        <span style="font-size: 13px; font-weight: 600; color: {{ $itemSeleccionado->completado ? '#065F46' : '#92400E' }};">
                            {{ $itemSeleccionado->completado ? 'Completado por evidencia fotográfica' : 'Falta foto de antes y/o después' }}
                        </span>
                    </div>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 14px;">
                        @foreach (['antes' => 'Foto antes', 'despues' => 'Foto después'] as $momento => $etiqueta)
                            @php $fotosMomento = $itemSeleccionado->fotos->where('momento', $momento); @endphp
                            <div style="border: 1px dashed #d1d5db; border-radius: 12px; padding: 12px; text-align: center;">
                                <p style="margin: 0 0 8px; font-size: 12.5px; font-weight: 700; color: #374151;">{{ $etiqueta }}</p>

                                @if ($fotosMomento->isNotEmpty())
                                    <div style="display: flex; flex-wrap: wrap; gap: 6px; justify-content: center; margin-bottom: 10px;">
                                        @foreach ($fotosMomento as $foto)
                                            <button
                                                type="button"
                                                wire:click="ampliarFoto({{ $foto->id }})"
                                                style="cursor: pointer; padding: 0; border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden; line-height: 0;"
                                            >
                                                <img
                                                    src="{{ Storage::disk('public')->url($foto->url) }}"
                                                    alt="{{ $etiqueta }}"
                                                    style="width: 56px; height: 56px; object-fit: cover; display: block;"
                                                />
                                            </button>
                                        @endforeach
                                    </div>
                                @endif

                                <label style="display: flex; flex-direction: column; align-items: center; gap: 6px; cursor: pointer; padding: 10px 6px; border-radius: 10px; background: #F9FAFB;">
                                    <x-heroicon-o-camera style="width: 22px; height: 22px; color: #F59E0B;" />
                                    <span style="font-size: 11.5px; font-weight: 600; color: #6b7280;">
                                        {{ $fotosMomento->isNotEmpty() ? 'Agregar otra' : 'Tomar foto' }}
                                    </span>
                                    <input
                                        type="file"
                                        accept="image/*"
                                        capture="environment"
                                        wire:model="{{ $momento === 'antes' ? 'fotoAntes' : 'fotoDespues' }}"
                                        style="display: none;"
                                    />
                                </label>

                                <div wire:loading wire:target="{{ $momento === 'antes' ? 'fotoAntes' : 'fotoDespues' }}" style="margin-top: 6px; font-size: 11px; color: #9ca3af;">
                                    Subiendo...
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <button
                        type="button"
                        wire:click="alternarCompletado"
                        style="width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px; border-radius: 12px; border: none; font-size: 15px; font-weight: 700; margin-top: 4px;
                            background: {{ $itemSeleccionado->completado ? '#ECFDF5' : '#F59E0B' }};
                            color: {{ $itemSeleccionado->completado ? '#065F46' : '#ffffff' }};"
                    >
                        <span style="font-size: 18px;">{{ $itemSeleccionado->completado ? '✅' : '⭕' }}</span>
                        {{ $itemSeleccionado->completado ? 'Completado (tap para deshacer)' : 'Marcar como completado' }}
                    </button>
                @endif

                <div style="margin-top: 20px;">
                    <label style="display: block; margin-bottom: 6px; font-size: 12.5px; font-weight: 700; color: #374151;">Observaciones</label>
                    <textarea
                        wire:model="observacionesTexto"
                        rows="3"
                        placeholder="Opcional"
                        style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 13.5px; font-family: inherit; resize: vertical;"
                    ></textarea>
                    <button
                        type="button"
                        wire:click="guardarObservaciones"
                        style="cursor: pointer; margin-top: 8px; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 8px 16px; font-size: 12.5px; font-weight: 600; color: #374151;"
                    >
                        Guardar observación
                    </button>
                </div>
            </div>
        </div>
    @else
        {{-- Vista lista --}}
        <div style="max-width: 480px; margin: 0 auto;">
            @if ($totalGeneral > 0)
                <div style="display: flex; align-items: center; justify-content: space-between; margin-bottom: 16px; padding: 12px 14px; border-radius: 12px; background: #F9FAFB; border: 1px solid #e5e7eb;">
                    <span style="font-size: 13px; font-weight: 700; color: #374151;">Avance del checklist</span>
                    <span style="font-size: 13px; font-weight: 700; color: {{ $completadosGeneral === $totalGeneral ? '#059669' : '#374151' }};">
                        {{ $completadosGeneral }} de {{ $totalGeneral }} completados
                    </span>
                </div>
            @endif

            @forelse ($secciones as $seccion)
                <div style="margin-bottom: 20px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding-left: 2px;">
                        <span style="width: 10px; height: 10px; border-radius: 999px; flex-shrink: 0; background: {{ $seccion['color'] ?? '#9CA3AF' }};"></span>
                        <span style="font-size: 13.5px; font-weight: 700; color: #111827;">{{ $seccion['nombre'] }}</span>
                        <span style="font-size: 12px; color: #6b7280;">
                            ({{ $seccion['completadosCount'] }} de {{ $seccion['total'] }} completados)
                        </span>
                    </div>

                    <div style="display: flex; flex-direction: column; gap: 8px;">
                        @foreach ($seccion['pendientes'] as $item)
                            @include('filament.resources.obra-resource.pages.partials.checklist-item-row', ['item' => $item])
                        @endforeach

                        @if ($seccion['completados']->isNotEmpty())
                            <div style="display: flex; align-items: center; gap: 8px; margin: {{ $seccion['pendientes']->isNotEmpty() ? '8px' : '0' }} 0 2px; opacity: 0.6;">
                                <span style="font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">Completados</span>
                            </div>

                            @foreach ($seccion['completados'] as $item)
                                @include('filament.resources.obra-resource.pages.partials.checklist-item-row', ['item' => $item])
                            @endforeach
                        @endif
                    </div>
                </div>
            @empty
                <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                    Este checklist todavía no tiene items.
                </div>
            @endforelse
        </div>
    @endif

    @if ($fotoAmpliada)
        <div
            wire:click="cerrarFotoAmpliada"
            style="position: fixed; inset: 0; z-index: 1000; background: rgba(17,24,39,0.85); display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 16px; padding: 24px;"
        >
            <img
                src="{{ Storage::disk('public')->url($fotoAmpliada->url) }}"
                alt="Foto ampliada"
                style="max-width: 100%; max-height: 75vh; border-radius: 10px; object-fit: contain;"
            />

            <div style="display: flex; align-items: center; gap: 10px;" wire:click.stop>
                <a
                    href="{{ route('fotos.descargar', $fotoAmpliada) }}"
                    style="display: flex; align-items: center; gap: 6px; text-decoration: none; background: #F59E0B; color: #ffffff; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700;"
                >
                    <x-heroicon-o-arrow-down-tray style="width: 16px; height: 16px;" />
                    Descargar
                </a>

                <button
                    type="button"
                    wire:click="eliminarFotoAmpliada"
                    wire:confirm="¿Eliminar esta foto? No se puede deshacer."
                    style="cursor: pointer; display: flex; align-items: center; gap: 6px; background: #DC2626; color: #ffffff; border: none; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700;"
                >
                    <x-heroicon-o-trash style="width: 16px; height: 16px;" />
                    Eliminar
                </button>

                <button
                    type="button"
                    wire:click="cerrarFotoAmpliada"
                    style="cursor: pointer; display: flex; align-items: center; gap: 6px; background: rgba(255,255,255,0.15); color: #ffffff; border: none; padding: 10px 18px; border-radius: 10px; font-size: 13px; font-weight: 700;"
                >
                    Cerrar
                </button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
