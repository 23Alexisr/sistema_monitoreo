<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MaterialResource\Pages;
use App\Models\CategoriaMaterial;
use App\Models\Material;
use App\Models\SubcategoriaMaterial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class MaterialResource extends Resource
{
    protected static ?string $model = Material::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    protected static ?string $navigationLabel = 'Materiales';

    protected static ?string $navigationGroup = 'Catálogo';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Material';

    protected static ?string $pluralModelLabel = 'Materiales';

    public const ESPECIALIDADES = [
        'electricista' => 'Electricista',
        'conductor' => 'Conductor',
        'instalador' => 'Instalador',
        'pintor' => 'Pintor',
        'vinilero' => 'Vinilero',
        'auxiliar' => 'Auxiliar',
    ];

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
                Forms\Components\Select::make('categoria_id')
                    ->label('Categoría')
                    ->options(fn () => CategoriaMaterial::query()->orderBy('orden')->pluck('nombre', 'id')->toArray())
                    ->searchable()
                    ->preload()
                    ->live()
                    ->required(fn (Forms\Get $get) => blank($get('subcategoria_id')))
                    ->dehydrateStateUsing(fn ($state, Forms\Get $get) => filled($get('subcategoria_id')) ? null : $state)
                    ->afterStateHydrated(function (Forms\Components\Select $component, ?Material $record) {
                        if ($record && blank($component->getState()) && $record->subcategoria_id) {
                            $component->state($record->subcategoria?->categoria_id);
                        }
                    })
                    ->afterStateUpdated(fn (Forms\Set $set) => $set('subcategoria_id', null)),
                Forms\Components\Select::make('subcategoria_id')
                    ->label('Subcategoría (opcional)')
                    ->helperText('Deja en blanco si el material cuelga directo de la categoría, sin nivel intermedio.')
                    ->options(fn (Forms\Get $get) => SubcategoriaMaterial::query()
                        ->where('categoria_id', $get('categoria_id'))
                        ->orderBy('orden')
                        ->pluck('nombre', 'id')
                        ->toArray())
                    ->searchable()
                    ->live()
                    ->disabled(fn (Forms\Get $get) => blank($get('categoria_id'))),
                Forms\Components\Select::make('cliente_id')
                    ->label('Cliente específico (opcional, dejar vacío si aplica a todos)')
                    ->relationship('cliente', 'nombre')
                    ->searchable()
                    ->preload(),
                Forms\Components\TextInput::make('codigo')
                    ->label('Código')
                    ->disabled()
                    ->dehydrated(false)
                    ->placeholder('Se generará automáticamente'),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('descripcion')
                    ->label('Descripción / especificación técnica')
                    ->helperText('Ej: "Cable THW 2.5mm, color negro"')
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('unidad_medida')
                    ->label('Unidad de medida')
                    ->helperText('Ej: metro, unidad, caja')
                    ->required(),
                Forms\Components\FileUpload::make('foto')
                    ->image()
                    ->disk('public')
                    ->directory('materiales'),
                Forms\Components\Toggle::make('activo')
                    ->default(true),
                Forms\Components\CheckboxList::make('especialidades_permitidas')
                    ->label('Especialidades que pueden pedir este material')
                    ->helperText('Quien esté encargado del día puede pedir cualquier material, independientemente de esto.')
                    ->options(self::ESPECIALIDADES)
                    ->dehydrated(true)
                    ->columns(3)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('foto')
                    ->label('')
                    ->circular(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('categoria.nombre')
                    ->label('Categoría')
                    ->getStateUsing(fn (Material $record) => $record->categoriaEfectiva()?->nombre)
                    ->badge()
                    ->searchable(query: fn ($query, string $search) => $query
                        ->whereHas('categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%"))
                        ->orWhereHas('subcategoria.categoria', fn ($q) => $q->where('nombre', 'like', "%{$search}%")))
                    ->sortable(false),
                Tables\Columns\TextColumn::make('cliente.nombre')
                    ->label('Cliente')
                    ->placeholder('Universal')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('unidad_medida')
                    ->label('Unidad'),
                Tables\Columns\IconColumn::make('activo')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('categoria_id')
                    ->label('Categoría')
                    ->options(fn () => CategoriaMaterial::query()->orderBy('orden')->pluck('nombre', 'id')->toArray())
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
            ->emptyStateIcon('heroicon-o-cube')
            ->emptyStateHeading('Aún no hay materiales en el catálogo')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar primer material'),
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
            'index' => Pages\ListMateriales::route('/'),
            'create' => Pages\CreateMaterial::route('/create'),
            'edit' => Pages\EditMaterial::route('/{record}/edit'),
        ];
    }
}
