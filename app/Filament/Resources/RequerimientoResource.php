<?php

namespace App\Filament\Resources;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\RequerimientoResource\Pages;
use App\Filament\Resources\RequerimientoResource\RelationManagers;
use App\Models\Requerimiento;
use App\Support\PermisoRequerimiento;
use Filament\Forms;
use Filament\Infolists\Components\Actions as InfolistActions;
use Filament\Infolists\Components\Actions\Action as InfolistAction;
use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class RequerimientoResource extends Resource
{
    protected static ?string $model = Requerimiento::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationLabel = 'Requerimientos';

    protected static ?string $navigationGroup = 'Operación';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Requerimiento';

    protected static ?string $pluralModelLabel = 'Requerimientos';

    public static function rolesConAcceso(): array
    {
        return ['administrador', 'jefe_planta', 'jefe_ssoma', 'jefe_proyectos', 'almacen', 'despacho'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return ($user?->hasAnyRole(static::rolesConAcceso()) ?? false) || PermisoRequerimiento::esVinilero($user);
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return ($user?->hasAnyRole(static::rolesConAcceso()) ?? false) || PermisoRequerimiento::esVinilero($user);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): Builder
    {
        $user = auth()->user();
        $query = parent::getEloquentQuery();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        if ($user->hasRole('administrador')) {
            return $query;
        }

        if ($user->hasRole('almacen')) {
            return $query
                ->whereIn('tipo', [TipoRequerimiento::Material->value, TipoRequerimiento::Seguridad->value])
                ->whereIn('estado', [
                    EstadoRequerimiento::Aprobado->value,
                    EstadoRequerimiento::EnAlistamiento->value,
                    EstadoRequerimiento::Entregado->value,
                ]);
        }

        if ($user->hasRole('despacho')) {
            return $query
                ->where('tipo', TipoRequerimiento::Señaletica->value)
                ->whereIn('estado', [EstadoRequerimiento::EnAlistamiento->value, EstadoRequerimiento::Entregado->value]);
        }

        if (PermisoRequerimiento::esVinilero($user)) {
            return $query
                ->where('tipo', TipoRequerimiento::Señaletica->value)
                ->where('estado', EstadoRequerimiento::Aprobado->value);
        }

        foreach (TipoRequerimiento::cases() as $tipo) {
            if ($user->hasRole($tipo->rolAprobador())) {
                return $query->where('tipo', $tipo->value);
            }
        }

        return $query->whereRaw('1 = 0');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('obra.nombre')
                    ->label('Obra')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('solicitadoPor.empleado.nombre_completo')
                    ->label('Solicitado por')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->formatStateUsing(fn (TipoRequerimiento $state) => $state->label())
                    ->color(fn (TipoRequerimiento $state) => $state->color()),
                Tables\Columns\TextColumn::make('estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoRequerimiento $state) => $state->label())
                    ->color(fn (EstadoRequerimiento $state) => $state->color()),
                Tables\Columns\TextColumn::make('fecha_solicitud')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(collect(EstadoRequerimiento::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])->toArray()),
                Tables\Filters\SelectFilter::make('tipo')
                    ->options(collect(TipoRequerimiento::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray())
                    ->visible(fn () => auth()->user()?->hasRole('administrador') ?? false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('aprobar')
                    ->label('Aprobar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Requerimiento $record) => $record->puedeAprobar(auth()->user()))
                    ->action(fn (Requerimiento $record) => $record->aprobar(auth()->user())),
                Tables\Actions\Action::make('rechazar')
                    ->label('Rechazar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('Motivo del rechazo')
                            ->required(),
                    ])
                    ->visible(fn (Requerimiento $record) => $record->puedeAprobar(auth()->user()))
                    ->action(fn (Requerimiento $record, array $data) => $record->rechazar($data['motivo'])),
                Tables\Actions\Action::make('marcarEnAlistamiento')
                    ->label('Marcar en alistamiento')
                    ->icon('heroicon-o-archive-box')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Requerimiento $record) => $record->estado === EstadoRequerimiento::Aprobado && $record->puedeGestionarAlmacen(auth()->user()))
                    ->action(fn (Requerimiento $record) => $record->marcarEnAlistamiento(auth()->user())),
                Tables\Actions\Action::make('marcarEntregado')
                    ->label('Marcar entregado')
                    ->icon('heroicon-o-truck')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Requerimiento $record) => $record->estado === EstadoRequerimiento::EnAlistamiento && $record->puedeGestionarAlmacen(auth()->user()))
                    ->action(fn (Requerimiento $record) => $record->marcarEntregado(auth()->user())),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('fecha_solicitud', 'desc')
            ->emptyStateIcon('heroicon-o-clipboard-document-list')
            ->emptyStateHeading('No hay requerimientos en tu bandeja');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Section::make()
                    ->columns(2)
                    ->schema([
                        TextEntry::make('obra.nombre')->label('Obra'),
                        TextEntry::make('tipo')->badge()
                            ->formatStateUsing(fn (TipoRequerimiento $state) => $state->label())
                            ->color(fn (TipoRequerimiento $state) => $state->color()),
                        TextEntry::make('estado')->badge()
                            ->formatStateUsing(fn (EstadoRequerimiento $state) => $state->label())
                            ->color(fn (EstadoRequerimiento $state) => $state->color()),
                        TextEntry::make('solicitadoPor.empleado.nombre_completo')->label('Solicitado por'),
                        TextEntry::make('fecha_solicitud')->label('Fecha de solicitud')->dateTime('d/m/Y H:i'),
                        TextEntry::make('checklistItem.descripcion')->label('Origen (trabajo del checklist)')->placeholder('—'),
                        TextEntry::make('requerimientoOriginal.id')->label('Adicional de')->placeholder('—')
                            ->formatStateUsing(fn ($state) => $state ? "#{$state}" : null),
                        TextEntry::make('aprobadoPor.empleado.nombre_completo')->label('Aprobado por')->placeholder('—'),
                        TextEntry::make('motivo_rechazo')->label('Motivo de rechazo')->placeholder('—')->columnSpanFull()
                            ->visible(fn (Requerimiento $record) => filled($record->motivo_rechazo)),
                        TextEntry::make('alistadoPor.empleado.nombre_completo')->label('Alistado por')->placeholder('—'),
                    ]),
                InfolistActions::make([
                    InfolistAction::make('aprobar')
                        ->label('Aprobar')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Requerimiento $record) => $record->puedeAprobar(auth()->user()))
                        ->action(fn (Requerimiento $record) => $record->aprobar(auth()->user())),
                    InfolistAction::make('rechazar')
                        ->label('Rechazar')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->form([
                            Forms\Components\Textarea::make('motivo')
                                ->label('Motivo del rechazo')
                                ->required(),
                        ])
                        ->visible(fn (Requerimiento $record) => $record->puedeAprobar(auth()->user()))
                        ->action(fn (Requerimiento $record, array $data) => $record->rechazar($data['motivo'])),
                    InfolistAction::make('marcarEnAlistamiento')
                        ->label('Marcar en alistamiento')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->visible(fn (Requerimiento $record) => $record->estado === EstadoRequerimiento::Aprobado && $record->puedeGestionarAlmacen(auth()->user()))
                        ->action(fn (Requerimiento $record) => $record->marcarEnAlistamiento(auth()->user())),
                    InfolistAction::make('marcarEntregado')
                        ->label('Marcar entregado')
                        ->icon('heroicon-o-truck')
                        ->color('success')
                        ->requiresConfirmation()
                        ->visible(fn (Requerimiento $record) => $record->estado === EstadoRequerimiento::EnAlistamiento && $record->puedeGestionarAlmacen(auth()->user()))
                        ->action(fn (Requerimiento $record) => $record->marcarEntregado(auth()->user())),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\RequerimientoItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequerimientos::route('/'),
            'create' => Pages\CrearRequerimiento::route('/crear'),
            'view' => Pages\ViewRequerimiento::route('/{record}'),
        ];
    }
}
