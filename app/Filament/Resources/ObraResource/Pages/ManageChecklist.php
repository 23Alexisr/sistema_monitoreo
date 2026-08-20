<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Forms\Components\ChecklistItemRepeater;
use App\Filament\Resources\ObraResource;
use App\Models\CategoriaTrabajo;
use App\Models\Checklist;
use App\Models\TrabajoMaestro;
use App\Support\OrdenValidator;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class ManageChecklist extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.manage-checklist';

    protected static ?string $title = 'Armar checklist';

    protected static ?string $navigationLabel = 'Armar checklist';

    protected const LIMITE_BUSQUEDA = 20;

    protected const LIMITE_RECIENTES = 8;

    public ?array $data = [];

    public ?Checklist $checklist = null;

    public bool $panelBusquedaAbierto = false;

    public string $busquedaCatalogo = '';

    public bool $formularioManualAbierto = false;

    public ?string $manualDescripcion = null;

    public ?string $manualDiasEstimados = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->authorizeAccess();

        $ordenTrabajo = $this->record->ordenTrabajo;

        if ($ordenTrabajo) {
            $this->checklist = $ordenTrabajo->checklist ?? $ordenTrabajo->checklist()->create();
        }

        $this->form->fill();
        $this->hidratarRepetidoresDesdeRelaciones();
    }

    /**
     * Bug de Filament: en una Page custom (no EditRecord), $this->form->fill()
     * sin argumentos no dispara loadStateFromRelationships() para los
     * Repeater ->relationship(), dejando items/children existentes con un
     * item vacío en vez de los datos reales — y si se guarda así, borra los
     * items existentes (se tratan como huérfanos). Se fuerza la hidratación
     * real después del fill() inicial, recursivamente para sub-repeaters.
     */
    protected function hidratarRepetidoresDesdeRelaciones(): void
    {
        $hidratar = function (Forms\Components\Repeater $repeater) use (&$hidratar): void {
            $repeater->fillFromRelationship();

            foreach ($repeater->getChildComponentContainers() as $container) {
                foreach ($container->getComponents() as $component) {
                    if ($component instanceof Forms\Components\Repeater) {
                        $hidratar($component);
                    }
                }
            }
        };

        foreach ($this->form->getComponents() as $component) {
            if ($component instanceof Forms\Components\Repeater) {
                $hidratar($component);
            }
        }
    }

    public static function canAccess(array $parameters = []): bool
    {
        return auth()->user()?->hasAnyRole(['administrador', 'jefe_planta']) ?? false;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(['record' => $this->record]), 403);
    }

    public function abrirPanelBusqueda(): void
    {
        $this->panelBusquedaAbierto = true;
        $this->formularioManualAbierto = false;
    }

    public function cerrarPanelBusqueda(): void
    {
        $this->panelBusquedaAbierto = false;
        $this->busquedaCatalogo = '';
        $this->formularioManualAbierto = false;
        $this->manualDescripcion = null;
        $this->manualDiasEstimados = null;
    }

    protected function baseQueryCatalogo(): Builder
    {
        $clienteId = $this->record->cliente_id;

        return TrabajoMaestro::query()
            ->where('activo', true)
            ->where(fn ($q) => $q->whereNull('cliente_id')->when($clienteId, fn ($q) => $q->orWhere('cliente_id', $clienteId)))
            ->with(['categoria', 'subcategoria.categoria']);
    }

    /**
     * @return array{items: Collection<int, TrabajoMaestro>, total: ?int}
     */
    public function resultadosBusqueda(): array
    {
        $termino = trim($this->busquedaCatalogo);

        if ($termino === '') {
            $items = $this->baseQueryCatalogo()
                ->withCount('checklistItems')
                ->orderByDesc('checklist_items_count')
                ->orderBy('descripcion')
                ->limit(self::LIMITE_RECIENTES)
                ->get();

            return ['items' => $items, 'total' => null];
        }

        $query = $this->baseQueryCatalogo()->where(fn ($q) => $q
            ->where('descripcion', 'like', "%{$termino}%")
            ->orWhere('codigo', 'like', "%{$termino}%"));

        $total = (clone $query)->count();
        $items = $query->orderBy('descripcion')->limit(self::LIMITE_BUSQUEDA)->get();

        return ['items' => $items, 'total' => $total];
    }

    public function resaltarCoincidencia(string $texto, string $termino): HtmlString
    {
        $termino = trim($termino);

        if ($termino === '') {
            return new HtmlString(e($texto));
        }

        $posicion = mb_stripos($texto, $termino);

        if ($posicion === false) {
            return new HtmlString(e($texto));
        }

        $antes = mb_substr($texto, 0, $posicion);
        $coincide = mb_substr($texto, $posicion, mb_strlen($termino));
        $despues = mb_substr($texto, $posicion + mb_strlen($termino));

        return new HtmlString(e($antes).'<strong>'.e($coincide).'</strong>'.e($despues));
    }

    protected function siguienteOrden(): int
    {
        return (int) (collect($this->data['items'] ?? [])->max('orden') ?? 0) + 1;
    }

    public function agregarDesdeBusqueda(int $trabajoMaestroId): void
    {
        $maestro = TrabajoMaestro::find($trabajoMaestroId);

        if (! $maestro) {
            return;
        }

        $items = $this->data['items'] ?? [];

        $items[(string) Str::uuid()] = [
            'id' => null,
            'checklist_id' => $this->checklist?->id,
            'trabajo_maestro_id' => $maestro->id,
            'parent_id' => null,
            'descripcion' => $maestro->descripcion,
            'dias_estimados_override' => (string) $maestro->dias_estimados,
            'orden' => $this->siguienteOrden(),
            'requiere_foto' => $maestro->requiere_foto,
            'observaciones' => null,
            'editar_orden_manual' => false,
            'children' => [],
        ];

        $this->data['items'] = $items;

        Notification::make()->success()->title('"'.$maestro->descripcion.'" agregado')->send();
    }

    public function agregarManual(): void
    {
        $this->validate([
            'manualDescripcion' => ['required', 'string', 'max:255'],
            'manualDiasEstimados' => ['required', 'numeric', 'min:0.01'],
        ], [], [
            'manualDescripcion' => 'descripción',
            'manualDiasEstimados' => 'días estimados',
        ]);

        $items = $this->data['items'] ?? [];

        $items[(string) Str::uuid()] = [
            'id' => null,
            'checklist_id' => $this->checklist?->id,
            'trabajo_maestro_id' => null,
            'parent_id' => null,
            'descripcion' => $this->manualDescripcion,
            'dias_estimados_override' => $this->manualDiasEstimados,
            'orden' => $this->siguienteOrden(),
            'requiere_foto' => false,
            'observaciones' => null,
            'editar_orden_manual' => false,
            'children' => [],
        ];

        $this->data['items'] = $items;

        $this->manualDescripcion = null;
        $this->manualDiasEstimados = null;
        $this->formularioManualAbierto = false;

        Notification::make()->success()->title('Item manual agregado')->send();
    }

    public static function catalogoOptions(?int $clienteId, ?int $categoriaId = null, ?int $subcategoriaId = null): array
    {
        return TrabajoMaestro::query()
            ->where('activo', true)
            ->where(fn ($q) => $q->whereNull('cliente_id')->when($clienteId, fn ($q) => $q->orWhere('cliente_id', $clienteId)))
            ->with(['categoria', 'subcategoria.categoria'])
            ->get()
            ->when(
                $subcategoriaId,
                fn ($coleccion) => $coleccion->filter(fn ($item) => $item->subcategoria_id === $subcategoriaId),
                fn ($coleccion) => $coleccion->when($categoriaId, fn ($c) => $c->filter(fn ($item) => $item->categoriaEfectiva()?->id === $categoriaId)),
            )
            ->sortBy(fn ($item) => [$item->categoriaEfectiva()?->orden ?? PHP_INT_MAX, $item->descripcion])
            ->groupBy(fn ($item) => $item->categoriaEfectiva()?->nombre ?? 'Sin categoría')
            ->map(fn ($grupo) => $grupo->pluck('descripcion', 'id'))
            ->toArray();
    }

    /**
     * Si este campo pertenece a un sub-item (dentro del Repeater 'children'),
     * devuelve el trabajo_maestro del item padre. Para items de nivel raíz,
     * o si el padre es manual (sin trabajo_maestro_id), devuelve null — sin
     * restricción de catálogo en ambos casos.
     */
    protected static function trabajoMaestroDelPadre(Forms\Get $get): ?TrabajoMaestro
    {
        $trabajoIdPadre = $get('../../trabajo_maestro_id');

        return $trabajoIdPadre ? TrabajoMaestro::find($trabajoIdPadre) : null;
    }

    /**
     * Igual que categoriaDelTrabajo(), pero a partir del estado crudo del
     * item (array), no de un Get con scope de campo. La necesita el
     * itemColor() del Repeater, que solo recibe el estado del item.
     */
    protected static function categoriaDelTrabajoDesdeEstado(array $state): ?CategoriaTrabajo
    {
        $trabajoId = $state['trabajo_maestro_id'] ?? null;

        if (! $trabajoId) {
            return null;
        }

        return TrabajoMaestro::find($trabajoId)?->categoriaEfectiva();
    }

    /**
     * Para items del checklist en edición cuya descripción se repite en 2 o
     * más (top-level y sub-items juntos, comparación exacta), arma un texto
     * "N de M" que refleja la posición del item DENTRO de su propio grupo
     * de duplicados (ordenados por su campo orden), no su orden global. Se
     * recalcula desde el estado en vivo del Repeater, no desde la base de
     * datos, para reflejar altas/bajas sin guardar todavía.
     *
     * @return array<string, string> uuid del item en el Repeater => "N de M"
     */
    protected function contadoresRepeticion(): array
    {
        $planos = collect();

        collect($this->data['items'] ?? [])->each(function (array $item, string $key) use ($planos) {
            $planos->push(['key' => $key, 'descripcion' => $item['descripcion'] ?? null, 'orden' => $item['orden'] ?? 0]);

            collect($item['children'] ?? [])->each(function (array $child, string $childKey) use ($planos) {
                $planos->push(['key' => $childKey, 'descripcion' => $child['descripcion'] ?? null, 'orden' => $child['orden'] ?? 0]);
            });
        });

        $contadores = [];

        $planos->filter(fn (array $fila) => filled($fila['descripcion']))
            ->groupBy('descripcion')
            ->each(function (Collection $grupo) use (&$contadores) {
                if ($grupo->count() < 2) {
                    return;
                }

                $ordenados = $grupo->sortBy('orden')->values();
                $total = $ordenados->count();

                $ordenados->each(function (array $fila, int $indice) use (&$contadores, $total) {
                    $contadores[$fila['key']] = ($indice + 1).' de '.$total;
                });
            });

        return $contadores;
    }

    protected static function itemLabelHtml(array $state, array $contadores = [], ?string $uuid = null): HtmlString
    {
        $descripcion = filled($state['descripcion'] ?? null) ? $state['descripcion'] : 'Nuevo item';
        $dias = $state['dias_estimados_override'] ?? null;
        $contador = $uuid ? ($contadores[$uuid] ?? null) : null;

        $html = $contador
            ? '<span style="font-weight:700;color:var(--text-secondary,#9ca3af);">'.e($contador).'</span> '
            : '';

        $html .= '<span>'.e($descripcion).'</span>';

        if (filled($dias)) {
            $html .= ' <span style="font-weight:400;color:var(--text-secondary,#6b7280);">· '.e($dias).' días</span>';
        }

        return new HtmlString($html);
    }

    protected static function seccionLabel(string $texto): Forms\Components\Placeholder
    {
        return Forms\Components\Placeholder::make('label_'.Str::slug($texto, '_'))
            ->hiddenLabel()
            ->content(new HtmlString(
                '<p style="margin:0;font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:0.06em;color:var(--text-secondary,#9ca3af);">'
                .e($texto).'</p>'
            ))
            ->columnSpanFull();
    }

    protected static function chip(Forms\Components\Component $component): Forms\Components\Group
    {
        return Forms\Components\Group::make([$component])
            ->extraAttributes([
                'style' => 'border:1px solid var(--border-strong,#e5e7eb); border-radius:10px; padding:8px 12px; background:transparent;',
            ]);
    }

    protected static function ordenMaximoHermanos(Forms\Get $get, mixed $excluirValor = null): int
    {
        $hermanos = collect($get('../') ?? []);

        if ($excluirValor !== null) {
            $hermanos = $hermanos->reject(fn ($item) => (int) ($item['orden'] ?? -1) === (int) $excluirValor);
        }

        return (int) ($hermanos->max('orden') ?? 0);
    }

    protected static function ordenSugerido(Forms\Get $get): int
    {
        return OrdenValidator::sugerido(static::ordenMaximoHermanos($get));
    }

    protected static function ordenAdvertencia(Forms\Get $get, ?int $orden): ?string
    {
        if (blank($orden) || $orden < 1) {
            return null;
        }

        $ownDescripcion = $get('descripcion');

        $duplicado = collect($get('../') ?? [])->first(fn ($item) => (int) ($item['orden'] ?? -1) === (int) $orden
            && ($item['descripcion'] ?? null) !== $ownDescripcion);

        $maxOtros = static::ordenMaximoHermanos($get, $orden);

        return OrdenValidator::advertencia($orden, $maxOtros, $duplicado['descripcion'] ?? null);
    }

    protected function itemFields(): array
    {
        return [
            static::seccionLabel('Identificación'),

            Forms\Components\Select::make('trabajo_maestro_id')
                ->label('Trabajo del catálogo')
                ->helperText(function (Forms\Get $get) {
                    $padre = static::trabajoMaestroDelPadre($get);

                    if (! $padre) {
                        return 'Deja en blanco para un item manual.';
                    }

                    return $padre->subcategoria_id
                        ? 'Deja en blanco para un item manual. Filtrado a la subcategoría del trabajo padre.'
                        : 'Deja en blanco para un item manual. Filtrado a la categoría del trabajo padre.';
                })
                ->options(function (Forms\Get $get) {
                    $padre = static::trabajoMaestroDelPadre($get);

                    if (! $padre) {
                        return static::catalogoOptions($this->record->cliente_id);
                    }

                    return $padre->subcategoria_id
                        ? static::catalogoOptions($this->record->cliente_id, subcategoriaId: $padre->subcategoria_id)
                        : static::catalogoOptions($this->record->cliente_id, categoriaId: $padre->categoriaEfectiva()?->id);
                })
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! $state) {
                        $set('requiere_foto', false);

                        return;
                    }

                    $maestro = TrabajoMaestro::find($state);

                    if ($maestro) {
                        $set('descripcion', $maestro->descripcion);
                        $set('dias_estimados_override', $maestro->dias_estimados);
                        $set('requiere_foto', $maestro->requiere_foto);
                    }
                })
                ->columnSpanFull(),
            Forms\Components\TextInput::make('descripcion')
                ->required()
                ->live(onBlur: true)
                ->columnSpanFull(),

            static::seccionLabel('Configuración'),

            Forms\Components\Grid::make(3)
                ->schema([
                    static::chip(
                        Forms\Components\TextInput::make('dias_estimados_override')
                            ->label('Días estimados')
                            ->numeric()
                            ->step(0.01)
                            ->minValue(0.01)
                            ->required()
                            ->live(onBlur: true)
                    ),
                    static::chip(
                        Forms\Components\TextInput::make('orden')
                            ->label('Orden')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->required()
                            ->live(onBlur: true)
                            ->default(fn (Forms\Get $get) => static::ordenSugerido($get))
                            ->disabled(fn (Forms\Get $get) => ! $get('editar_orden_manual'))
                            ->dehydrated(true)
                            ->hint(fn (Forms\Get $get, $state) => static::ordenAdvertencia($get, filled($state) ? (int) $state : null))
                            ->hintColor('warning')
                            ->hintIcon(fn (Forms\Get $get, $state) => static::ordenAdvertencia($get, filled($state) ? (int) $state : null) ? 'heroicon-o-exclamation-triangle' : null)
                            ->suffixAction(
                                Action::make('editarOrden')
                                    ->icon(fn (Forms\Get $get) => $get('editar_orden_manual') ? 'heroicon-m-lock-open' : 'heroicon-m-pencil-square')
                                    ->tooltip('Editar orden manualmente')
                                    ->action(fn (Forms\Get $get, Forms\Set $set) => $set('editar_orden_manual', ! $get('editar_orden_manual')))
                            )
                    ),
                    static::chip(
                        Forms\Components\Group::make([
                            Forms\Components\Toggle::make('requiere_foto')
                                ->label('Requiere foto')
                                ->visible(fn (Forms\Get $get) => blank($get('trabajo_maestro_id')))
                                ->dehydratedWhenHidden(),
                            Forms\Components\Placeholder::make('requiere_foto_info')
                                ->label('Requiere foto')
                                ->content(fn (Forms\Get $get) => $get('requiere_foto') ? 'Sí' : 'No')
                                ->visible(fn (Forms\Get $get) => filled($get('trabajo_maestro_id'))),
                        ])
                    ),
                ]),

            Forms\Components\Hidden::make('editar_orden_manual')
                ->default(false)
                ->dehydrated(false),

            Section::make('+ Observaciones (opcional)')
                ->collapsible()
                ->collapsed()
                ->compact()
                ->schema([
                    Forms\Components\Textarea::make('observaciones')
                        ->hiddenLabel()
                        ->columnSpanFull(),
                ])
                ->columnSpanFull(),
        ];
    }

    public function form(Form $form): Form
    {
        if (! $this->checklist) {
            return $form->schema([]);
        }

        return $form
            ->model($this->checklist)
            ->statePath('data')
            ->schema([
                ChecklistItemRepeater::make('items')
                    ->relationship('items')
                    ->label('Items del checklist')
                    ->reorderable(false)
                    ->collapsible()
                    ->collapsed()
                    ->agruparPorCategoria()
                    ->addActionLabel('Agregar item')
                    ->addAction(fn (Action $action) => $action->action(fn () => $this->abrirPanelBusqueda()))
                    ->itemLabel(fn (array $state, string $uuid) => static::itemLabelHtml($state, $this->contadoresRepeticion(), $uuid))
                    ->itemColor(fn (array $state) => static::categoriaDelTrabajoDesdeEstado($state)?->color)
                    ->schema([
                        ...$this->itemFields(),
                        ChecklistItemRepeater::make('children')
                            ->relationship('children')
                            ->label('Sub-items')
                            ->reorderable(false)
                            ->collapsible()
                            ->collapsed()
                            ->addActionLabel('Agregar sub-item')
                            ->addAction(fn (Action $action) => $action
                                ->icon('heroicon-o-plus')
                                ->extraAttributes([
                                    'style' => 'color: var(--text-secondary, #6b7280); border: 1px dashed var(--border-strong, #d1d5db); background: transparent;',
                                ]))
                            ->itemLabel(fn (array $state, string $uuid) => static::itemLabelHtml($state, $this->contadoresRepeticion(), $uuid))
                            ->itemColor(fn (array $state) => static::categoriaDelTrabajoDesdeEstado($state)?->color)
                            ->schema($this->itemFields())
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('save')
                ->label('Guardar')
                ->action('save')
                ->visible(fn () => $this->checklist !== null),
        ];
    }

    public function save(): void
    {
        $this->form->getState();

        Notification::make()
            ->success()
            ->title('Checklist guardado')
            ->send();
    }
}
