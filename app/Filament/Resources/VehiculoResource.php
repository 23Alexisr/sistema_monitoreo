<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VehiculoResource\Pages;
use App\Models\Empleado;
use App\Models\Vehiculo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class VehiculoResource extends Resource
{
    protected static ?string $model = Vehiculo::class;

    protected static ?string $navigationIcon = 'heroicon-o-truck';

    protected static ?string $modelLabel = 'Vehículo';

    protected static ?string $pluralModelLabel = 'Vehículos';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 3;

    public const MOTIVOS = [
        'mantenimiento' => 'Mantenimiento',
        'falla' => 'Falla',
        'llantas' => 'Llantas',
        'otro' => 'Otro',
    ];

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
                Forms\Components\TextInput::make('placa')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('modelo')
                    ->helperText('Ej. "Camioneta Toyota Hilux", "Moto lineal".'),
                Forms\Components\Select::make('tipo')
                    ->options([
                        'camioneta' => 'Camioneta',
                        'moto' => 'Moto',
                        'auto' => 'Auto',
                    ])
                    ->native(false),
                Forms\Components\Select::make('estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'no_disponible' => 'No disponible',
                    ])
                    ->default('disponible')
                    ->required()
                    ->live(),
                Forms\Components\Select::make('motivo_no_disponible')
                    ->label('Motivo')
                    ->options(self::MOTIVOS)
                    ->native(false)
                    ->visible(fn (Forms\Get $get) => $get('estado') === 'no_disponible')
                    ->required(fn (Forms\Get $get) => $get('estado') === 'no_disponible')
                    ->dehydrated(fn (Forms\Get $get) => $get('estado') === 'no_disponible'),
                Forms\Components\Select::make('empleado_responsable_id')
                    ->label('Responsable')
                    ->relationship(
                        'responsable',
                        'nombre_completo',
                        modifyQueryUsing: fn (Builder $query) => $query->where('especialidad', 'conductor')->where('estado', 'activo'),
                    )
                    ->helperText('Conductor titular, responsable del vehículo y de su uso normal.')
                    ->searchable()
                    ->preload(),
                Forms\Components\Textarea::make('observaciones')
                    ->label('Observaciones')
                    ->helperText('Detalle libre del motivo, ej. "cambio de llantas programado para el viernes".')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('activo')
                    ->label('Activo')
                    ->helperText('Desactivar para dar de baja el vehículo definitivamente, sin borrar su historial.')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('placa')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('modelo')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->color('gray')
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'camioneta' => 'Camioneta',
                        'moto' => 'Moto',
                        'auto' => 'Auto',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(function (string $state, Vehiculo $record): string {
                        if ($state === 'disponible') {
                            return 'Disponible';
                        }

                        $motivo = self::MOTIVOS[$record->motivo_no_disponible] ?? null;

                        return $motivo ? "No disponible · {$motivo}" : 'No disponible';
                    })
                    ->color(fn (string $state) => $state === 'disponible' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('responsable.nombre_completo')
                    ->label('Responsable')
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'disponible' => 'Disponible',
                        'no_disponible' => 'No disponible',
                    ]),
                Tables\Filters\TernaryFilter::make('activo')
                    ->label('Activo')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Dados de baja'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon('heroicon-o-truck')
            ->emptyStateHeading('Aún no hay vehículos registrados')
            ->emptyStateDescription('Registra el primer vehículo para poder asignarlo a la programación.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar vehículo'),
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
            'index' => Pages\ListVehiculos::route('/'),
            'create' => Pages\CreateVehiculo::route('/create'),
            'edit' => Pages\EditVehiculo::route('/{record}/edit'),
        ];
    }
}
