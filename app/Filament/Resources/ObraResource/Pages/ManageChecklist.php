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

    public static function catalogoOptions(): array
    {
        return TrabajoMaestro::query()
            ->where('activo', true)
            ->orderBy('categoria')
            ->orderBy('descripcion')
            ->get()
            ->groupBy('categoria')
            ->map(fn ($grupo) => $grupo->pluck('descripcion', 'id'))
            ->toArray();
    }

    protected static function itemFields(): array
    {
        return [
            Forms\Components\Select::make('trabajo_maestro_id')
                ->label('Trabajo del catálogo')
                ->helperText('Deja en blanco para un item manual.')
                ->options(fn () => static::catalogoOptions())
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
            Forms\Components\TextInput::make('dias_estimados_override')
                ->label('Días estimados')
                ->numeric()
                ->minValue(1)
                ->required(),
            Forms\Components\Toggle::make('requiere_foto')
                ->label('Requiere foto'),
            Forms\Components\Toggle::make('completado'),
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
                    ->orderColumn('orden')
                    ->addActionLabel('Agregar item')
                    ->schema([
                        ...static::itemFields(),
                        Forms\Components\Repeater::make('children')
                            ->relationship('children')
                            ->label('Sub-items')
                            ->orderColumn('orden')
                            ->addActionLabel('Agregar sub-item')
                            ->schema(static::itemFields())
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
