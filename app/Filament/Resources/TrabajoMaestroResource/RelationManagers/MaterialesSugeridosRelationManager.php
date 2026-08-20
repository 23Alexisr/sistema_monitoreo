<?php

namespace App\Filament\Resources\TrabajoMaestroResource\RelationManagers;

use App\Models\Material;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialesSugeridosRelationManager extends RelationManager
{
    protected static string $relationship = 'sugerenciasMaterial';

    protected static ?string $title = 'Materiales sugeridos';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('material_id')
                    ->label('Material')
                    ->relationship('material', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('cantidad_sugerida')
                    ->label('Cantidad sugerida')
                    ->numeric()
                    ->step(0.01)
                    ->minValue(0.01)
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('material.nombre')
            ->columns([
                Tables\Columns\TextColumn::make('material.nombre')
                    ->label('Material'),
                Tables\Columns\TextColumn::make('material.categoria')
                    ->label('Categoría')
                    ->badge(),
                Tables\Columns\TextColumn::make('cantidad_sugerida')
                    ->label('Cantidad')
                    ->formatStateUsing(fn ($record) => $record->cantidad_sugerida.' '.$record->material?->unidad_medida),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
}
