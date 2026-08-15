@php
    use Illuminate\Support\Facades\Storage;

    $itemSeleccionado = $this->getItemSeleccionado();
    $checklist = $this->getChecklist();
    $items = $this->getItems();
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
                                            <img
                                                src="{{ Storage::disk('public')->url($foto->url) }}"
                                                alt="{{ $etiqueta }}"
                                                style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;"
                                            />
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
        <div style="max-width: 480px; margin: 0 auto; display: flex; flex-direction: column; gap: 8px;">
            @forelse ($items as $item)
                @include('filament.resources.obra-resource.pages.partials.checklist-item-row', ['item' => $item, 'nivel' => 0])
            @empty
                <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                    Este checklist todavía no tiene items.
                </div>
            @endforelse
        </div>
    @endif
</x-filament-panels::page>
