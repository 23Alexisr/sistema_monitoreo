<?php

namespace App\Filament\Resources\ObraResource\RelationManagers;

use App\Models\Programacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ProgramacionesRelationManager extends RelationManager
{
    protected static string $relationship = 'programaciones';

    protected static ?string $title = 'Personal asignado';

    protected static ?string $modelLabel = 'asignación';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
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

                            if (! $value || ! $fecha) {
                                return;
                            }

                            if (Programacion::empleadoTieneOtraObraEseDia($value, $fecha, $this->getOwnerRecord()->getKey(), $record?->id)) {
                                $fail('Este empleado ya está programado en otra obra ese día.');
                            }
                        };
                    }),
                Forms\Components\DatePicker::make('fecha')
                    ->required()
                    ->live(),
                Forms\Components\TimePicker::make('hora')
                    ->native(false)
                    ->seconds(false)
                    ->displayFormat('h:i A')
                    ->format('H:i'),
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

                            $fecha = $get('fecha');

                            if (! $fecha) {
                                return;
                            }

                            if (Programacion::obraTieneEncargado($this->getOwnerRecord()->getKey(), $fecha, $record?->id)) {
                                $fail('Ya hay un encargado asignado para esta obra en esta fecha.');
                            }
                        };
                    }),
                Forms\Components\Select::make('vehiculo_id')
                    ->label('Vehículo')
                    ->relationship('vehiculo', 'placa')
                    ->getOptionLabelFromRecordUsing(fn (\App\Models\Vehiculo $record) => $record->modelo ? "{$record->placa} · {$record->modelo}" : $record->placa)
                    ->searchable()
                    ->preload(),
                Forms\Components\Toggle::make('es_conductor')
                    ->label('Conductor del vehículo ese día'),
                Forms\Components\TextInput::make('orden')
                    ->label('Orden de parada')
                    ->helperText('Secuencia del itinerario del día para este empleado (1ra parada, 2da parada, etc).')
                    ->numeric()
                    ->integer()
                    ->minValue(1),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('empleado.nombre_completo')
                    ->label('Personal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('hora')
                    ->time('h:i A'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge(),
                Tables\Columns\IconColumn::make('es_encargado')
                    ->label('Encargado')
                    ->boolean(),
                Tables\Columns\TextColumn::make('vehiculo.placa')
                    ->label('Vehículo')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('es_conductor')
                    ->label('Conductor')
                    ->boolean(),
                Tables\Columns\TextColumn::make('orden')
                    ->label('Orden')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'trabajo' => 'Trabajo',
                        'viaje' => 'Viaje',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->after(fn ($record) => $this->avisarSiSinEncargado($record)),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->after(fn ($record) => $this->avisarSiSinEncargado($record)),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected function avisarSiSinEncargado(Programacion $record): void
    {
        $mensaje = Programacion::advertenciaSinEncargado($record->obra_id, $record->fecha->toDateString());

        if (! $mensaje) {
            return;
        }

        Notification::make()
            ->warning()
            ->title('Posible vacío de liderazgo')
            ->body($mensaje)
            ->send();
    }
}
