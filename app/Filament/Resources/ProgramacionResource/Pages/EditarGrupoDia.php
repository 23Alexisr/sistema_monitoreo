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
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class EditarGrupoDia extends Page
{
    use HasProgramacionFormHelpers;

    protected static string $resource = ProgramacionResource::class;

    protected static string $view = 'filament.resources.programacion-resource.pages.editar-grupo-dia';

    protected static ?string $title = 'Editar personal de la obra';

    public ?array $data = [];

    public Obra $obraRecord;

    public string $fecha;

    public function mount(int $obra, string $fecha): void
    {
        abort_if(auth()->user()?->hasRole('operario'), 403);

        $this->obraRecord = Obra::findOrFail($obra);
        $this->fecha = $fecha;

        $registros = Programacion::query()
            ->where('obra_id', $obra)
            ->whereDate('fecha', $fecha)
            ->get();

        abort_if($registros->isEmpty(), 404);

        $primero = $registros->first();
        $encargado = $registros->firstWhere('es_encargado', true);
        $conductor = $registros->firstWhere('es_conductor', true);

        $this->form->fill([
            'hora' => $primero->hora?->format('H:i'),
            'tipo' => $primero->tipo,
            'comentario' => $primero->comentario,
            'vehiculo_id' => $primero->vehiculo_id,
            'empleado_ids' => $registros->pluck('empleado_id')->toArray(),
            'encargado_id' => $encargado?->empleado_id,
            'conductor_id' => $conductor?->empleado_id,
        ]);
    }

    public function getTitle(): string
    {
        return 'Editar personal · '.$this->obraRecord->nombre;
    }

    public function form(Form $form): Form
    {
        return $form
            ->statePath('data')
            ->schema([
                Forms\Components\Grid::make(['default' => 1, 'lg' => 2])
                    ->schema([
                        // Columna izquierda
                        Forms\Components\Group::make([
                            static::seccionLabel('Dónde y cuándo'),
                            Forms\Components\Placeholder::make('obra_display')
                                ->hiddenLabel()
                                ->content(new HtmlString(
                                    '<div style="display:flex;align-items:center;gap:8px;font-size:14px;font-weight:600;color:var(--text-secondary,#374151);">'
                                    .'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:18px;height:18px;flex-shrink:0;"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" /></svg>'
                                    .e($this->obraRecord->nombre)
                                    .' · '.e(Carbon::parse($this->fecha)->locale('es')->translatedFormat('d \d\e F, Y'))
                                    .'</div>'
                                ))
                                ->columnSpanFull(),
                            Forms\Components\TimePicker::make('hora')
                                ->label('Hora')
                                ->prefixIcon('heroicon-o-clock')
                                ->native(false)
                                ->seconds(false)
                                ->displayFormat('h:i A')
                                ->format('H:i'),
                            Forms\Components\Textarea::make('comentario')
                                ->label('Comentario del día (opcional)')
                                ->placeholder('Ej. no olvidar llevar la escalera, cliente pidió llegar después de las 8am...')
                                ->rows(2)
                                ->columnSpanFull(),

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

                            static::seccionLabel('Vehículo'),
                            Forms\Components\Select::make('vehiculo_id')
                                ->label('Vehículo (opcional)')
                                ->prefixIcon('heroicon-o-truck')
                                ->options(fn (Get $get) => Vehiculo::query()
                                    ->where('activo', true)
                                    ->where(fn ($q) => $q->where('estado', 'disponible')->when($get('vehiculo_id'), fn ($q2, $vId) => $q2->orWhere('id', $vId)))
                                    ->orderBy('placa')
                                    ->get()
                                    ->mapWithKeys(fn (Vehiculo $vehiculo) => [
                                        $vehiculo->id => $vehiculo->modelo ? "{$vehiculo->placa} · {$vehiculo->modelo}" : $vehiculo->placa,
                                    ]))
                                ->searchable()
                                ->live()
                                ->helperText('Solo se muestran vehículos disponibles y activos (más el ya asignado, si lo hubiera).')
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
                                ->label('Conductor sugerido')
                                ->prefixIcon('heroicon-o-user')
                                ->options(fn (Get $get) => Empleado::query()
                                    ->where('especialidad', 'conductor')
                                    ->where(fn ($q) => $q->where('estado', 'activo')->when($get('conductor_id'), fn ($q2, $cId) => $q2->orWhere('id', $cId)))
                                    ->orderBy('nombre_completo')
                                    ->pluck('nombre_completo', 'id'))
                                ->searchable()
                                ->live()
                                ->visible(fn (Get $get) => filled($get('vehiculo_id')))
                                ->helperText('Se sugiere el responsable titular del vehículo. Filtrado a personal con especialidad de conductor.')
                                ->afterStateUpdated(function (Get $get, Set $set, ?string $state) {
                                    if (! $state) {
                                        return;
                                    }

                                    $empleadoIds = $get('empleado_ids') ?? [];

                                    if (! in_array((int) $state, $empleadoIds, true)) {
                                        $set('empleado_ids', [...$empleadoIds, (int) $state]);
                                    }
                                })
                                ->extraFieldWrapperAttributes([
                                    'style' => 'border-radius:10px; background:var(--surface-1,#f9fafb); border:1px solid var(--border-strong,#e5e7eb); padding:10px 12px;',
                                ]),
                        ]),

                        // Columna derecha
                        Forms\Components\Group::make([
                            static::seccionLabel('Personal'),
                            Forms\Components\CheckboxList::make('empleado_ids')
                                ->hiddenLabel()
                                ->options(function (Get $get) {
                                    $conductorId = $get('conductor_id');

                                    return static::empleadosActivos()
                                        ->mapWithKeys(function (Empleado $empleado) use ($conductorId) {
                                            $badge = ($conductorId && (int) $empleado->id === (int) $conductorId)
                                                ? '<span style="display:inline-block;margin-left:6px;border-radius:999px;background:#DBEAFE;color:#1E40AF;padding:1px 7px;font-size:10px;font-weight:700;">Conductor</span>'
                                                : '';

                                            return [
                                                $empleado->id => '<div style="display:flex;align-items:center;gap:8px;">'
                                                    .'<img src="'.e($empleado->avatarUrl()).'" alt="" style="width:26px;height:26px;border-radius:999px;object-fit:cover;flex-shrink:0;" />'
                                                    .'<span>'.e($empleado->nombre_completo).'</span>'
                                                    .$badge
                                                    .'</div>',
                                            ];
                                        });
                                })
                                ->descriptions(fn () => static::empleadosActivos()
                                    ->mapWithKeys(fn (Empleado $empleado) => [
                                        $empleado->id => static::especialidadLabels()[$empleado->especialidad] ?? ($empleado->especialidad ?? ''),
                                    ]))
                                ->allowHtml()
                                ->searchable()
                                ->bulkToggleable()
                                ->columns(1)
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
                                ->hiddenLabel()
                                ->content(fn (Get $get) => $this->conflictosParaMostrar($get('empleado_ids'), $this->fecha, $this->obraRecord->id))
                                ->visible(fn (Get $get) => filled($get('empleado_ids')))
                                ->columnSpanFull(),
                            Forms\Components\Placeholder::make('advertencia_conductores')
                                ->hiddenLabel()
                                ->content(fn (Get $get) => $this->advertenciaConductores($get('empleado_ids'), $get('conductor_id')))
                                ->visible(fn (Get $get) => filled($get('empleado_ids')))
                                ->columnSpanFull(),

                            static::seccionLabel('A cargo del día'),
                            AvatarToggleButtons::make('encargado_id')
                                ->hiddenLabel()
                                ->helperText('Limitado al personal ya marcado arriba. Si no se marca a nadie, la obra queda sin encargado para esta fecha.')
                                ->options(fn (Get $get) => Empleado::query()
                                    ->whereIn('id', $get('empleado_ids') ?? [])
                                    ->get()
                                    ->mapWithKeys(fn (Empleado $empleado) => [$empleado->id => static::nombreCorto($empleado->nombre_completo)]))
                                ->avatars(fn (Get $get) => Empleado::query()
                                    ->whereIn('id', $get('empleado_ids') ?? [])
                                    ->get()
                                    ->mapWithKeys(fn (Empleado $empleado) => [$empleado->id => $empleado->avatarUrl()])
                                    ->toArray())
                                ->inline(),
                        ]),
                    ]),
            ]);
    }

    public function guardar(): void
    {
        $data = $this->form->getState();

        $empleadoIds = $data['empleado_ids'];
        $encargadoId = $data['encargado_id'] ?? null;
        $conductorId = $data['conductor_id'] ?? null;

        $obraId = $this->obraRecord->id;
        $fecha = $this->fecha;

        DB::transaction(function () use ($data, $empleadoIds, $encargadoId, $conductorId, $obraId, $fecha) {
            $existentes = Programacion::query()
                ->where('obra_id', $obraId)
                ->whereDate('fecha', $fecha)
                ->get()
                ->keyBy('empleado_id');

            foreach ($empleadoIds as $empleadoId) {
                $atributos = [
                    'hora' => $data['hora'] ?? null,
                    'tipo' => $data['tipo'],
                    'comentario' => $data['comentario'] ?? null,
                    'vehiculo_id' => $data['vehiculo_id'] ?? null,
                    'es_encargado' => $encargadoId && ((int) $encargadoId === (int) $empleadoId),
                    'es_conductor' => $conductorId && ((int) $conductorId === (int) $empleadoId),
                ];

                if ($existentes->has($empleadoId)) {
                    $existentes[$empleadoId]->update($atributos);
                } else {
                    Programacion::create([
                        'obra_id' => $obraId,
                        'empleado_id' => $empleadoId,
                        'fecha' => $fecha,
                        ...$atributos,
                    ]);
                }
            }

            $existentes->whereNotIn('empleado_id', $empleadoIds)->each->delete();
        });

        Notification::make()
            ->success()
            ->title('Personal actualizado')
            ->send();

        if ($mensaje = Programacion::advertenciaSinEncargado($obraId, $fecha)) {
            Notification::make()
                ->warning()
                ->title('Posible vacío de liderazgo')
                ->body($mensaje)
                ->send();
        }

        $this->redirect(ProgramacionResource::getUrl('detalle', ['obra' => $obraId, 'fecha' => $fecha]));
    }
}
