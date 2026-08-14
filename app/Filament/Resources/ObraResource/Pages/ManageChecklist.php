<?php

namespace App\Filament\Resources\ObraResource\Pages;

use App\Filament\Resources\ObraResource;
use App\Models\Checklist;
use App\Models\TrabajoMaestro;
use Filament\Actions;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;

class ManageChecklist extends Page
{
    use InteractsWithRecord;

    protected static string $resource = ObraResource::class;

    protected static string $view = 'filament.resources.obra-resource.pages.manage-checklist';

    protected static ?string $title = 'Checklist';

    protected static ?string $navigationLabel = 'Checklist';

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
    }

    protected function authorizeAccess(): void
    {
        abort_unless(static::getResource()::canEdit($this->record), 403);
    }

    public static function catalogoOptions(?int $clienteId): array
    {
        return TrabajoMaestro::query()
            ->where('activo', true)
            ->where(fn ($q) => $q->whereNull('cliente_id')->when($clienteId, fn ($q) => $q->orWhere('cliente_id', $clienteId)))
            ->with(['categoria', 'subcategoria.categoria'])
            ->get()
            ->sortBy(fn ($item) => [$item->categoriaEfectiva()?->orden ?? PHP_INT_MAX, $item->descripcion])
            ->groupBy(fn ($item) => $item->categoriaEfectiva()?->nombre ?? 'Sin categoría')
            ->map(fn ($grupo) => $grupo->pluck('descripcion', 'id'))
            ->toArray();
    }

    protected static function faltanFotos(Forms\Get $get): bool
    {
        return (bool) $get('requiere_foto') && (blank($get('fotosAntes')) || blank($get('fotosDespues')));
    }

    protected static function itemLabel(array $state): ?string
    {
        $check = ($state['completado'] ?? false) ? '✅' : '⭕';
        $totalFotos = count($state['fotosAntes'] ?? []) + count($state['fotosDespues'] ?? []);
        $camara = $totalFotos > 0 ? " 📷{$totalFotos}" : '';

        return trim($check.' '.($state['descripcion'] ?? 'Nuevo item').$camara);
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
                ->helperText('Deja en blanco para un item manual.')
                ->options(fn () => static::catalogoOptions($this->record->cliente_id))
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! $state) {
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
                ->columnSpan(2),
            Forms\Components\TextInput::make('orden')
                ->label('Orden')
                ->numeric()
                ->integer()
                ->minValue(1)
                ->required()
                ->live(onBlur: true)
                ->default(fn (Forms\Get $get) => static::ordenSugerido($get))
                ->hint(fn (Forms\Get $get, $state) => static::ordenAdvertencia($get, filled($state) ? (int) $state : null))
                ->hintColor('warning')
                ->hintIcon(fn (Forms\Get $get, $state) => static::ordenAdvertencia($get, filled($state) ? (int) $state : null) ? 'heroicon-o-exclamation-triangle' : null),
            Forms\Components\TextInput::make('dias_estimados_override')
                ->label('Días estimados')
                ->numeric()
                ->step(0.01)
                ->minValue(0.01)
                ->required(),
            Forms\Components\Toggle::make('requiere_foto')
                ->label('Requiere foto')
                ->live(),
            Forms\Components\Toggle::make('completado')
                ->disabled(fn (Forms\Get $get) => static::faltanFotos($get))
                ->helperText(fn (Forms\Get $get) => static::faltanFotos($get)
                    ? 'Falta foto de antes/después para validar este trabajo.'
                    : null),
            Forms\Components\Repeater::make('fotosAntes')
                ->relationship('fotos', modifyQueryUsing: fn ($query) => $query->where('momento', 'antes'))
                ->label('Foto antes')
                ->schema([
                    Forms\Components\FileUpload::make('url')
                        ->label('Foto')
                        ->image()
                        ->disk('public')
                        ->directory('checklist-fotos')
                        ->required(),
                ])
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => [
                    ...$data,
                    'momento' => 'antes',
                    'subido_por' => auth()->id(),
                    'fecha_subida' => now(),
                ])
                ->addActionLabel('Agregar foto de antes')
                ->live()
                ->collapsible()
                ->columnSpan(1),
            Forms\Components\Repeater::make('fotosDespues')
                ->relationship('fotos', modifyQueryUsing: fn ($query) => $query->where('momento', 'despues'))
                ->label('Foto después')
                ->schema([
                    Forms\Components\FileUpload::make('url')
                        ->label('Foto')
                        ->image()
                        ->disk('public')
                        ->directory('checklist-fotos')
                        ->required(),
                ])
                ->mutateRelationshipDataBeforeCreateUsing(fn (array $data) => [
                    ...$data,
                    'momento' => 'despues',
                    'subido_por' => auth()->id(),
                    'fecha_subida' => now(),
                ])
                ->addActionLabel('Agregar foto de después')
                ->live()
                ->collapsible()
                ->columnSpan(1),
            Forms\Components\Textarea::make('observaciones')
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
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->label('Items del checklist')
                    ->reorderable(false)
                    ->addActionLabel('Agregar item')
                    ->itemLabel(fn (array $state): ?string => static::itemLabel($state))
                    ->schema([
                        ...$this->itemFields(),
                        Forms\Components\Repeater::make('children')
                            ->relationship('children')
                            ->label('Sub-items')
                            ->reorderable(false)
                            ->addActionLabel('Agregar sub-item')
                            ->itemLabel(fn (array $state): ?string => static::itemLabel($state))
                            ->schema($this->itemFields())
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->columns(2)
                    ->collapsible()
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
