<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CategoriaMaterialResource\Pages;
use App\Filament\Resources\CategoriaMaterialResource\RelationManagers;
use App\Models\CategoriaMaterial;
use App\Support\OrdenValidator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class CategoriaMaterialResource extends Resource
{
    protected static ?string $model = CategoriaMaterial::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $navigationLabel = 'Categorías de Materiales';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Categoría de material';

    protected static ?string $pluralModelLabel = 'Categorías de material';

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasRole('administrador') ?? false;
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
                    ->default(fn () => OrdenValidator::sugerido(CategoriaMaterial::max('orden')))
                    ->hint(fn (?CategoriaMaterial $record, $state) => static::ordenAdvertencia($record, $state))
                    ->hintColor('warning')
                    ->hintIcon(fn (?CategoriaMaterial $record, $state) => static::ordenAdvertencia($record, $state) ? 'heroicon-o-exclamation-triangle' : null),
            ]);
    }

    protected static function ordenAdvertencia(?CategoriaMaterial $record, $state): ?string
    {
        if (blank($state) || (int) $state < 1) {
            return null;
        }

        $orden = (int) $state;

        $query = CategoriaMaterial::query();

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
            ->emptyStateDescription('Crea categorías para organizar el catálogo de materiales.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar categoría'),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\SubcategoriasMaterialRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategoriaMaterials::route('/'),
            'create' => Pages\CreateCategoriaMaterial::route('/create'),
            'edit' => Pages\EditCategoriaMaterial::route('/{record}/edit'),
        ];
    }
}
