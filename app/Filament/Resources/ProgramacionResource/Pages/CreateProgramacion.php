<?php

namespace App\Filament\Resources\ProgramacionResource\Pages;

use App\Filament\Forms\Components\AvatarToggleButtons;
use App\Filament\Resources\ProgramacionResource;
use App\Filament\Resources\ProgramacionResource\Concerns\HasProgramacionFormHelpers;
use App\Models\Empleado;
use App\Models\Obra;
use App\Models\Programacion;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;

class CreateProgramacion extends Page
{
    use HasProgramacionFormHelpers;

    protected static string $resource = ProgramacionResource::class;

    protected static string $view = 'filament.resources.programacion-resource.pages.create-programacion';

    protected static ?string $title = 'Asignar personal a obra';

    public ?array $data = [];

    public function mount(): void
    {
        abort_unless(static::getResource()::canCreate(), 403);

        $fecha = request()->query('fecha');

        $this->form->fill([
            'tipo' => 'trabajo',
            'fecha' => $fecha && \Illuminate\Support\Carbon::hasFormat($fecha, 'Y-m-d') ? $fecha : null,
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                static::seccionLabel('Dónde y cuándo'),
                Forms\Components\Select::make('obra_id')
                    ->label('Obra')
                    ->prefixIcon('heroicon-o-building-office-2')
                    ->options(fn () => Obra::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Grid::make(2)
                    ->schema([
                        Forms\Components\DatePicker::make('fecha')
                            ->prefixIcon('heroicon-o-calendar')
                            ->required()
                            ->live(),
                        Forms\Components\TimePicker::make('hora')
                            ->prefixIcon('heroicon-o-clock')
                            ->native(false)
                            ->seconds(false)
                            ->displayFormat('h:i A')
                            ->format('H:i'),
                    ]),

                static::seccionLabel('Tipo de jornada'),
                Forms\Components\ToggleButtons::make('tipo')
                    ->hiddenLabel()
                    ->options([
                        'trabajo' => 'Trabajo',
                        'viaje' => 'Viaje',
                    ])
                    ->icons([
                        'trabajo' => 'heroicon-o-briefcase',
                        'viaje' => 'heroicon-o-truck',
                    ])
                    ->grouped()
                    ->default('trabajo')
                    ->required(),

                static::seccionLabel('Vehículo (opcional)'),
                Forms\Components\Select::make('vehiculo_id')
                    ->hiddenLabel()
                    ->prefixIcon('heroicon-o-truck')
                    ->options(fn () => Vehiculo::query()
                        ->where('estado', 'disponible')
                        ->where('activo', true)
                        ->orderBy('placa')
                        ->get()
                        ->mapWithKeys(fn (Vehiculo $vehiculo) => [
                            $vehiculo->id => $vehiculo->modelo ? "{$vehiculo->placa} · {$vehiculo->modelo}" : $vehiculo->placa,
                        ]))
                    ->searchable()
                    ->live()
                    ->helperText('Solo se muestran vehículos disponibles y activos.')
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        $responsableId = $state ? Vehiculo::find($state)?->empleado_responsable_id : null;

                        $set('conductor_id', $responsableId);

                        if ($responsableId) {
                            $empleadoIds = $get('empleado_ids') ?? [];

                            if (! in_array((int) $responsableId, $empleadoIds, true)) {
                                $set('empleado_ids', [...$empleadoIds, (int) $responsableId]);
                            }
                        }
                    }),
                Forms\Components\Select::make('conductor_id')
                    ->label('Conductor sugerido (opcional)')
                    ->helperText('Se sugiere el responsable titular del vehículo. Filtrado a personal con especialidad de conductor.')
                    ->options(fn (Get $get) => Empleado::query()
                        ->where('especialidad', 'conductor')
                        ->where(fn ($q) => $q->where('estado', 'activo')->when($get('conductor_id'), fn ($q2, $cId) => $q2->orWhere('id', $cId)))
                        ->orderBy('nombre_completo')
                        ->pluck('nombre_completo', 'id'))
                    ->searchable()
                    ->live()
                    ->visible(fn (Get $get) => filled($get('vehiculo_id')))
                    ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                        if (! $state) {
                            return;
                        }

                        $empleadoIds = $get('empleado_ids') ?? [];

                        if (! in_array((int) $state, $empleadoIds, true)) {
                            $set('empleado_ids', [...$empleadoIds, (int) $state]);
                        }
                    }),

                static::seccionLabel('Personal'),
                Forms\Components\CheckboxList::make('empleado_ids')
                    ->hiddenLabel()
                    ->options(fn () => static::empleadosActivos()
                        ->mapWithKeys(fn (Empleado $empleado) => [
                            $empleado->id => '<div style="display:flex;align-items:center;gap:8px;">'
                                .'<img src="'.e($empleado->avatarUrl()).'" alt="" style="width:26px;height:26px;border-radius:999px;object-fit:cover;flex-shrink:0;" />'
                                .'<span>'.e($empleado->nombre_completo).'</span>'
                                .'</div>',
                        ]))
                    ->descriptions(fn () => static::empleadosActivos()
                        ->mapWithKeys(fn (Empleado $empleado) => [
                            $empleado->id => static::especialidadLabels()[$empleado->especialidad] ?? ($empleado->especialidad ?? ''),
                        ]))
                    ->allowHtml()
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(2)
                    ->required()
                    ->minItems(1)
                    ->live()
                    ->afterStateUpdated(function (Get $get, Set $set, ?array $state) {
                        $ids = $state ?? [];

                        if ($get('encargado_id') && ! in_array((int) $get('encargado_id'), $ids, true)) {
                            $set('encargado_id', null);
                        }

                        if ($get('conductor_id') && ! in_array((int) $get('conductor_id'), $ids, true)) {
                            $set('conductor_id', null);
                        }
                    })
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('conflictos')
                    ->label('Posibles dobles asignaciones ese día')
                    ->content(fn (Get $get) => $this->conflictosParaMostrar($get('empleado_ids'), $get('fecha')))
                    ->visible(fn (Get $get) => filled($get('fecha')) && filled($get('empleado_ids')))
                    ->columnSpanFull(),

                static::seccionLabel('A cargo del día'),
                AvatarToggleButtons::make('encargado_id')
                    ->hiddenLabel()
                    ->helperText('Limitado al personal ya marcado arriba. Si no se marca a nadie, todos quedan sin encargado para esta obra y fecha.')
                    ->options(fn (Get $get) => Empleado::query()
                        ->whereIn('id', $get('empleado_ids') ?? [])
                        ->get()
                        ->mapWithKeys(fn (Empleado $empleado) => [$empleado->id => static::nombreCorto($empleado->nombre_completo)]))
                    ->avatars(fn (Get $get) => Empleado::query()
                        ->whereIn('id', $get('empleado_ids') ?? [])
                        ->get()
                        ->mapWithKeys(fn (Empleado $empleado) => [$empleado->id => $empleado->avatarUrl()])
                        ->toArray())
                    ->inline()
                    ->rule(function (Get $get) {
                        return function (string $attribute, $value, \Closure $fail) use ($get) {
                            if (! $value) {
                                return;
                            }

                            $obraId = $get('obra_id');
                            $fecha = $get('fecha');

                            if (! $obraId || ! $fecha) {
                                return;
                            }

                            if (Programacion::obraTieneEncargado($obraId, $fecha)) {
                                $fail('Ya hay un encargado asignado para esta obra en esta fecha.');
                            }
                        };
                    }),
            ]);
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $empleadoIds = $data['empleado_ids'];
        $encargadoId = $data['encargado_id'] ?? null;
        $conductorId = $data['conductor_id'] ?? null;

        DB::transaction(function () use ($data, $empleadoIds, $encargadoId, $conductorId) {
            foreach ($empleadoIds as $empleadoId) {
                Programacion::create([
                    'obra_id' => $data['obra_id'],
                    'empleado_id' => $empleadoId,
                    'fecha' => $data['fecha'],
                    'hora' => $data['hora'] ?? null,
                    'tipo' => $data['tipo'],
                    'es_encargado' => $encargadoId && ((int) $encargadoId === (int) $empleadoId),
                    'vehiculo_id' => $data['vehiculo_id'] ?? null,
                    'es_conductor' => $conductorId && ((int) $conductorId === (int) $empleadoId),
                ]);
            }
        });

        Notification::make()
            ->success()
            ->title(count($empleadoIds).' '.(count($empleadoIds) === 1 ? 'asignación creada' : 'asignaciones creadas'))
            ->send();

        if ($mensaje = Programacion::advertenciaSinEncargado($data['obra_id'], $data['fecha'])) {
            Notification::make()
                ->warning()
                ->title('Posible vacío de liderazgo')
                ->body($mensaje)
                ->send();
        }

        $this->redirect(static::getResource()::getUrl('index'));
    }
}
