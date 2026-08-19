@php
    use App\Enums\EstadoChecklistItem;
    use Illuminate\Support\Facades\Storage;

    $itemSeleccionado = $this->getItemSeleccionado();
    $checklist = $this->getChecklist();
    $secciones = $this->getSeccionesAgrupadas();
    $totalGeneral = $secciones->sum('total');
    $completadosGeneral = $secciones->sum('completadosCount');
    $pendientesGeneral = $secciones->sum(fn ($seccion) => $seccion['pendientes']->count());
    $enRevisionGeneral = $secciones->sum(fn ($seccion) => $seccion['enRevision']->count());
    $fotoAmpliada = $this->getFotoAmpliada();
    $siguientePendiente = $this->getSiguientePendiente();
    $puedeAprobar = $this->puedeAprobar();
    $contadoresRepeticion = $this->contadoresRepeticion();
    /** @var \App\Models\Obra $obra */
    $obra = $this->record;

    $tabs = [
        'pendientes' => "Pendientes ({$pendientesGeneral})",
        'en_revision' => "En revisión ({$enRevisionGeneral})",
        'completados' => "Completados ({$completadosGeneral})",
        'todos' => 'Todos',
    ];
@endphp

<x-filament-panels::page>
    <style>
        .ejecutar-checklist-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 16px;
            align-items: start;
        }

        .ejecutar-checklist-desktop-panel {
            display: none;
        }

        @media (min-width: 1024px) {
            .ejecutar-checklist-grid {
                grid-template-columns: 1.7fr 1fr;
            }

            .ejecutar-checklist-mobile-resumen {
                display: none;
            }

            .ejecutar-checklist-desktop-panel {
                display: block;
            }
        }
    </style>

    @if (! $checklist)
        <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
            Esta obra todavía no tiene un checklist armado.
        </div>
    @elseif ($itemSeleccionado)
        {{-- Vista detalle --}}
        @php
            $categoriaItem = $itemSeleccionado->categoriaEfectiva();
            $puedeSubirFotos = $itemSeleccionado->estado !== EstadoChecklistItem::Completado;
        @endphp

        <button
            type="button"
            wire:click="volverALista"
            style="cursor: pointer; display: flex; align-items: center; gap: 6px; border: none; background: none; padding: 8px 0; margin-bottom: 12px; font-size: 14px; font-weight: 600; color: #6b7280;"
        >
            <x-heroicon-o-arrow-left style="width: 18px; height: 18px;" />
            Volver a la lista
        </button>

        <div class="ejecutar-checklist-grid">
            <div style="min-width: 0; display: flex; flex-direction: column; gap: 14px;">
                @if ($itemSeleccionado->requiere_foto)
                    @if ($itemSeleccionado->estado === EstadoChecklistItem::PendienteAprobacion)
                        <div style="border-radius: 10px; border: 1.5px solid #0EA5E9; background: #F0F9FF; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="font-size: 18px; line-height: 1;">🔎</span>
                                <span style="font-size: 13px; font-weight: 700; color: #0369A1;">Pendiente de aprobación</span>
                            </div>
                            <p style="margin: 0; font-size: 12.5px; color: #0369A1; line-height: 1.5;">
                                Ambas fotos fueron cargadas. Un jefe debe revisar y aprobar para dar por completado este trabajo.
                            </p>

                            @if ($puedeAprobar)
                                <div style="display: flex; gap: 8px; margin-top: 12px;">
                                    <button
                                        type="button"
                                        wire:click="aprobarItem"
                                        style="flex: 1; cursor: pointer; border: none; border-radius: 10px; background: #059669; color: #ffffff; padding: 10px; font-size: 12.5px; font-weight: 700;"
                                    >
                                        Aprobar y marcar completado
                                    </button>
                                    <button
                                        type="button"
                                        wire:click="rechazarItem"
                                        style="flex: 1; cursor: pointer; border: none; border-radius: 10px; background: #DC2626; color: #ffffff; padding: 10px; font-size: 12.5px; font-weight: 700;"
                                    >
                                        Rechazar, falta corregir
                                    </button>
                                </div>

                                <textarea
                                    wire:model="motivoRechazoTexto"
                                    rows="2"
                                    placeholder="Motivo del rechazo (obligatorio para rechazar)"
                                    style="width: 100%; box-sizing: border-box; margin-top: 8px; border-radius: 8px; border: 1px solid #BAE6FD; padding: 8px 10px; font-size: 12.5px; font-family: inherit; resize: vertical;"
                                ></textarea>
                            @endif
                        </div>
                    @elseif ($itemSeleccionado->estado === EstadoChecklistItem::Rechazado)
                        <div style="border-radius: 10px; border: 1.5px solid #DC2626; background: #FEF2F2; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                <span style="font-size: 18px; line-height: 1;">✖️</span>
                                <span style="font-size: 13px; font-weight: 700; color: #991B1B;">Rechazado</span>
                            </div>
                            <p style="margin: 0 0 4px; font-size: 12.5px; color: #991B1B; line-height: 1.5;">
                                {{ $itemSeleccionado->motivo_rechazo }}
                            </p>
                            <p style="margin: 0; font-size: 12px; font-weight: 600; color: #991B1B;">
                                Hace falta corregir y volver a subir la evidencia.
                            </p>
                        </div>
                    @elseif ($itemSeleccionado->estado === EstadoChecklistItem::Completado)
                        <div style="border-radius: 10px; border: 1.5px solid #10B981; background: #ECFDF5; padding: 14px;">
                            <div style="display: flex; align-items: center; gap: 8px;">
                                <x-heroicon-o-check-circle style="width: 22px; height: 22px; color: #059669; flex-shrink: 0;" />
                                <div>
                                    <p style="margin: 0; font-size: 13px; font-weight: 700; color: #065F46;">Completado</p>
                                    <p style="margin: 2px 0 0; font-size: 12px; color: #065F46;">
                                        @if ($itemSeleccionado->aprobadoPor)
                                            Aprobado por {{ $itemSeleccionado->aprobadoPor->getFilamentName() }}
                                            @if ($itemSeleccionado->fecha_aprobacion)
                                                el {{ $itemSeleccionado->fecha_aprobacion->format('d/m/Y H:i') }}
                                            @endif
                                        @else
                                            Evidencia aprobada.
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; border-radius: 10px; background: #FFFBEB;">
                            <span style="font-size: 20px; line-height: 1;">⭕</span>
                            <span style="font-size: 13px; font-weight: 600; color: #92400E;">
                                Falta foto de antes y/o después
                            </span>
                        </div>
                    @endif

                    @foreach (['antes' => 'Foto antes', 'despues' => 'Foto después'] as $momento => $etiqueta)
                        @php $fotosMomento = $itemSeleccionado->fotos->where('momento', $momento); @endphp
                        <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
                            <p style="margin: 0 0 12px; font-size: 12.5px; font-weight: 700; color: #374151;">{{ $etiqueta }}</p>

                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(84px, 1fr)); gap: 10px;">
                                @foreach ($fotosMomento as $foto)
                                    <button
                                        type="button"
                                        wire:click="ampliarFoto({{ $foto->id }})"
                                        style="cursor: pointer; padding: 0; border: 1px solid #e5e7eb; border-radius: 10px; overflow: hidden; aspect-ratio: 1; line-height: 0;"
                                    >
                                        <img
                                            src="{{ Storage::disk('public')->url($foto->url) }}"
                                            alt="{{ $etiqueta }}"
                                            style="width: 100%; height: 100%; object-fit: cover; display: block;"
                                        />
                                    </button>
                                @endforeach

                                @if ($puedeSubirFotos)
                                    <label style="cursor: pointer; aspect-ratio: 1; border-radius: 10px; border: 1.5px dashed #d1d5db; background: #F9FAFB; display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 4px;">
                                        <span style="position: relative; display: inline-flex;">
                                            <x-heroicon-o-camera style="width: 22px; height: 22px; color: #9CA3AF;" />
                                            <span style="position: absolute; bottom: -4px; right: -7px; width: 15px; height: 15px; border-radius: 999px; background: #F59E0B; color: #ffffff; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; line-height: 1;">+</span>
                                        </span>
                                        <span style="font-size: 11px; font-weight: 600; color: #6b7280;">Agregar</span>
                                        <input
                                            type="file"
                                            accept="image/*"
                                            capture="environment"
                                            multiple
                                            wire:model="{{ $momento === 'antes' ? 'fotoAntes' : 'fotoDespues' }}"
                                            style="display: none;"
                                        />
                                    </label>
                                @endif
                            </div>

                            @if ($fotosMomento->isEmpty() && ! $puedeSubirFotos)
                                <p style="margin: 8px 0 0; font-size: 12px; color: #9ca3af;">Sin fotos.</p>
                            @endif

                            <div wire:loading wire:target="{{ $momento === 'antes' ? 'fotoAntes' : 'fotoDespues' }}" style="margin-top: 8px; font-size: 11px; color: #9ca3af;">
                                Subiendo...
                            </div>
                        </div>
                    @endforeach
                @else
                    <button
                        type="button"
                        wire:click="alternarCompletado"
                        style="width: 100%; cursor: pointer; display: flex; align-items: center; justify-content: center; gap: 8px; padding: 16px; border-radius: 12px; border: none; font-size: 15px; font-weight: 700;
                            background: {{ $itemSeleccionado->estado === EstadoChecklistItem::Completado ? '#ECFDF5' : '#F59E0B' }};
                            color: {{ $itemSeleccionado->estado === EstadoChecklistItem::Completado ? '#065F46' : '#ffffff' }};"
                    >
                        <span style="font-size: 18px;">{{ $itemSeleccionado->estado === EstadoChecklistItem::Completado ? '✅' : '⭕' }}</span>
                        {{ $itemSeleccionado->estado === EstadoChecklistItem::Completado ? 'Completado (tap para deshacer)' : 'Marcar como completado' }}
                    </button>
                @endif

                <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
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

            <div style="display: flex; flex-direction: column; gap: 14px;">
                <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
                    <p style="margin: 0 0 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                        Trabajo
                    </p>
                    <h2 style="margin: 0 0 10px; font-size: 16px; font-weight: 700; color: #111827; line-height: 1.35;">
                        {{ $itemSeleccionado->descripcion }}
                    </h2>

                    @if ($categoriaItem)
                        <span style="display: inline-flex; align-items: center; gap: 6px; border-radius: 999px; background: #F3F4F6; padding: 4px 10px; font-size: 11.5px; font-weight: 600; color: #374151;">
                            <span style="width: 8px; height: 8px; border-radius: 999px; flex-shrink: 0; background: {{ $categoriaItem->color ?? '#9CA3AF' }};"></span>
                            {{ $categoriaItem->nombre }}
                        </span>
                    @endif
                </div>

                @if ($itemSeleccionado->requiere_foto)
                    <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 16px;">
                        <p style="margin: 0 0 10px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                            Para completar
                        </p>

                        <div style="display: flex; flex-direction: column; gap: 10px;">
                            @foreach ([['tiene' => $itemSeleccionado->tieneFotoAntes(), 'ok' => 'Foto antes cargada', 'falta' => 'Falta foto antes'], ['tiene' => $itemSeleccionado->tieneFotoDespues(), 'ok' => 'Foto después cargada', 'falta' => 'Falta foto después']] as $chequeo)
                                <div style="display: flex; align-items: center; gap: 8px;">
                                    @if ($chequeo['tiene'])
                                        <x-heroicon-o-check-circle style="width: 18px; height: 18px; flex-shrink: 0; color: #10B981;" />
                                        <span style="font-size: 12.5px; font-weight: 600; color: #374151;">{{ $chequeo['ok'] }}</span>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" style="width: 18px; height: 18px; flex-shrink: 0;">
                                            <circle cx="12" cy="12" r="9" stroke="#d1d5db" stroke-width="2" stroke-dasharray="3 3" />
                                        </svg>
                                        <span style="font-size: 12.5px; font-weight: 600; color: #9ca3af;">{{ $chequeo['falta'] }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @else
        {{-- Vista lista --}}
        <div class="ejecutar-checklist-grid">
            <div class="ejecutar-checklist-mobile-resumen" style="margin-bottom: 4px;">
                <details>
                    <summary style="cursor: pointer; list-style: none; display: flex; align-items: center; justify-content: center; gap: 6px; border-radius: 10px; border: 1px solid #e5e7eb; background: #ffffff; padding: 10px; font-size: 13px; font-weight: 700; color: #374151;">
                        <x-heroicon-o-chart-pie style="width: 16px; height: 16px;" />
                        Ver resumen
                    </summary>

                    <div style="margin-top: 12px;">
                        @include('filament.resources.obra-resource.pages.partials.checklist-resumen-panel', [
                            'obra' => $obra,
                            'secciones' => $secciones,
                            'totalGeneral' => $totalGeneral,
                            'completadosGeneral' => $completadosGeneral,
                            'siguientePendiente' => $siguientePendiente,
                            'contadoresRepeticion' => $contadoresRepeticion,
                        ])
                    </div>
                </details>
            </div>

            <div style="min-width: 0;">
                @if ($totalGeneral > 0)
                    <div style="display: flex; gap: 6px; margin-bottom: 16px; border-radius: 12px; background: #F3F4F6; padding: 4px; flex-wrap: wrap;">
                        @foreach ($tabs as $clave => $etiqueta)
                            <button
                                type="button"
                                wire:click="setFiltro('{{ $clave }}')"
                                style="flex: 1; min-width: 90px; cursor: pointer; border: none; border-radius: 9px; padding: 8px 6px; font-size: 12.5px; font-weight: 700;
                                    background: {{ $filtro === $clave ? '#ffffff' : 'transparent' }};
                                    color: {{ $filtro === $clave ? '#111827' : '#6b7280' }};
                                    box-shadow: {{ $filtro === $clave ? '0 1px 2px rgba(0,0,0,0.08)' : 'none' }};"
                            >
                                {{ $etiqueta }}
                            </button>
                        @endforeach
                    </div>
                @endif

                @if ($filtro === 'pendientes' && $pendientesGeneral === 0 && $totalGeneral > 0)
                    <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                        ✅ No quedan items pendientes.
                    </div>
                @elseif ($filtro === 'en_revision' && $enRevisionGeneral === 0)
                    <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                        No hay items esperando aprobación.
                    </div>
                @elseif ($filtro === 'completados' && $completadosGeneral === 0)
                    <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                        Todavía no hay items completados.
                    </div>
                @elseif ($totalGeneral === 0)
                    <div style="border-radius: 14px; border: 1px dashed #d1d5db; padding: 48px 24px; text-align: center; font-size: 13px; color: #6b7280;">
                        Este checklist todavía no tiene items.
                    </div>
                @else
                    @foreach ($secciones as $seccion)
                        @php
                            $filtrarBloque = fn ($bloque) => match ($filtro) {
                                'en_revision' => $bloque['enRevision'],
                                'completados' => $bloque['completados'],
                                'todos' => $bloque['pendientes']->concat($bloque['enRevision'])->concat($bloque['completados']),
                                default => $bloque['pendientes'],
                            };

                            $bloques = ($seccion['subgrupos'] ?? collect([$seccion]))
                                ->map(fn ($bloque) => [...$bloque, 'itemsMostrar' => $filtrarBloque($bloque)])
                                ->filter(fn ($bloque) => $bloque['itemsMostrar']->isNotEmpty())
                                ->values();
                        @endphp

                        @continue($bloques->isEmpty())

                        <div style="margin-bottom: 20px;">
                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px; padding-left: 2px;">
                                <span style="width: 10px; height: 10px; border-radius: 999px; flex-shrink: 0; background: {{ $seccion['color'] ?? '#9CA3AF' }};"></span>
                                <span style="font-size: 13.5px; font-weight: 700; color: #111827;">{{ $seccion['nombre'] }}</span>
                                <span style="font-size: 12px; color: #6b7280;">
                                    ({{ $seccion['completadosCount'] }} de {{ $seccion['total'] }} completados)
                                </span>
                            </div>

                            @foreach ($bloques as $bloque)
                                @if ($seccion['subgrupos'] && $bloque['nombre'])
                                    <div style="margin: 10px 0 6px 14px; font-size: 11.5px; font-weight: 700; color: #9ca3af; text-transform: uppercase; letter-spacing: 0.04em;">
                                        {{ $bloque['nombre'] }}
                                    </div>
                                @endif

                                <div style="display: flex; flex-direction: column; gap: 8px; {{ $seccion['subgrupos'] && $bloque['nombre'] ? 'padding-left: 14px;' : '' }}">
                                    @foreach ($bloque['itemsMostrar'] as $item)
                                        @include('filament.resources.obra-resource.pages.partials.checklist-item-row', ['item' => $item, 'color' => $seccion['color'] ?? '#9CA3AF', 'contadoresRepeticion' => $contadoresRepeticion])
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                @endif
            </div>

            <div class="ejecutar-checklist-desktop-panel">
                @include('filament.resources.obra-resource.pages.partials.checklist-resumen-panel', [
                    'obra' => $obra,
                    'secciones' => $secciones,
                    'totalGeneral' => $totalGeneral,
                    'completadosGeneral' => $completadosGeneral,
                    'siguientePendiente' => $siguientePendiente,
                    'contadoresRepeticion' => $contadoresRepeticion,
                ])
            </div>
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
