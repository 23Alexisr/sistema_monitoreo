<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EmpleadoResource\Pages;
use App\Models\Empleado;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class EmpleadoResource extends Resource
{
    protected static ?string $model = Empleado::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    protected static ?string $modelLabel = 'Empleado';

    protected static ?string $pluralModelLabel = 'Empleados';

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
                Forms\Components\TextInput::make('nombre_completo')
                    ->label('Nombre completo')
                    ->required(),
                Forms\Components\TextInput::make('dni')
                    ->required()
                    ->unique(ignoreRecord: true),
                Forms\Components\TextInput::make('telefono')
                    ->label('Teléfono')
                    ->tel(),
                Forms\Components\Select::make('especialidad')
                    ->options([
                        'electricista' => 'Electricista',
                        'conductor' => 'Conductor',
                        'instalador' => 'Instalador',
                        'pintor' => 'Pintor',
                        'auxiliar' => 'Auxiliar',
                    ])
                    ->native(false)
                    ->visible(fn (Forms\Get $get) => $get('rol') === 'operario' || (blank($get('email')) && blank($get('password'))))
                    ->dehydratedWhenHidden(),
                Forms\Components\Select::make('estado')
                    ->options([
                        'activo' => 'Activo',
                        'inactivo' => 'Inactivo',
                    ])
                    ->default('activo')
                    ->required(),
                Forms\Components\FileUpload::make('foto')
                    ->image()
                    ->disk('public')
                    ->directory('empleados'),
                Forms\Components\TextInput::make('email')
                    ->label('Email (cuenta de acceso al sistema, opcional)')
                    ->email()
                    ->live()
                    ->nullable()
                    ->requiredWith('password')
                    ->unique(table: 'users', column: 'email', ignorable: fn (?Empleado $record) => $record?->user)
                    ->helperText('Se completa junto con la contraseña solo si el empleado va a tener acceso al sistema.')
                    ->extraInputAttributes(['autocomplete' => 'off']),
                Forms\Components\TextInput::make('password')
                    ->label('Contraseña')
                    ->password()
                    ->revealable()
                    ->live()
                    ->nullable()
                    ->required(fn (Forms\Get $get, ?Empleado $record) => filled($get('email')) && ! $record?->user_id)
                    ->minLength(8)
                    ->helperText(fn (?Empleado $record) => $record?->user_id ? 'Dejar en blanco para mantener la contraseña actual.' : null)
                    ->extraInputAttributes(['autocomplete' => 'new-password']),
                Forms\Components\Select::make('rol')
                    ->label('Rol en el sistema')
                    ->options([
                        'administrador' => 'Administrador',
                        'supervisor' => 'Supervisor',
                        'jefe_cuadrilla' => 'Jefe de cuadrilla',
                        'operario' => 'Operario',
                    ])
                    ->nullable()
                    ->live()
                    ->visible(fn (Forms\Get $get) => filled($get('email')) || filled($get('password'))),
            ]);
    }

    public static function procesarCuentaAcceso(array $data, ?int $userIdActual = null): array
    {
        $email = $data['email'] ?? null;
        $password = $data['password'] ?? null;
        $rol = $data['rol'] ?? null;

        unset($data['email'], $data['password'], $data['rol']);

        if ($userIdActual) {
            $user = User::find($userIdActual);

            if ($user) {
                if ($email) {
                    $user->email = $email;
                }

                if ($password) {
                    $user->password = $password;
                }

                $user->save();
                $user->syncRoles($rol ? [$rol] : []);
            }

            return $data;
        }

        if ($email && $password) {
            $user = User::create([
                'email' => $email,
                'password' => $password,
            ]);

            if ($rol) {
                $user->assignRole($rol);
            }

            $data['user_id'] = $user->id;
        }

        return $data;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre_completo')
                    ->label('Nombre completo')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('dni')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefono')
                    ->label('Teléfono'),
                Tables\Columns\TextColumn::make('especialidad')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): ?string => match ($state) {
                        'electricista' => 'Electricista',
                        'conductor' => 'Conductor',
                        'instalador' => 'Instalador',
                        'pintor' => 'Pintor',
                        'auxiliar' => 'Auxiliar',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => $state === 'activo' ? 'Activo' : 'Inactivo')
                    ->color(fn (string $state) => $state === 'activo' ? 'success' : 'gray'),
                Tables\Columns\ImageColumn::make('foto')
                    ->circular(),
                Tables\Columns\TextColumn::make('user.roles.name')
                    ->label('Rol')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'administrador' => 'Administrador',
                        'supervisor' => 'Supervisor',
                        'jefe_cuadrilla' => 'Jefe de cuadrilla',
                        'operario' => 'Operario',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'administrador' => 'danger',
                        'supervisor' => 'warning',
                        'jefe_cuadrilla' => 'info',
                        'operario' => 'success',
                        default => 'gray',
                    })
                    ->placeholder('Sin cuenta'),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('estado')
                    ->label('Estado')
                    ->placeholder('Todos')
                    ->trueLabel('Activos')
                    ->falseLabel('Inactivos')
                    ->queries(
                        true: fn ($query) => $query->where('estado', 'activo'),
                        false: fn ($query) => $query->where('estado', 'inactivo'),
                        blank: fn ($query) => $query,
                    ),
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
            ->emptyStateIcon('heroicon-o-identification')
            ->emptyStateHeading('Aún no hay empleados registrados')
            ->emptyStateDescription('Registra al primer empleado para poder asignarlo a las obras.')
            ->emptyStateActions([
                Tables\Actions\CreateAction::make()
                    ->label('Agregar empleado'),
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
            'index' => Pages\ListEmpleados::route('/'),
            'create' => Pages\CreateEmpleado::route('/create'),
            'edit' => Pages\EditEmpleado::route('/{record}/edit'),
        ];
    }
}
