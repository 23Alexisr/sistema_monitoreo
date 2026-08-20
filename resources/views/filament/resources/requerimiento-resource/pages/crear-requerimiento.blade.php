@php
    $obra = $this->getObra();
@endphp

<x-filament-panels::page>
    @if (! $obra)
        @php $candidatas = $this->obrasCandidatas(); @endphp

        <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 20px;">
            <p style="margin: 0 0 12px; font-size: 13.5px; font-weight: 700; color: #374151;">¿Para qué obra es el pedido?</p>

            @forelse ($candidatas as $candidata)
                <button
                    type="button"
                    wire:click="seleccionarObra({{ $candidata->id }})"
                    style="width: 100%; cursor: pointer; text-align: left; display: block; border: 1px solid #e5e7eb; border-radius: 10px; padding: 12px 14px; margin-bottom: 8px; background: #ffffff; font-size: 13.5px; font-weight: 600; color: #111827;"
                >
                    {{ $candidata->nombre }}
                </button>
            @empty
                <p style="margin: 0; font-size: 13px; color: #9ca3af;">No tienes obras asignadas hoy para pedir materiales.</p>
            @endforelse
        </div>
    @else
        @php
            $carrito = $this->carritoDecorado();
            $resultados = $this->modoFlujo === 'material' ? $this->resultadosBusqueda() : null;
            $sugerencias = $this->modoFlujo === 'material' ? $this->sugerenciasPendientes() : collect();
        @endphp

        <div style="display: flex; flex-direction: column; gap: 14px;">
            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <p style="margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">Obra</p>
                <p style="margin: 4px 0 0; font-size: 15px; font-weight: 700; color: #111827;">{{ $obra->nombre }}</p>
            </div>

            {{-- Trabajo relacionado (opcional) — del catálogo general, no del checklist de esta obra --}}
            @if ($this->modoFlujo === 'material')
                @php $trabajoSeleccionado = $this->trabajoMaestroId ? \App\Models\TrabajoMaestro::find($this->trabajoMaestroId) : null; @endphp
                <details @if ($trabajoSeleccionado) open @endif style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                    <summary style="cursor: pointer; font-size: 13px; font-weight: 700; color: #374151;">
                        Trabajo relacionado (opcional)
                    </summary>

                    <div style="margin-top: 12px;">
                        @if ($trabajoSeleccionado)
                            <div style="display: flex; align-items: center; gap: 8px; border: 1px solid #F59E0B; background: #FFFBEB; border-radius: 10px; padding: 9px 10px;">
                                <span style="flex: 1; min-width: 0; font-size: 13px; font-weight: 600; color: #92400E; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                    {{ $trabajoSeleccionado->descripcion }}
                                </span>
                                <button
                                    type="button"
                                    wire:click="seleccionarTrabajoMaestro(null)"
                                    style="cursor: pointer; border: none; background: transparent; color: #92400E; font-size: 11.5px; font-weight: 700;"
                                >
                                    Quitar
                                </button>
                            </div>
                        @else
                            <input
                                type="text"
                                wire:model.live.debounce.300ms="busquedaTrabajo"
                                placeholder="Buscar trabajo del catálogo..."
                                style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13px; margin-bottom: 8px;"
                            />

                            @php $resultadosTrabajo = $this->resultadosTrabajoMaestro(); @endphp

                            @if ($resultadosTrabajo['total'] !== null)
                                <p style="margin: 0 0 6px; font-size: 11px; color: #9ca3af;">
                                    {{ $resultadosTrabajo['total'] }} resultado(s){{ $resultadosTrabajo['total'] > $resultadosTrabajo['items']->count() ? ', mostrando los primeros '.$resultadosTrabajo['items']->count() : '' }}
                                </p>
                            @endif

                            <div style="max-height: 220px; overflow-y: auto; display: flex; flex-direction: column; gap: 6px;">
                                @forelse ($resultadosTrabajo['items'] as $trabajo)
                                    <button
                                        type="button"
                                        wire:click="seleccionarTrabajoMaestro({{ $trabajo->id }})"
                                        style="cursor: pointer; text-align: left; border: 1px solid #e5e7eb; border-radius: 10px; padding: 8px 10px; background: #ffffff;"
                                    >
                                        <span style="display: block; font-size: 12.5px; font-weight: 600; color: #111827;">{{ $trabajo->descripcion }}</span>
                                        <span style="display: block; margin-top: 2px; font-size: 10.5px; color: #9ca3af;">{{ $trabajo->categoriaEfectiva()?->nombre }}</span>
                                    </button>
                                @empty
                                    <p style="margin: 0; font-size: 12.5px; color: #9ca3af;">Sin resultados.</p>
                                @endforelse
                            </div>
                        @endif

                        @if ($sugerencias->isNotEmpty())
                            <div style="margin-top: 12px; display: flex; flex-direction: column; gap: 8px;">
                                <p style="margin: 0; font-size: 11.5px; font-weight: 700; color: #9ca3af;">Sugerencias para este trabajo</p>

                                @foreach ($sugerencias as $sugerencia)
                                    <div style="display: flex; align-items: center; gap: 8px; border: 1px dashed #d1d5db; border-radius: 10px; padding: 8px 10px;">
                                        <span style="flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $sugerencia->material->nombre }}
                                        </span>
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            wire:model="cantidadesSugeridas.{{ $sugerencia->material_id }}"
                                            placeholder="{{ $sugerencia->cantidad_sugerida }}"
                                            style="width: 64px; border-radius: 8px; border: 1px solid #e5e7eb; padding: 6px 8px; font-size: 12.5px;"
                                        />
                                        <span style="font-size: 11px; color: #9ca3af;">{{ $sugerencia->material->unidad_medida }}</span>
                                        <button
                                            type="button"
                                            wire:click="agregarSugerido({{ $sugerencia->material_id }})"
                                            style="cursor: pointer; border: none; border-radius: 8px; background: #F59E0B; color: #ffffff; padding: 6px 10px; font-size: 11.5px; font-weight: 700;"
                                        >
                                            Agregar
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </details>
            @endif

            {{-- Adicional de un pedido anterior (opcional) --}}
            <details style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <summary style="cursor: pointer; font-size: 13px; font-weight: 700; color: #374151;">
                    ¿Es un adicional de un pedido anterior? (opcional)
                </summary>

                <div style="margin-top: 12px;">
                    <select
                        wire:change="basarEnAnterior($event.target.value)"
                        style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13px;"
                    >
                        <option value="">No, es un pedido nuevo</option>
                        @foreach ($this->requerimientosAnterioresDeObra() as $anterior)
                            <option value="{{ $anterior->id }}" @selected($this->requerimientoOriginalId === $anterior->id)>
                                #{{ $anterior->id }} · {{ $anterior->tipo->label() }} · {{ $anterior->fecha_solicitud->format('d/m/Y') }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </details>

            {{-- Catálogo --}}
            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <p style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #374151;">
                    Catálogo de {{ $this->modoFlujo === 'señaletica' ? 'señalética' : 'materiales' }}
                </p>

                <input
                    type="text"
                    wire:model.live.debounce.300ms="busquedaCatalogo"
                    placeholder="Buscar por nombre o código..."
                    style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 13.5px; margin-bottom: 12px;"
                />

                @if ($this->modoFlujo === 'material')
                    @if ($resultados['total'] !== null)
                        <p style="margin: 0 0 8px; font-size: 11.5px; color: #9ca3af;">
                            {{ $resultados['total'] }} resultado(s){{ $resultados['total'] > $resultados['items']->count() ? ', mostrando los primeros '.$resultados['items']->count() : '' }}
                        </p>
                    @endif

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                        @forelse ($resultados['items'] as $material)
                            @include('filament.resources.requerimiento-resource.pages.partials.tile-material', ['material' => $material])
                        @empty
                            <p style="grid-column: 1 / -1; margin: 0; font-size: 12.5px; color: #9ca3af;">Sin materiales para tu especialidad, o sin resultados.</p>
                        @endforelse

                        @include('filament.resources.requerimiento-resource.pages.partials.tile-no-catalogado')
                    </div>
                @else
                    @forelse ($this->catalogoSenaleticaAgrupado() as $nombreSubcategoria => $materialesGrupo)
                        <div style="margin-bottom: 14px;">
                            <p style="margin: 0 0 8px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                                {{ $nombreSubcategoria }}
                            </p>
                            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                                @foreach ($materialesGrupo as $material)
                                    @include('filament.resources.requerimiento-resource.pages.partials.tile-material', ['material' => $material])
                                @endforeach
                            </div>
                        </div>
                    @empty
                        <p style="margin: 0 0 12px; font-size: 12.5px; color: #9ca3af;">Sin señalética en el catálogo, o sin resultados.</p>
                    @endforelse

                    <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 10px;">
                        @include('filament.resources.requerimiento-resource.pages.partials.tile-no-catalogado')
                    </div>
                @endif
            </div>

            {{-- Modal simple de cantidad --}}
            @if ($this->materialParaCantidadId)
                @php $materialElegido = \App\Models\Material::find($this->materialParaCantidadId); @endphp
                <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: flex-end; justify-content: center; z-index: 50;" wire:click.self="cancelarCantidad">
                    <div style="width: 100%; max-width: 420px; background: #ffffff; border-radius: 16px 16px 0 0; padding: 20px;">
                        <p style="margin: 0 0 4px; font-size: 13px; font-weight: 700; color: #374151;">{{ $materialElegido?->nombre }}</p>
                        <p style="margin: 0 0 12px; font-size: 11.5px; color: #9ca3af;">¿Cuánto necesitas? ({{ $materialElegido?->unidad_medida }})</p>

                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            wire:model="cantidadTexto"
                            autofocus
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 15px; margin-bottom: 12px;"
                        />

                        <div style="display: flex; gap: 8px;">
                            <button type="button" wire:click="cancelarCantidad" style="flex: 1; cursor: pointer; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700; color: #374151;">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmarCantidad" style="flex: 1; cursor: pointer; border: none; background: #F59E0B; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700;">
                                Agregar al pedido
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Modal de item no catalogado --}}
            @if ($this->modalManualAbierto)
                @php $tiposManual = $this->tiposManualDisponibles(); @endphp
                <div style="position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex; align-items: flex-end; justify-content: center; z-index: 50;" wire:click.self="cancelarManual">
                    <div style="width: 100%; max-width: 420px; max-height: 88vh; overflow-y: auto; background: #ffffff; border-radius: 16px 16px 0 0; padding: 20px;">
                        <p style="margin: 0 0 12px; font-size: 13px; font-weight: 700; color: #374151;">Item no catalogado</p>

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">¿Qué necesitas?</label>
                        <textarea
                            wire:model="descripcionManual"
                            rows="2"
                            placeholder="Ej: Letrero de acrílico &quot;Salida de emergencia&quot;"
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13.5px; font-family: inherit; resize: vertical; margin-bottom: 10px;"
                        ></textarea>

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">Medidas (opcional)</label>
                        <input
                            type="text"
                            wire:model="medidasManual"
                            placeholder="Ej: 40cm x 60cm"
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13.5px; margin-bottom: 10px;"
                        />

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">Foto de referencia (opcional)</label>
                        <input
                            type="file"
                            accept="image/*"
                            capture="environment"
                            wire:model="fotoReferenciaManual"
                            style="width: 100%; margin-bottom: 4px; font-size: 12.5px;"
                        />
                        <div wire:loading wire:target="fotoReferenciaManual" style="font-size: 11px; color: #9ca3af; margin-bottom: 10px;">Subiendo...</div>

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">Cantidad</label>
                        <input
                            type="number"
                            step="0.01"
                            min="0.01"
                            wire:model="cantidadManualTexto"
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13.5px; margin-bottom: 10px;"
                        />

                        @if (count($tiposManual) > 1)
                            <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">Tipo de pedido</label>
                            <div style="display: flex; gap: 6px; margin-bottom: 14px;">
                                @foreach ($tiposManual as $valor => $etiqueta)
                                    <button
                                        type="button"
                                        wire:click="seleccionarTipoManual('{{ $valor }}')"
                                        style="flex: 1; cursor: pointer; border-radius: 8px; padding: 8px 6px; font-size: 12px; font-weight: 700;
                                            border: 1.5px solid {{ $this->tipoManualSeleccionado === $valor ? '#F59E0B' : '#e5e7eb' }};
                                            background: {{ $this->tipoManualSeleccionado === $valor ? '#FFFBEB' : '#ffffff' }};
                                            color: {{ $this->tipoManualSeleccionado === $valor ? '#92400E' : '#374151' }};"
                                    >
                                        {{ $etiqueta }}
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <div style="display: flex; gap: 8px;">
                            <button type="button" wire:click="cancelarManual" style="flex: 1; cursor: pointer; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700; color: #374151;">
                                Cancelar
                            </button>
                            <button type="button" wire:click="confirmarManual" style="flex: 1; cursor: pointer; border: none; background: #F59E0B; color: #ffffff; border-radius: 10px; padding: 10px; font-size: 13px; font-weight: 700;">
                                Agregar al pedido
                            </button>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Carrito --}}
            <div style="border-radius: 14px; border: 1px solid #e5e7eb; background: #ffffff; padding: 14px 16px;">
                <p style="margin: 0 0 10px; font-size: 13px; font-weight: 700; color: #374151;">Pedido ({{ $carrito->count() }})</p>

                @forelse ($carrito as $linea)
                    <div style="display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px solid #f0f0f0;">
                        <span style="flex: 1; min-width: 0; font-size: 12.5px; font-weight: 600; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $linea->nombre }}
                            @if (! $linea->esCatalogado)
                                <span style="font-size: 10px; font-weight: 700; color: #6b7280;">· no catalogado{{ $linea->medidas ? ' · '.$linea->medidas : '' }}</span>
                            @endif
                            @if ($linea->es_sugerido)
                                <span style="font-size: 10px; font-weight: 700; color: #F59E0B;">· sugerido</span>
                            @endif
                        </span>
                        <span style="font-size: 12px; font-weight: 700; color: #111827;">{{ $linea->cantidad }} {{ $linea->unidad }}</span>
                        <button type="button" wire:click="quitarDelCarrito('{{ $linea->clave }}')" style="cursor: pointer; border: none; background: transparent; color: #DC2626;">
                            <x-heroicon-o-trash style="width: 16px; height: 16px;" />
                        </button>
                    </div>
                @empty
                    <p style="margin: 0; font-size: 12.5px; color: #9ca3af;">Todavía no agregaste materiales.</p>
                @endforelse

                <button
                    type="button"
                    wire:click="enviar"
                    @disabled($carrito->isEmpty())
                    style="width: 100%; margin-top: 14px; cursor: pointer; border: none; border-radius: 12px; padding: 14px; font-size: 14px; font-weight: 700; background: {{ $carrito->isEmpty() ? '#E5E7EB' : '#059669' }}; color: {{ $carrito->isEmpty() ? '#9CA3AF' : '#ffffff' }};"
                >
                    Enviar pedido
                </button>
            </div>
        </div>
    @endif
</x-filament-panels::page>
