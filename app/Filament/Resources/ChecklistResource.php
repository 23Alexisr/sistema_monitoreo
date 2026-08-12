<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ChecklistResource\Pages;
use App\Models\Checklist;
use App\Models\TrabajoMaestro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ChecklistResource extends Resource
{
    protected static ?string $model = Checklist::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';

    public static function catalogoOptions(): array
    {
        return TrabajoMaestro::query()
            ->where('activo', true)
            ->orderBy('categoria')
            ->orderBy('descripcion')
            ->get()
            ->groupBy('categoria')
            ->map(fn ($grupo) => $grupo->pluck('descripcion', 'id'))
            ->toArray();
    }

    protected static function itemFields(): array
    {
        return [
            Forms\Components\Select::make('trabajo_maestro_id')
                ->label('Trabajo del catálogo')
                ->helperText('Deja en blanco para un item manual.')
                ->options(fn () => static::catalogoOptions())
                ->searchable()
                ->live()
                ->afterStateUpdated(function ($state, Forms\Set $set) {
                    if (! $state) {
                        return;
                    }

                    $maestro = TrabajoMaestro::find($state);

                    if ($maestro) {
                        $set('descripcion', $maestro->descripcion);
                        $set('dias_estimados_override', $maestro->dias_estimados);
                        $set('requiere_foto', $maestro->requiere_foto);
                    }
                })
                ->columnSpanFull(),
            Forms\Components\TextInput::make('descripcion')
                ->required()
                ->columnSpan(2),
            Forms\Components\TextInput::make('dias_estimados_override')
                ->label('Días estimados')
                ->numeric()
                ->minValue(1)
                ->required(),
            Forms\Components\Toggle::make('requiere_foto')
                ->label('Requiere foto'),
            Forms\Components\Toggle::make('completado'),
            Forms\Components\Textarea::make('observaciones')
                ->columnSpanFull(),
        ];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ot_id')
                    ->label('Orden de Trabajo')
                    ->relationship('ordenTrabajo', 'numero_ot')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\Repeater::make('items')
                    ->relationship('items')
                    ->label('Items del checklist')
                    ->orderColumn('orden')
                    ->addActionLabel('Agregar item')
                    ->schema([
                        ...static::itemFields(),
                        Forms\Components\Repeater::make('children')
                            ->relationship('children')
                            ->label('Sub-items')
                            ->orderColumn('orden')
                            ->addActionLabel('Agregar sub-item')
                            ->schema(static::itemFields())
                            ->columnSpanFull()
                            ->collapsible(),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordenTrabajo.numero_ot')
                    ->label('OT')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('ordenTrabajo.obra.nombre')
                    ->label('Obra')
                    ->searchable(),
                Tables\Columns\TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items'),
                Tables\Columns\TextColumn::make('ordenTrabajo.obra.avance_pct')
                    ->label('Avance')
                    ->suffix('%'),
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
            'index' => Pages\ListChecklists::route('/'),
            'create' => Pages\CreateChecklist::route('/create'),
            'edit' => Pages\EditChecklist::route('/{record}/edit'),
        ];
    }
}
