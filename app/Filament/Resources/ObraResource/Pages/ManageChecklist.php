<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Resources\ObraResource;
use App\Models\Checklist;
use App\Models\TrabajoMaestro;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Components\Section;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Illuminate\Support\HtmlString;

class ManageChecklist extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.manage-checklist';

    protected static ?string $title = 'Armar checklist';

    protected static ?string $navigationLabel = 'Armar checklist';

    public ?array $data = [];

    public ?Checklist $checklist = null;

    public function mount(int | string $record): void
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
        return auth()->user()?->hasAnyRole(['administrador', 'jefe_cuadrilla']) ?? false;
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::canAccess(['record' => $this->record]), 403);
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

    protected static function itemLabel(array $state): ?string
    {
        return $state['descripcion'] ?? 'Nuevo item';
    }

    protected static function categoriaDelTrabajo(Forms\Get $get): ?\App\Models\CategoriaTrabajo
    {
        $trabajoId = $get('trabajo_maestro_id');

        if (! $trabajoId) {
            return null;
        }

        return TrabajoMaestro::find($trabajoId)?->categoriaEfectiva();
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

    protected static function encabezadoDescripcion(Forms\Get $get): ?string
    {
        $categoria = static::categoriaDelTrabajo($get);
        $dias = $get('dias_estimados_override');

        $partes = array_filter([
            $categoria?->nombre,
            filled($dias) ? "{$dias} días" : null,
        ]);

        return $partes ? implode(' · ', $partes) : null;
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
        return \App\Support\OrdenValidator::sugerido(static::ordenMaximoHermanos($get));
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

        return \App\Support\OrdenValidator::advertencia($orden, $maxOtros, $duplicado['descripcion'] ?? null);
    }

    protected function itemFields(): array
    {
        return [
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
                ->columnSpan(2),
            Forms\Components\TextInput::make('dias_estimados_override')
                ->label('Días estimados')
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->required()
                ->live(onBlur: true),
            Forms\Components\Toggle::make('requiere_foto')
                ->label('Requiere foto')
                ->visible(fn (Forms\Get $get) => blank($get('trabajo_maestro_id'))),
            Forms\Components\Placeholder::make('requiere_foto_info')
                ->label('Requiere foto')
                ->content(fn (Forms\Get $get) => $get('requiere_foto')
                    ? 'Este trabajo requiere foto de antes y después para validarse en campo.'
                    : 'Este trabajo no requiere evidencia fotográfica.')
                ->visible(fn (Forms\Get $get) => filled($get('trabajo_maestro_id'))),
            Forms\Components\Toggle::make('editar_orden_manual')
                ->label('Reordenar manualmente')
                ->live()
                ->dehydrated(false)
                ->default(false)
                ->columnSpanFull(),
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
                ->hintIcon(fn (Forms\Get $get, $state) => static::ordenAdvertencia($get, filled($state) ? (int) $state : null) ? 'heroicon-o-exclamation-triangle' : null),
            Forms\Components\Textarea::make('observaciones')
                ->columnSpanFull(),
        ];
    }

    protected function itemSection(): array
    {
        return [
            Section::make()
                ->heading(fn (Forms\Get $get) => $get('descripcion') ?: 'Nuevo item')
                ->description(fn (Forms\Get $get) => static::encabezadoDescripcion($get))
                ->icon(fn (Forms\Get $get) => new HtmlString(
                    '<span style="display:inline-block;width:12px;height:12px;border-radius:999px;background:'
                    .e(static::categoriaDelTrabajo($get)?->color ?? '#9CA3AF')
                    .';"></span>'
                ))
                ->collapsible()
                ->columns(2)
                ->schema($this->itemFields()),
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
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->label('Items del checklist')
                    ->reorderable(false)
                    ->addActionLabel('Agregar item')
                    ->itemLabel(fn (array $state): ?string => static::itemLabel($state))
                    ->schema([
                        ...$this->itemSection(),
                        Forms\Components\Repeater::make('children')
                            ->relationship('children')
                            ->label('Sub-items')
                            ->reorderable(false)
                            ->addActionLabel('Agregar sub-item')
                            ->itemLabel(fn (array $state): ?string => static::itemLabel($state))
                            ->schema($this->itemSection())
                            ->extraAttributes([
                                'style' => 'border-left: 3px solid #FDE68A; padding-left: 14px; margin-left: 4px; background: #FFFBEB; border-radius: 8px;',
                            ])
                            ->columnSpanFull()
                            ->collapsible(),
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
