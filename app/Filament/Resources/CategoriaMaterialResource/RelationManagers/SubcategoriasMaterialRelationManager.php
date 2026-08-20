<?php

namespace App\Filament\Resources\CategoriaMaterialResource\RelationManagers;

use App\Models\SubcategoriaMaterial;
use App\Support\OrdenValidator;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class SubcategoriasMaterialRelationManager extends RelationManager
{
    protected static string $relationship = 'subcategorias';

    protected static ?string $title = 'Subcategorías';

    protected static ?string $modelLabel = 'subcategoría';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nombre')
                    ->required(),
                Forms\Components\TextInput::make('orden')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->required()
                    ->live(onBlur: true)
                    ->default(fn () => OrdenValidator::sugerido(
                        SubcategoriaMaterial::where('categoria_id', $this->getOwnerRecord()->getKey())->max('orden')
                    ))
                    ->hint(fn (?SubcategoriaMaterial $record, $state) => $this->ordenAdvertencia($record, $state))
                    ->hintColor('warning')
                    ->hintIcon(fn (?SubcategoriaMaterial $record, $state) => $this->ordenAdvertencia($record, $state) ? 'heroicon-o-exclamation-triangle' : null),
            ]);
    }

    protected function ordenAdvertencia(?SubcategoriaMaterial $record, $state): ?string
    {
        if (blank($state) || (int) $state < 1) {
            return null;
        }

        $orden = (int) $state;

        $query = SubcategoriaMaterial::query()->where('categoria_id', $this->getOwnerRecord()->getKey());

        if ($record) {
            $query->whereKeyNot($record->getKey());
        }

        $duplicado = (clone $query)->where('orden', $orden)->first();
        $maxOtros = (clone $query)->max('orden');

        return OrdenValidator::advertencia($orden, $maxOtros, $duplicado?->nombre);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nombre')
            ->defaultSort('orden')
            ->heading('Subcategorías')
            ->description('Los materiales en sí se crean y editan desde "Materiales". Acá solo se organizan las subcategorías de esta categoría.')
            ->columns([
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('orden')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('materiales_count')
                    ->label('Materiales')
                    ->counts('materiales')
                    ->badge(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar subcategoría'),
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
            ->emptyStateHeading('Aún no hay subcategorías')
            ->emptyStateDescription('Crea subcategorías para agrupar materiales dentro de esta categoría.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar subcategoría'),
            ]);
    }
}
