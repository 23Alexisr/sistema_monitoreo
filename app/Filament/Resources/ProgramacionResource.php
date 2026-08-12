<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProgramacionResource\Pages;
use App\Models\Programacion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ProgramacionResource extends Resource
{
    protected static ?string $model = Programacion::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Programación de Personal';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('obra_id')
                    ->label('Obra')
                    ->relationship('obra', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('user_id')
                    ->label('Personal')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->live(),
                Forms\Components\Select::make('rol_obra')
                    ->label('Rol en la obra')
                    ->options([
                        'pdr' => 'PDR',
                        'supervisor' => 'Supervisor',
                        'jefe_cuadrilla' => 'Jefe de cuadrilla',
                    ])
                    ->required(),
                Forms\Components\DatePicker::make('fecha_inicio')
                    ->required()
                    ->live(),
                Forms\Components\DatePicker::make('fecha_fin')
                    ->required()
                    ->afterOrEqual('fecha_inicio')
                    ->live()
                    ->rule(function (Forms\Get $get, ?Programacion $record) {
                        return function (string $attribute, $value, \Closure $fail) use ($get, $record) {
                            $userId = $get('user_id');
                            $fechaInicio = $get('fecha_inicio');

                            if (! $userId || ! $fechaInicio || ! $value) {
                                return;
                            }

                            if (Programacion::tieneSolapamiento($userId, $fechaInicio, $value, $record?->id)) {
                                $fail('Este usuario ya tiene una asignación en otra obra que se solapa con estas fechas.');
                            }
                        };
                    })
                    ->validationMessages([
                        'after_or_equal' => 'La fecha fin debe ser igual o posterior a la fecha de inicio.',
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('obra.nombre')
                    ->label('Obra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Personal')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('rol_obra')
                    ->label('Rol')
                    ->badge(),
                Tables\Columns\TextColumn::make('fecha_inicio')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('fecha_fin')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('rol_obra')
                    ->options([
                        'pdr' => 'PDR',
                        'supervisor' => 'Supervisor',
                        'jefe_cuadrilla' => 'Jefe de cuadrilla',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
