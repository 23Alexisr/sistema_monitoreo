<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TrabajoMaestroResource\Pages;
use App\Models\CategoriaTrabajo;
use App\Models\SubcategoriaTrabajo;
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

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Trabajo maestro';

    protected static ?string $pluralModelLabel = 'Trabajos maestros';

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
                Forms\Components\Select::make('categoria_id')
                    ->label('Categoría')
                    ->options(fn () => CategoriaTrabajo::query()->orderBy('orden')->pluck('nombre', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(fn (Forms\Get $get) => blank($get('subcategoria_id')))
                    ->dehydrateStateUsing(fn ($state, Forms\Get $get) => filled($get('subcategoria_id')) ? null : $state)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?TrabajoMaestro $record) {
                        if ($record && blank($component->getState()) && $record->subcategoria_id) {
                            $component->state($record->subcategoria?->categoria_id);
                        }
                    })
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('subcategoria_id', null)),
                Forms\Components\Select::make('subcategoria_id')
                    ->label('Subcategoría (opcional)')
                    ->helperText('Deja en blanco si el trabajo cuelga directo de la categoría, sin nivel intermedio.')
                    ->options(fn (Forms\Get $get) => SubcategoriaTrabajo::query()
                        ->where('categoria_id', $get('categoria_id'))
                        ->orderBy('orden')
                        ->pluck('nombre', 'id')
                        ->toArray())
                    ->searchable()
                    ->live()
                    ->disabled(fn (Forms\Get $get) => blank($get('categoria_id'))),
                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente'),
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
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->getStateUsing(fn (TrabajoMaestro $record) => $record->categoriaEfectiva()?->nombre)
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHas('categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('subcategoria.categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%")))
                    ->sortable(false),
                Tables\Columns\TextColumn::make('subcategoria.nombre')
                    ->label('Subcategoría')
                    ->placeholder('—')
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
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->options(fn () => CategoriaTrabajo::query()->orderBy('orden')->pluck('nombre', 'id')->toArray())
                    ->query(function ($query, array $data) {
                        if (blank($data['value'] ?? null)) {
                            return $query;
                        }

                        return $query->where(fn ($q) => $q
                            ->where('categoria_id', $data['value'])
                            ->orWhereHas('subcategoria', fn ($q2) => $q2->where('categoria_id', $data['value'])));
                    }),
                Tables\Filters\TernaryFilter::make('activo'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->emptyStateIcon('heroicon-o-book-open')
            ->emptyStateHeading('Aún no has agregado trabajos al catálogo')
            ->emptyStateDescription('Los trabajos maestros son la base para armar los checklists de cada obra.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar primer trabajo'),
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
