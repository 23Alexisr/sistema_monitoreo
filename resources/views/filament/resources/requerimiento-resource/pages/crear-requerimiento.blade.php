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
                            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                                <p style="margin: 0; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                                    {{ $nombreSubcategoria }}
                                </p>
                                <button
                                    type="button"
                                    wire:click="agregarGrupoCompleto(@js($nombreSubcategoria))"
                                    style="flex: none; cursor: pointer; border: 1px solid #F59E0B; background: #FFFBEB; color: #92400E; border-radius: 8px; padding: 4px 9px; font-size: 10.5px; font-weight: 700; white-space: nowrap;"
                                >
                                    + Agregar todo el grupo
                                </button>
                            </div>
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
                <div style="position: fixed; top: 0; right: 0; bottom: 0; left: 0; min-height: 100dvh; background: rgba(0,0,0,0.4); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 24px 16px; z-index: 50;" wire:click.self="cancelarCantidad">
                    <div style="width: 100%; max-width: 420px; max-height: 90dvh; overflow-y: auto; background: #ffffff; border-radius: 16px; padding: 20px; margin: auto;">
                        <p style="margin: 0 0 4px; font-size: 13px; font-weight: 700; color: #374151;">
                            {{ $materialElegido?->nombre }}
                            @if ($materialElegido?->dimensiones())
                                <span style="font-weight: 600; color: #6b7280;">· {{ $materialElegido->dimensiones() }}</span>
                            @endif
                        </p>
                        <p style="margin: 0 0 12px; font-size: 11.5px; color: #9ca3af;">¿Cuánto necesitas? ({{ $materialElegido?->unidad_medida }})</p>

                        <input
                            type="number"
                            step="{{ $this->modoFlujo === 'señaletica' ? '1' : '0.01' }}"
                            min="{{ $this->modoFlujo === 'señaletica' ? '1' : '0.01' }}"
                            wire:model="cantidadTexto"
                            autofocus
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 15px; margin-bottom: 12px;"
                        />

                        @if ($this->materialRequiereMedidaPedido($materialElegido))
                            <p style="margin: 0 0 6px; font-size: 11.5px; color: #9ca3af;">Este material no tiene medida fija, especifícala para este pedido:</p>
                            <div style="display: flex; gap: 8px; margin-bottom: 12px;">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="anchoPedidoTexto"
                                    placeholder="Ancho (m)"
                                    style="flex: 1; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 15px;"
                                />
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    wire:model="largoPedidoTexto"
                                    placeholder="Largo (m)"
                                    style="flex: 1; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 10px 12px; font-size: 15px;"
                                />
                            </div>
                        @endif

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
                <div style="position: fixed; top: 0; right: 0; bottom: 0; left: 0; min-height: 100dvh; background: rgba(0, 0, 0, 0.55); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 24px 16px; z-index: 55;" wire:click.self="cancelarManual">
                    <div style="width: 100%; max-width: 420px; max-height: 90dvh; overflow-y: auto; background: #ffffff; border-radius: 16px; padding: 20px;">
                        <p style="margin: 0 0 12px; font-size: 13px; font-weight: 700; color: #374151;">Item no catalogado</p>

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">¿Qué necesitas?</label>
                        <textarea
                            wire:model="descripcionManual"
                            rows="2"
                            placeholder="Ej: Letrero de acrílico &quot;Salida de emergencia&quot;"
                            style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid #e5e7eb; padding: 9px 10px; font-size: 13.5px; font-family: inherit; resize: vertical; margin-bottom: 10px;"
                        ></textarea>

                        <label style="display: block; margin-bottom: 4px; font-size: 11.5px; font-weight: 700; color: #6b7280;">Medidas</label>
                        <div style="display: flex; gap: 8px; margin-bottom: 4px;">
                            <div style="flex: 1;">
                                <div style="position: relative;">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        wire:model="anchoManualTexto"
                                        placeholder="Ancho"
                                        style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid {{ $this->errorAnchoManual ? '#DC2626' : '#e5e7eb' }}; padding: 9px 28px 9px 10px; font-size: 13.5px;"
                                    />
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 11.5px; color: #9ca3af;">m</span>
                                </div>
                                @if ($this->errorAnchoManual)
                                    <p style="margin: 4px 0 0; font-size: 11px; color: #DC2626; font-weight: 600;">{{ $this->errorAnchoManual }}</p>
                                @endif
                            </div>
                            <div style="flex: 1;">
                                <div style="position: relative;">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0.01"
                                        wire:model="largoManualTexto"
                                        placeholder="Alto"
                                        style="width: 100%; box-sizing: border-box; border-radius: 10px; border: 1px solid {{ $this->errorLargoManual ? '#DC2626' : '#e5e7eb' }}; padding: 9px 28px 9px 10px; font-size: 13.5px;"
                                    />
                                    <span style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); font-size: 11.5px; color: #9ca3af;">m</span>
                                </div>
                                @if ($this->errorLargoManual)
                                    <p style="margin: 4px 0 0; font-size: 11px; color: #DC2626; font-weight: 600;">{{ $this->errorLargoManual }}</p>
                                @endif
                            </div>
                        </div>
                        <p style="margin: 0 0 10px; font-size: 11px; color: #9ca3af;">Ej: 1.44 x 0.58 m</p>

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

            {{-- Espacio para que el contenido no quede tapado por el FAB del pedido --}}
            <div style="height: 76px;"></div>
        </div>

        {{-- Resumen de pedido persistente: FAB con badge + bottom sheet con el detalle agrupado --}}
        <div x-data="{ pedidoAbierto: false }">
            <button
                type="button"
                @click="pedidoAbierto = true"
                style="position: fixed; right: 18px; bottom: 18px; z-index: 46; cursor: pointer; width: 56px; height: 56px; border-radius: 50%; border: none; background: #111827; color: #ffffff; box-shadow: 0 8px 20px -4px rgba(0,0,0,0.45); display: flex; align-items: center; justify-content: center;"
            >
                <x-heroicon-o-shopping-cart style="width: 24px; height: 24px;" />
                @if ($carrito->isNotEmpty())
                    <span style="position: absolute; top: -2px; right: -2px; min-width: 20px; height: 20px; padding: 0 4px; border-radius: 999px; background: #F59E0B; color: #111827; font-size: 11px; font-weight: 800; display: flex; align-items: center; justify-content: center; line-height: 1; box-shadow: 0 0 0 2px #ffffff;">
                        {{ $carrito->count() }}
                    </span>
                @endif
            </button>

            @php $gruposCarrito = $this->carritoAgrupado(); @endphp
            <div
                x-show="pedidoAbierto"
                x-cloak
                @click.outside="pedidoAbierto = false"
                style="position: fixed; top: 24px; right: 24px; width: 100%; max-width: 360px; max-height: calc(100dvh - 48px); overflow: hidden; border-radius: 16px; background: #ffffff; box-shadow: 0 20px 40px -12px rgba(17,24,39,0.28), 0 0 0 1px rgba(17,24,39,0.06); z-index: 9999; display: flex; flex-direction: column;"
            >
                <div style="background: #F59E0B; padding: 12px 14px 14px; flex: none;">
                    <div style="display: flex; align-items: flex-start; justify-content: space-between; gap: 10px;">
                        <div style="min-width: 0;">
                            <p style="margin: 0 0 2px; font-size: 11.5px; color: rgba(255,255,255,0.75); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                {{ $obra->nombre }}
                            </p>
                            <p style="margin: 0; font-size: 16px; font-weight: 800; color: #ffffff;">
                                {{ $this->modoFlujo === 'señaletica' ? 'Pedido de señalética' : 'Pedido de materiales' }}
                            </p>
                        </div>
                        <button type="button" @click="pedidoAbierto = false" style="flex: none; cursor: pointer; border: none; width: 28px; height: 28px; border-radius: 50%; background: rgba(255,255,255,0.25); color: #ffffff; display: flex; align-items: center; justify-content: center;">
                            <x-heroicon-o-x-mark style="width: 16px; height: 16px;" />
                        </button>
                    </div>
                </div>

                <div style="flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 4px 14px 0;">
                    @forelse ($gruposCarrito as $nombreGrupo => $items)
                        <div style="margin-top: 12px;">
                            @if ($gruposCarrito->count() > 1)
                                <p style="margin: 0 0 6px; display: flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: #F59E0B; flex: none;"></span>
                                    {{ $nombreGrupo }}
                                </p>
                            @endif

                            @foreach ($items as $linea)
                                <div style="display: flex; align-items: center; gap: 8px; padding: 8px 0; border-bottom: 1px dashed #E5E7EB;">
                                    <div style="width: 34px; height: 34px; flex: none; border-radius: 8px; overflow: hidden; background: {{ $linea->material?->categoriaEfectiva()?->color ?? '#F3F4F6' }}; display: flex; align-items: center; justify-content: center;">
                                        @if ($linea->material?->fotoUrl())
                                            <img src="{{ $linea->material->fotoUrl() }}" alt="{{ $linea->nombre }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
                                        @elseif (! $linea->material?->categoriaEfectiva()?->color)
                                            <x-heroicon-o-cube style="width: 14px; height: 14px; color: #9CA3AF;" />
                                        @endif
                                    </div>
                                    <div style="flex: 1; min-width: 0;">
                                        <p style="margin: 0; font-size: 12px; font-weight: 600; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $linea->nombre }}
                                        </p>
                                        <p style="margin: 1px 0 0; font-size: 10.5px; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                            {{ $linea->dimensiones ?? $linea->unidad }}
                                            @if (! $linea->esCatalogado)
                                                <span style="font-weight: 700;">· no catalogado</span>
                                            @endif
                                            @if ($linea->es_sugerido)
                                                <span style="color: #F59E0B; font-weight: 700;">· sugerido</span>
                                            @endif
                                        </p>
                                    </div>

                                    @if ($this->modoFlujo === 'señaletica')
                                        <div style="flex: none; display: flex; align-items: center; gap: 1px; background: #F3F4F6; border-radius: 999px; padding: 2px;">
                                            <button type="button" wire:click="decrementarCantidadCarrito('{{ $linea->clave }}')" style="width: 20px; height: 20px; flex: none; border-radius: 50%; border: none; background: #ffffff; color: #374151; font-size: 13px; font-weight: 800; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(17,24,39,0.08);">−</button>
                                            <span style="width: 20px; text-align: center; font-size: 11.5px; font-weight: 700; color: #111827;">{{ (int) $linea->cantidad }}</span>
                                            <button type="button" wire:click="incrementarCantidadCarrito('{{ $linea->clave }}')" style="width: 20px; height: 20px; flex: none; border-radius: 50%; border: none; background: #ffffff; color: #374151; font-size: 13px; font-weight: 800; line-height: 1; cursor: pointer; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 2px rgba(17,24,39,0.08);">+</button>
                                        </div>
                                    @else
                                        <input
                                            type="number"
                                            step="0.01"
                                            min="0.01"
                                            value="{{ $linea->cantidad }}"
                                            wire:change="actualizarCantidadCarrito('{{ $linea->clave }}', $event.target.value)"
                                            style="width: 48px; flex: none; box-sizing: border-box; border-radius: 8px; border: 1px solid #e5e7eb; padding: 4px 5px; font-size: 11.5px; font-weight: 700; text-align: center;"
                                        />
                                    @endif

                                    <button type="button" wire:click="quitarDelCarrito('{{ $linea->clave }}')" style="flex: none; cursor: pointer; border: none; background: transparent; color: #DC2626; margin-left: 1px;">
                                        <x-heroicon-o-trash style="width: 14px; height: 14px;" />
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @empty
                        <p style="margin: 12px 0 0; font-size: 12.5px; color: #9ca3af;">Todavía no agregaste materiales.</p>
                    @endforelse
                </div>

                <div style="flex: none; background: #F9FAFB; border-top: 1px solid #E5E7EB; padding: 10px 14px; display: flex; align-items: center; justify-content: space-between; gap: 12px;">
                    <span style="font-size: 12.5px; font-weight: 700; color: #374151;">
                        {{ $carrito->count() }} {{ \Illuminate\Support\Str::plural('item', $carrito->count()) }}
                    </span>
                    <button
                        type="button"
                        wire:click="revisarPedido"
                        @click="pedidoAbierto = false"
                        @disabled($carrito->isEmpty())
                        style="cursor: pointer; border: none; border-radius: 10px; padding: 9px 18px; font-size: 12.5px; font-weight: 700; background: {{ $carrito->isEmpty() ? '#E5E7EB' : '#059669' }}; color: {{ $carrito->isEmpty() ? '#9CA3AF' : '#ffffff' }};"
                    >
                        Revisar pedido
                    </button>
                </div>
            </div>
        </div>

        {{-- Previsualización final antes de confirmar --}}
        @if ($this->revisandoPedido)
            @php $anterior = $this->requerimientoOriginalSeleccionado(); @endphp
            <div style="position: fixed; top: 0; right: 0; bottom: 0; left: 0; min-height: 100dvh; background: rgba(0, 0, 0, 0.55); display: flex; align-items: center; justify-content: center; overflow-y: auto; padding: 24px 16px; z-index: 55;" wire:click.self="volverAEditarPedido">
                <div style="width: 100%; max-width: 400px; max-height: 90dvh; overflow-y: auto; background: #ffffff; border-radius: 16px; display: flex; flex-direction: column;">
                    <div style="background: #F59E0B; padding: 14px 16px 16px; border-radius: 16px 16px 0 0; flex: none;">
                        <p style="margin: 0 0 2px; font-size: 12px; color: rgba(255,255,255,0.75); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                            {{ $obra->nombre }}
                        </p>
                        <p style="margin: 0; font-size: 18px; font-weight: 800; color: #ffffff;">Revisar pedido</p>
                    </div>

                    <div style="padding: 16px;">
                        @if ($anterior)
                            <div style="border-radius: 10px; border: 1px solid #F59E0B; background: #FFFBEB; padding: 10px 12px; margin-bottom: 4px;">
                                <p style="margin: 0; font-size: 10.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #92400E;">Adicional de</p>
                                <p style="margin: 2px 0 0; font-size: 13px; font-weight: 700; color: #92400E;">
                                    #{{ $anterior->id }} · {{ $anterior->tipo->label() }} · {{ $anterior->fecha_solicitud->format('d/m/Y') }}
                                </p>
                            </div>
                        @endif

                        <p style="margin: 16px 0 6px; display: flex; align-items: center; gap: 6px; font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.05em; color: #9ca3af;">
                            <span style="width: 6px; height: 6px; border-radius: 50%; background: #F59E0B; flex: none;"></span>
                            Items ({{ $carrito->count() }})
                        </p>

                        @foreach ($carrito as $linea)
                            <div style="display: flex; align-items: center; gap: 10px; padding: 10px 0; border-bottom: 1px dashed #E5E7EB;">
                                <div style="width: 44px; height: 44px; flex: none; border-radius: 10px; overflow: hidden; background: {{ $linea->material?->categoriaEfectiva()?->color ?? '#F3F4F6' }}; display: flex; align-items: center; justify-content: center;">
                                    @if ($linea->material?->fotoUrl())
                                        <img src="{{ $linea->material->fotoUrl() }}" alt="{{ $linea->nombre }}" style="width: 100%; height: 100%; object-fit: cover; display: block;" />
                                    @elseif (! $linea->material?->categoriaEfectiva()?->color)
                                        <x-heroicon-o-cube style="width: 18px; height: 18px; color: #9CA3AF;" />
                                    @endif
                                </div>
                                <div style="flex: 1; min-width: 0;">
                                    <p style="margin: 0; font-size: 12.5px; font-weight: 600; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $linea->nombre }}
                                    </p>
                                    <p style="margin: 2px 0 0; font-size: 11px; color: #9ca3af; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                        {{ $linea->dimensiones ?? $linea->unidad }}
                                        @if (! $linea->esCatalogado)
                                            <span style="font-weight: 700;">· no catalogado</span>
                                        @endif
                                    </p>
                                </div>
                                <span style="flex: none; font-size: 13px; font-weight: 700; color: #111827;">{{ $linea->cantidad }} {{ $linea->unidad }}</span>
                            </div>
                        @endforeach

                        <div style="display: flex; gap: 8px; margin-top: 16px;">
                            <button type="button" wire:click="volverAEditarPedido" style="flex: 1; cursor: pointer; border: 1px solid #e5e7eb; background: #ffffff; border-radius: 10px; padding: 11px 22px; font-size: 13.5px; font-weight: 700; color: #374151;">
                                Volver a editar
                            </button>
                            <button type="button" wire:click="enviar" style="flex: 1; cursor: pointer; border: none; background: #059669; color: #ffffff; border-radius: 10px; padding: 11px 22px; font-size: 13.5px; font-weight: 700;">
                                Confirmar pedido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        @endif
    @endif
</x-filament-panels::page>
