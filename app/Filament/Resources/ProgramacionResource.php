<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramacionResource\Pages;
use App\Models\Programacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;

class ProgramacionResource extends Resource
{
    protected static ?string $model = Programacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Programación de Personal';

    protected static ?string $modelLabel = 'Programación';

    protected static ?string $pluralModelLabel = 'Programaciones';

    public static function shouldRegisterNavigation(): bool
    {
        return ! auth()->user()?->hasRole('operario');
    }

    public static function canViewAny(): bool
    {
        return ! auth()->user()?->hasRole('operario');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('obra_id')
                    ->label('Obra')
                    ->relationship('obra', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('empleado_id')
                    ->label('Personal')
                    ->relationship(
                        'empleado',
                        'nombre_completo',
                        modifyQueryUsing: fn (Builder $query) => $query->where('estado', 'activo'),
                    )
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live()
                    ->rule(function (Forms\Get $get, ?Programacion $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $fecha = $get('fecha');
                            $obraId = $get('obra_id');

                            if (! $value || ! $fecha) {
                                return;
                            }

                            if (Programacion::empleadoTieneOtraObraEseDia($value, $fecha, $obraId, $record?->id)) {
                                $fail('Este empleado ya está programado en otra obra ese día.');
                            }
                        };
                    }),
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
                Forms\Components\Toggle::make('es_encargado')
                    ->label('Encargado del día')
                    ->live()
                    ->rule(function (Forms\Get $get, ?Programacion $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            if (! $value) {
                                return;
                            }

                            $obraId = $get('obra_id');
                            $fecha = $get('fecha');

                            if (! $obraId || ! $fecha) {
                                return;
                            }

                            if (Programacion::obraTieneEncargado($obraId, $fecha, $record?->id)) {
                                $fail('Ya hay un encargado asignado para esta obra en esta fecha.');
                            }
                        };
                    }),
                Forms\Components\TextInput::make('unidad')
                    ->label('Unidad / vehículo'),
                Forms\Components\TextInput::make('placa'),
                Forms\Components\TextInput::make('orden')
                    ->label('Orden de parada')
                    ->helperText('Secuencia del itinerario del día para este empleado (1ra parada, 2da parada, etc).')
                    ->numeric()
                    ->integer()
                    ->minValue(1),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProgramacions::route('/'),
            'create' => Pages\CreateProgramacion::route('/create'),
            'edit' => Pages\EditProgramacion::route('/{record}/edit'),
        ];
    }
}
