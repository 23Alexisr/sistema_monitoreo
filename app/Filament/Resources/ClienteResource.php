<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClienteResource\Pages;
use App\Models\Cliente;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ClienteResource extends Resource
{
    protected static ?string $model = Cliente::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?string $modelLabel = 'Cliente';

    protected static ?string $pluralModelLabel = 'Clientes';

    protected static ?string $navigationGroup = 'Administración';

    protected static ?int $navigationSort = 1;

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
                Forms\Components\FileUpload::make('logo')
                    ->label('Logo')
                    ->image()
                    ->disk('public')
                    ->directory('clientes')
                    ->helperText('Opcional. SVG recomendado (también admite PNG/JPG). Si no se sube, se muestran las iniciales del cliente.'),
                Forms\Components\TextInput::make('nombre')
                    ->required()
                    ->string()
                    ->maxLength(255)
                    ->dehydrateStateUsing(fn ($state) => ucwords(mb_strtolower(preg_replace('/\s+/', ' ', trim($state)))))
                    ->extraInputAttributes([
                        'x-on:keydown' => '
                            if ($event.key === " " && ($event.target.value.slice(-1) === " " || $event.target.value.length === 0)) { 
                                $event.preventDefault(); 
                            }
                        ',
                        // ESTA LÍNEA HACE LA MAGIA EN TIEMPO REAL:
                        'x-on:input' => '
                            let val = $event.target.value.replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, "");
                            $event.target.value = val.toLowerCase().replace(/(^\w{1})|(\s+\w{1})/g, letter => letter.toUpperCase());
                        ',
                    ])
                    ->regex('/^[a-zA-ZáéíóúÁÉÍÓÚñÑ]+(\s[a-zA-ZáéíóúÁÉÍÓÚñÑ]+)*$/')
                    ->validationMessages([
                        'regex' => 'El nombre solo debe contener letras y espacios simples.',
                    ])
                    ->live(onBlur: true),
                Forms\Components\TextInput::make('ruc')
                    ->required()
                    ->length(11)
                    ->rules(['digits:11'])
                    ->unique(ignoreRecord: true),
                Forms\Components\ColorPicker::make('color_marca')
                    ->label('Color de marca')
                    ->helperText('Opcional. Se usa como color de cabecera en el dashboard de obras. Si no se define, se asigna uno automáticamente.'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('logo')
                    ->label('')
                    ->getStateUsing(fn (Cliente $record) => $record->avatarUrl())
                    ->circular(),
                Tables\Columns\TextColumn::make('nombre')
                    ->searchable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('ruc')
                    ->searchable(),
                Tables\Columns\TextColumn::make('obras_count')
                    ->label('Obras')
                    ->counts('obras')
                    ->badge(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
            ->emptyStateIcon('heroicon-o-building-office')
            ->emptyStateHeading('Aún no hay clientes registrados')
            ->emptyStateDescription('Registra tu primer cliente para empezar a asociar sus obras.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar cliente'),
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
            'index' => Pages\ListClientes::route('/'),
            'create' => Pages\CreateCliente::route('/create'),
            'edit' => Pages\EditCliente::route('/{record}/edit'),
        ];
    }
}
