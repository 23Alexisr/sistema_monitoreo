@php
    use Filament\Forms\Components\Actions\Action;
    use Filament\Support\Enums\Alignment;

    $containers = $getChildComponentContainers();

    $addAction = $getAction($getAddActionName());
    $addBetweenAction = $getAction($getAddBetweenActionName());
    $cloneAction = $getAction($getCloneActionName());
    $collapseAllAction = $getAction($getCollapseAllActionName());
    $expandAllAction = $getAction($getExpandAllActionName());
    $deleteAction = $getAction($getDeleteActionName());
    $moveDownAction = $getAction($getMoveDownActionName());
    $moveUpAction = $getAction($getMoveUpActionName());
    $reorderAction = $getAction($getReorderActionName());
    $extraItemActions = $getExtraItemActions();

    $hasItemNumbers = $hasItemNumbers();
    $isAddable = $isAddable();
    $isCloneable = $isCloneable();
    $isCollapsible = $isCollapsible();
    $isDeletable = $isDeletable();
    $isReorderableWithButtons = $isReorderableWithButtons();
    $isReorderableWithDragAndDrop = $isReorderableWithDragAndDrop();

    $collapseAllActionIsVisible = $isCollapsible && $collapseAllAction->isVisible();
    $expandAllActionIsVisible = $isCollapsible && $expandAllAction->isVisible();

    $statePath = $getStatePath();

    /**
     * Franja de color lateral por item: solo aplica cuando el campo es un
     * App\Filament\Forms\Components\ChecklistItemRepeater (o subclase) con
     * ->itemColor() configurado. Repeaters normales de Filament no se ven
     * afectados por este override de vista.
     */
    $isColoredRepeater = method_exists($field, 'getItemColor');

    /**
     * Agrupación por categoría/subcategoría del trabajo_maestro efectivo de
     * cada item, mismo criterio que EjecutarChecklist::getSeccionesAgrupadas():
     * items sin subcategoría primero (sin sub-encabezado), luego cada
     * subcategoría en uso. Solo se activa si el campo lo pide explícitamente
     * vía ->agruparPorCategoria() (evita afectar el Repeater 'children').
     */
    $agrupar = method_exists($field, 'debeAgruparPorCategoria') && $field->debeAgruparPorCategoria();

    $metaPorUuid = collect();
    $categoriasConSubcategoria = [];
    $orderedUuids = collect($containers)->keys();

    if ($agrupar) {
        $metaPorUuid = $orderedUuids->mapWithKeys(function (string $uuid) use ($field) {
            $state = $field->getRawItemState($uuid);
            $trabajoId = $state['trabajo_maestro_id'] ?? null;
            $maestro = $trabajoId
                ? \App\Models\TrabajoMaestro::with(['categoria', 'subcategoria.categoria'])->find($trabajoId)
                : null;
            $categoria = $maestro?->categoriaEfectiva();
            $subcategoria = $maestro?->subcategoria;

            return [$uuid => [
                'categoriaId' => $categoria?->id ?? 'otros',
                'categoriaNombre' => $categoria?->nombre ?? 'Otros / Items manuales',
                'categoriaColor' => $categoria?->color,
                'categoriaOrden' => $categoria?->orden ?? PHP_INT_MAX,
                'subcategoriaId' => $subcategoria?->id,
                'subcategoriaNombre' => $subcategoria?->nombre,
                'subcategoriaOrden' => $subcategoria?->orden ?? -1,
                'itemOrden' => (int) ($state['orden'] ?? 0),
            ]];
        });

        $categoriasConSubcategoria = $metaPorUuid
            ->groupBy('categoriaId')
            ->map(fn ($grupo) => $grupo->contains(fn ($fila) => filled($fila['subcategoriaId'])))
            ->filter()
            ->keys()
            ->all();

        $orderedUuids = $metaPorUuid
            ->sortBy([
                fn ($a, $b) => $a['categoriaOrden'] <=> $b['categoriaOrden'],
                fn ($a, $b) => $a['subcategoriaOrden'] <=> $b['subcategoriaOrden'],
                fn ($a, $b) => $a['itemOrden'] <=> $b['itemOrden'],
            ])
            ->keys();
    }
@endphp

<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    <div
        x-data="{}"
        {{
            $attributes
                ->merge($getExtraAttributes(), escape: false)
                ->class(['fi-fo-repeater grid gap-y-4'])
        }}
    >
        @if ($collapseAllActionIsVisible || $expandAllActionIsVisible)
            <div
                @class([
                    'flex gap-x-3',
                    'hidden' => count($containers) < 2,
                ])
            >
                @if ($collapseAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('repeater-collapse', '{{ $statePath }}')"
                    >
                        {{ $collapseAllAction }}
                    </span>
                @endif

                @if ($expandAllActionIsVisible)
                    <span
                        x-on:click="$dispatch('repeater-expand', '{{ $statePath }}')"
                    >
                        {{ $expandAllAction }}
                    </span>
                @endif
            </div>
        @endif

        @if (count($containers))
            <ul>
                <x-filament::grid
                    :default="$getGridColumns('default')"
                    :sm="$getGridColumns('sm')"
                    :md="$getGridColumns('md')"
                    :lg="$getGridColumns('lg')"
                    :xl="$getGridColumns('xl')"
                    :two-xl="$getGridColumns('2xl')"
                    :wire:end.stop="'mountFormComponentAction(\'' . $statePath . '\', \'reorder\', { items: $event.target.sortable.toArray() })'"
                    x-sortable
                    :data-sortable-animation-duration="$getReorderAnimationDuration()"
                    class="items-start gap-4"
                >
                    @foreach ($orderedUuids as $uuid)
                        @php
                            $item = $containers[$uuid];
                            $metaActual = $agrupar ? $metaPorUuid[$uuid] : null;
                            $metaAnterior = ($agrupar && ! $loop->first) ? $metaPorUuid[$orderedUuids[$loop->index - 1]] : null;
                            $cambioCategoria = $agrupar && ($metaAnterior === null || $metaAnterior['categoriaId'] !== $metaActual['categoriaId']);
                            $cambioSubcategoria = $agrupar && ($cambioCategoria || $metaAnterior['subcategoriaId'] !== $metaActual['subcategoriaId']);
                            $enSubcategoria = $agrupar && $metaActual['subcategoriaId'] && in_array($metaActual['categoriaId'], $categoriasConSubcategoria, true);
                        @endphp

                        @if ($cambioCategoria)
                            <li style="list-style: none; display: flex; align-items: center; gap: 8px; margin: {{ $loop->first ? '0' : '18' }}px 0 8px; padding-left: 2px;">
                                <span style="width: 10px; height: 10px; border-radius: 999px; flex-shrink: 0; background: {{ $metaActual['categoriaColor'] ?? '#9CA3AF' }};"></span>
                                <span style="font-size: 13.5px; font-weight: 700; color: #111827;">{{ $metaActual['categoriaNombre'] }}</span>
                                <span style="font-size: 12px; color: #6b7280;">
                                    ({{ $metaPorUuid->where('categoriaId', $metaActual['categoriaId'])->count() }} items)
                                </span>
                            </li>
                        @endif

                        @if ($enSubcategoria && $cambioSubcategoria)
                            <li style="list-style: none; margin: 8px 0 6px 14px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #9ca3af;">
                                {{ $metaActual['subcategoriaNombre'] }}
                            </li>
                        @endif

                        @php
                            $itemLabel = $getItemLabel($uuid);
                            $itemColor = $isColoredRepeater ? ($field->getItemColor($uuid) ?? '#9CA3AF') : null;
                            $visibleExtraItemActions = array_filter(
                                $extraItemActions,
                                fn (Action $action): bool => $action(['item' => $uuid])->isVisible(),
                            );
                            $cloneAction = $cloneAction(['item' => $uuid]);
                            $cloneActionIsVisible = $isCloneable && $cloneAction->isVisible();
                            $deleteAction = $deleteAction(['item' => $uuid]);
                            $deleteActionIsVisible = $isDeletable && $deleteAction->isVisible();
                            $moveDownAction = $moveDownAction(['item' => $uuid])->disabled($loop->last);
                            $moveDownActionIsVisible = $isReorderableWithButtons && $moveDownAction->isVisible();
                            $moveUpAction = $moveUpAction(['item' => $uuid])->disabled($loop->first);
                            $moveUpActionIsVisible = $isReorderableWithButtons && $moveUpAction->isVisible();
                            $reorderActionIsVisible = $isReorderableWithDragAndDrop && $reorderAction->isVisible();
                        @endphp

                        <li
                            wire:ignore.self
                            wire:key="{{ $this->getId() }}.{{ $item->getStatePath() }}.{{ $field::class }}.item"
                            x-data="{
                                isCollapsed: @js($isCollapsed($item)),
                            }"
                            x-on:expand="isCollapsed = false"
                            x-on:repeater-expand.window="$event.detail === '{{ $statePath }}' && (isCollapsed = false)"
                            x-on:repeater-collapse.window="$event.detail === '{{ $statePath }}' && (isCollapsed = true)"
                            x-sortable-item="{{ $uuid }}"
                            @class([
                                'fi-fo-repeater-item rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-white/5 dark:ring-white/10',
                                'flex overflow-hidden' => $isColoredRepeater,
                                'divide-y divide-gray-100 dark:divide-white/10' => ! $isColoredRepeater,
                            ])
                            x-bind:class="{ 'fi-collapsed': isCollapsed }"
                            @if ($enSubcategoria) style="margin-left: 14px;" @endif
                        >
                            @if ($isColoredRepeater)
                                <div
                                    class="w-1.5 flex-shrink-0"
                                    style="background-color: {{ $itemColor }};"
                                ></div>
                            @endif

                            <div @class(['flex-1 min-w-0 divide-y divide-gray-100 dark:divide-white/10' => $isColoredRepeater])>
                                @if ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible || filled($itemLabel) || $cloneActionIsVisible || $deleteActionIsVisible || $isCollapsible || $visibleExtraItemActions)
                                    <div
                                        @if ($isCollapsible)
                                            x-on:click.stop="isCollapsed = !isCollapsed"
                                        @endif
                                        @class([
                                            'fi-fo-repeater-item-header flex items-center gap-x-3 overflow-hidden px-4 py-3',
                                            'cursor-pointer select-none' => $isCollapsible,
                                        ])
                                    >
                                        @if ($reorderActionIsVisible || $moveUpActionIsVisible || $moveDownActionIsVisible)
                                            <ul class="flex items-center gap-x-3">
                                                @if ($reorderActionIsVisible)
                                                    <li
                                                        x-sortable-handle
                                                        x-on:click.stop
                                                    >
                                                        {{ $reorderAction }}
                                                    </li>
                                                @endif

                                                @if ($moveUpActionIsVisible || $moveDownActionIsVisible)
                                                    <li
                                                        x-on:click.stop
                                                        class="flex items-center justify-center"
                                                    >
                                                        {{ $moveUpAction }}
                                                    </li>

                                                    <li
                                                        x-on:click.stop
                                                        class="flex items-center justify-center"
                                                    >
                                                        {{ $moveDownAction }}
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif

                                        @if (filled($itemLabel))
                                            <h4
                                                @class([
                                                    'text-sm font-medium text-gray-950 dark:text-white',
                                                    'truncate' => $isItemLabelTruncated(),
                                                ])
                                            >
                                                {{ $itemLabel }}

                                                @if ($hasItemNumbers)
                                                    {{ $loop->iteration }}
                                                @endif
                                            </h4>
                                        @endif

                                        @if ($cloneActionIsVisible || $deleteActionIsVisible || $isCollapsible || $visibleExtraItemActions)
                                            <ul
                                                class="ms-auto flex items-center gap-x-3"
                                            >
                                                @foreach ($visibleExtraItemActions as $extraItemAction)
                                                    <li x-on:click.stop>
                                                        {{ $extraItemAction(['item' => $uuid]) }}
                                                    </li>
                                                @endforeach

                                                @if ($cloneActionIsVisible)
                                                    <li x-on:click.stop>
                                                        {{ $cloneAction }}
                                                    </li>
                                                @endif

                                                @if ($deleteActionIsVisible)
                                                    <li x-on:click.stop>
                                                        {{ $deleteAction }}
                                                    </li>
                                                @endif

                                                @if ($isCollapsible)
                                                    <li
                                                        class="relative transition"
                                                        x-on:click.stop="isCollapsed = !isCollapsed"
                                                        x-bind:class="{ '-rotate-180': isCollapsed }"
                                                    >
                                                        <div
                                                            class="transition"
                                                            x-bind:class="{ 'opacity-0 pointer-events-none': isCollapsed }"
                                                        >
                                                            {{ $getAction('collapse') }}
                                                        </div>

                                                        <div
                                                            class="absolute inset-0 rotate-180 transition"
                                                            x-bind:class="{ 'opacity-0 pointer-events-none': ! isCollapsed }"
                                                        >
                                                            {{ $getAction('expand') }}
                                                        </div>
                                                    </li>
                                                @endif
                                            </ul>
                                        @endif
                                    </div>
                                @endif

                                <div
                                    x-show="! isCollapsed"
                                    class="fi-fo-repeater-item-content p-4"
                                >
                                    {{ $item }}
                                </div>
                            </div>
                        </li>

                        @if (! $loop->last)
                            @if ($isAddable && $addBetweenAction(['afterItem' => $uuid])->isVisible())
                                <li class="flex w-full justify-center">
                                    <div
                                        class="fi-fo-repeater-add-between-action-ctn rounded-lg bg-white dark:bg-gray-900"
                                    >
                                        {{ $addBetweenAction(['afterItem' => $uuid]) }}
                                    </div>
                                </li>
                            @elseif (filled($labelBetweenItems = $getLabelBetweenItems()))
                                <li
                                    class="relative border-t border-gray-200 dark:border-white/10"
                                >
                                    <span
                                        class="absolute -top-3 left-3 px-1 text-sm font-medium"
                                    >
                                        {{ $labelBetweenItems }}
                                    </span>
                                </li>
                            @endif
                        @endif
                    @endforeach
                </x-filament::grid>
            </ul>
        @endif

        @if ($isAddable && $addAction->isVisible())
            <div
                @class([
                    'flex',
                    match ($getAddActionAlignment()) {
                        Alignment::Start, Alignment::Left => 'justify-start',
                        Alignment::Center, null => 'justify-center',
                        Alignment::End, Alignment::Right => 'justify-end',
                        default => $alignment,
                    },
                ])
            >
                {{ $addAction }}
            </div>
        @endif
    </div>
</x-dynamic-component>
