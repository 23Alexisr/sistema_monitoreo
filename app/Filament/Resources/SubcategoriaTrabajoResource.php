<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SubcategoriaTrabajoResource\Pages;
use App\Models\SubcategoriaTrabajo;
use App\Support\OrdenValidator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SubcategoriaTrabajoResource extends Resource
{
    protected static ?string $model = SubcategoriaTrabajo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-group';

    protected static ?string $navigationLabel = 'Subcategorías de Trabajo';

    protected static ?string $modelLabel = 'Subcategoría de trabajo';

    protected static ?string $pluralModelLabel = 'Subcategorías de trabajo';

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
                    ->relationship('categoria', 'nombre', fn ($query) => $query->orderBy('orden'))
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(),
                Forms\Components\TextInput::make('nombre')
                    ->required(),
                Forms\Components\TextInput::make('orden')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->live(onBlur: true)
                    ->default(fn (Forms\Get $get) => OrdenValidator::sugerido(
                        SubcategoriaTrabajo::where('categoria_id', $get('categoria_id'))->max('orden')
                    ))
                    ->hint(fn (Forms\Get $get, ?SubcategoriaTrabajo $record, $state) => static::ordenAdvertencia($get, $record, $state))
                    ->hintColor('warning')
                    ->hintIcon(fn (Forms\Get $get, ?SubcategoriaTrabajo $record, $state) => static::ordenAdvertencia($get, $record, $state) ? 'heroicon-o-exclamation-triangle' : null),
            ]);
    }

    protected static function ordenAdvertencia(Forms\Get $get, ?SubcategoriaTrabajo $record, $state): ?string
    {
        if (blank($state) || (int) $state < 1) {
            return null;
        }

        $orden = (int) $state;
        $categoriaId = $get('categoria_id');

        if (blank($categoriaId)) {
            return null;
        }

        $query = SubcategoriaTrabajo::query()->where('categoria_id', $categoriaId);

        if ($record) {
            $query->whereKeyNot($record->getKey());
        }

        $duplicado = (clone $query)->where('orden', $orden)->first();
        $maxOtros = (clone $query)->max('orden');

        return OrdenValidator::advertencia($orden, $maxOtros, $duplicado?->nombre);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('orden')
            ->columns([
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orden')
                    ->numeric()
                    ->sortable(),
            ])
            ->filters([
                //
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
            ->emptyStateIcon('heroicon-o-rectangle-group')
            ->emptyStateHeading('Aún no hay subcategorías creadas')
            ->emptyStateDescription('Crea subcategorías para agrupar trabajos dentro de una categoría, ej. "Pintura exteriores" dentro de "Pintura".')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar subcategoría'),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageSubcategoriaTrabajos::route('/'),
        ];
    }
}
