<?php

namespace App\Filament\Resources\ProgramacionResource\Pages;

use App\Filament\Resources\ProgramacionResource;
use App\Models\Empleado;
use App\Models\Obra;
use App\Models\Programacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;

class CreateProgramacion extends Page
{
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
                Forms\Components\Select::make('obra_id')
                    ->label('Obra')
                    ->options(fn () => Obra::query()->orderBy('nombre')->pluck('nombre', 'id'))
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->live(),
                Forms\Components\TimePicker::make('hora'),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'trabajo' => 'Trabajo',
                        'viaje' => 'Viaje',
                    ])
                    ->default('trabajo')
                    ->required(),
                Forms\Components\TextInput::make('unidad')
                    ->label('Unidad / vehículo'),
                Forms\Components\TextInput::make('placa'),
                Forms\Components\CheckboxList::make('empleado_ids')
                    ->label('Empleados')
                    ->options(fn () => Empleado::query()
                        ->where('estado', 'activo')
                        ->orderBy('nombre_completo')
                        ->pluck('nombre_completo', 'id'))
                    ->searchable()
                    ->bulkToggleable()
                    ->columns(2)
                    ->required()
                    ->minItems(1)
                    ->live()
                    ->columnSpanFull(),
                Forms\Components\Placeholder::make('conflictos')
                    ->label('Posibles dobles asignaciones ese día')
                    ->content(fn (Get $get) => $this->conflictosParaMostrar($get('empleado_ids'), $get('fecha')))
                    ->visible(fn (Get $get) => filled($get('fecha')) && filled($get('empleado_ids')))
                    ->columnSpanFull(),
                Forms\Components\Select::make('encargado_id')
                    ->label('Encargado del día (opcional)')
                    ->helperText('Si no se marca a nadie, todos quedan sin encargado para esta obra y fecha.')
                    ->options(fn (Get $get) => Empleado::query()
                        ->whereIn('id', $get('empleado_ids') ?? [])
                        ->pluck('nombre_completo', 'id'))
                    ->searchable()
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

    protected function conflictosParaMostrar(?array $empleadoIds, ?string $fecha): HtmlString
    {
        if (empty($empleadoIds) || ! $fecha) {
            return new HtmlString('');
        }

        $lineas = [];

        foreach (Empleado::query()->whereIn('id', $empleadoIds)->get() as $empleado) {
            $registros = Programacion::otrasAsignacionesEseDia($empleado->id, $fecha);

            if ($registros->isEmpty()) {
                continue;
            }

            $detalle = $registros->map(function (Programacion $registro) {
                $partes = [$registro->obra?->nombre ?? 'obra eliminada'];

                if ($registro->hora) {
                    $partes[] = 'a las '.$registro->hora->format('H:i');
                }

                if ($registro->orden) {
                    $partes[] = '(parada '.$registro->orden.')';
                }

                return implode(' ', $partes);
            })->implode(', ');

            $lineas[] = '<strong>'.e($empleado->nombre_completo).'</strong> ya está programado en: '.e($detalle);
        }

        if (empty($lineas)) {
            return new HtmlString('');
        }

        return new HtmlString(implode('<br>', $lineas));
    }

    public function create(): void
    {
        $data = $this->form->getState();

        $empleadoIds = $data['empleado_ids'];
        $encargadoId = $data['encargado_id'] ?? null;

        DB::transaction(function () use ($data, $empleadoIds, $encargadoId) {
            foreach ($empleadoIds as $empleadoId) {
                Programacion::create([
                    'obra_id' => $data['obra_id'],
                    'empleado_id' => $empleadoId,
                    'fecha' => $data['fecha'],
                    'hora' => $data['hora'] ?? null,
                    'tipo' => $data['tipo'],
                    'es_encargado' => $encargadoId && ((int) $encargadoId === (int) $empleadoId),
                    'unidad' => $data['unidad'] ?? null,
                    'placa' => $data['placa'] ?? null,
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
