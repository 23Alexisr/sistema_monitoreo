<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrabajoMaestroResource\Pages;
use App\Models\TrabajoMaestro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TrabajoMaestroResource extends Resource
{
    protected static ?string $model = TrabajoMaestro::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $navigationLabel = 'Catálogo de Trabajos';

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
                Forms\Components\TextInput::make('categoria')
                    ->required()
                    ->datalist([
                        'Estructura',
                        'Eléctrico',
                        'Gráfica',
                        'Limpieza',
                    ]),
                Forms\Components\TextInput::make('codigo')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente específico (opcional, dejar vacío si aplica a todos)')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('descripcion')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('dias_estimados')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->required(),
                Forms\Components\Toggle::make('requiere_foto'),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('categoria')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('descripcion')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('dias_estimados')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('requiere_foto')
                    ->boolean(),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria')
                    ->options(fn () => TrabajoMaestro::query()->distinct()->pluck('categoria', 'categoria')->toArray()),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
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
            'index' => Pages\ListTrabajoMaestros::route('/'),
            'create' => Pages\CreateTrabajoMaestro::route('/create'),
            'edit' => Pages\EditTrabajoMaestro::route('/{record}/edit'),
        ];
    }
}
