<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrdenTrabajoResource\Pages;
use App\Models\OrdenTrabajo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrdenTrabajoResource extends Resource
{
    protected static ?string $model = OrdenTrabajo::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Órdenes de Trabajo';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('obra_id')
                    ->relationship('obra', 'nombre')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('numero_ot')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Textarea::make('alcance')
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('especificaciones')
                    ->columnSpanFull(),
                Forms\Components\Select::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'cerrada' => 'Cerrada',
                    ])
                    ->default('pendiente')
                    ->required(),
                Forms\Components\DatePicker::make('fecha_emision'),
                Forms\Components\FileUpload::make('archivo_excel_original')
                    ->label('Excel original (respaldo)')
                    ->helperText('Se guarda tal cual como respaldo. No se procesa automáticamente.')
                    ->disk('local')
                    ->directory('ordenes-trabajo')
                    ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                    ->columnSpanFull(),
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
                Tables\Columns\TextColumn::make('numero_ot')
                    ->searchable(),
                Tables\Columns\TextColumn::make('estado')
                    ->badge(),
                Tables\Columns\TextColumn::make('fecha_emision')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options([
                        'pendiente' => 'Pendiente',
                        'en_proceso' => 'En proceso',
                        'cerrada' => 'Cerrada',
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
            'index' => Pages\ListOrdenTrabajos::route('/'),
            'create' => Pages\CreateOrdenTrabajo::route('/create'),
            'edit' => Pages\EditOrdenTrabajo::route('/{record}/edit'),
        ];
    }
}
