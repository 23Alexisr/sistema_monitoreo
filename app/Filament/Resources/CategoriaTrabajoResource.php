<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaTrabajoResource\Pages;
use App\Filament\Resources\CategoriaTrabajoResource\RelationManagers;
use App\Models\CategoriaTrabajo;
use App\Support\OrdenValidator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaTrabajoResource extends Resource
{
    protected static ?string $model = CategoriaTrabajo::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Categorías de Trabajo';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Categoría de trabajo';

    protected static ?string $pluralModelLabel = 'Categorías de trabajo';

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
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\ColorPicker::make('color'),
                Forms\Components\TextInput::make('orden')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->live(onBlur: true)
                    ->default(fn () => OrdenValidator::sugerido(CategoriaTrabajo::max('orden')))
                    ->hint(fn (?CategoriaTrabajo $record, $state) => static::ordenAdvertencia($record, $state))
                    ->hintColor('warning')
                    ->hintIcon(fn (?CategoriaTrabajo $record, $state) => static::ordenAdvertencia($record, $state) ? 'heroicon-o-exclamation-triangle' : null),
            ]);
    }

    protected static function ordenAdvertencia(?CategoriaTrabajo $record, $state): ?string
    {
        if (blank($state) || (int) $state < 1) {
            return null;
        }

        $orden = (int) $state;

        $query = CategoriaTrabajo::query();

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
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ColorColumn::make('color'),
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
            ->emptyStateIcon('heroicon-o-rectangle-stack')
            ->emptyStateHeading('Aún no hay categorías creadas')
            ->emptyStateDescription('Crea categorías para organizar el catálogo de trabajos maestros.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar categoría'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubcategoriasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoriaTrabajos::route('/'),
            'create' => Pages\CreateCategoriaTrabajo::route('/create'),
            'edit' => Pages\EditCategoriaTrabajo::route('/{record}/edit'),
        ];
    }
}
