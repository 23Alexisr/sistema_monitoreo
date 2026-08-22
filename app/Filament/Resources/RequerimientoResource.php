<?php

namespace App\Filament\Resources;

use App\Enums\EstadoRequerimiento;
use App\Enums\TipoRequerimiento;
use App\Filament\Resources\RequerimientoResource\Pages;
use App\Filament\Resources\RequerimientoResource\RelationManagers;
use App\Models\Requerimiento;
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
        return ['administrador', 'jefe_planta', 'jefe_ssoma', 'jefe_proyectos', 'almacen', 'despacho', 'acabados'];
    }

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->hasAnyRole(static::rolesConAcceso()) ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->hasAnyRole(static::rolesConAcceso()) ?? false;
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

        if ($user->hasRole('acabados')) {
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
                Tables\Columns\TextColumn::make('items_pendientes')
                    ->label('Items pendientes')
                    ->getStateUsing(fn (Requerimiento $record) => $record->items()->where('preparado', false)->count())
                    ->visible(fn () => auth()->user()?->hasRole('acabados') ?? false),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('estado')
                    ->options(collect(EstadoRequerimiento::cases())->mapWithKeys(fn ($e) => [$e->value => $e->label()])->toArray()),
                Tables\Filters\SelectFilter::make('tipo')
                    ->options(collect(TipoRequerimiento::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->toArray())
                    ->visible(fn () => auth()->user()?->hasRole('administrador') ?? false),
            ])
            ->recordUrl(fn (Requerimiento $record) => static::getUrlSegunRol($record))
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->url(fn (Requerimiento $record) => static::getUrlSegunRol($record)),
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
                        TextEntry::make('requerimientoOriginal.id')->label('Adicional de')
                            ->formatStateUsing(fn ($state) => "#{$state}")
                            ->visible(fn (Requerimiento $record) => filled($record->requerimiento_original_id)),
                        TextEntry::make('aprobadoPor.empleado.nombre_completo')->label('Aprobado por')->placeholder('—'),
                        TextEntry::make('auto_aprobado')
                            ->label('')
                            ->state('Auto-aprobado')
                            ->badge()
                            ->color('warning')
                            ->visible(fn (Requerimiento $record) => filled($record->aprobado_por) && $record->aprobado_por === $record->solicitado_por),
                        TextEntry::make('motivo_rechazo')->label('Motivo de rechazo')->placeholder('—')->columnSpanFull()
                            ->visible(fn (Requerimiento $record) => filled($record->motivo_rechazo)),
                        TextEntry::make('alistadoPor.empleado.nombre_completo')->label('Alistado por')->placeholder('—')
                            ->visible(fn (Requerimiento $record) => $record->tipo !== TipoRequerimiento::Señaletica),
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
            'preparar' => Pages\PrepararSenaletica::route('/{record}/preparar'),
            'verificar' => Pages\VerificarDespacho::route('/{record}/verificar'),
        ];
    }

    /**
     * acabados/despacho van directo a su pantalla de tarea (checklist), no
     * a la vista de detalle administrativa (esa es "la de los jefes": todo
     * el estado del pedido, aprobación, etc). El resto de los roles sigue
     * yendo al detalle de siempre.
     */
    public static function getUrlSegunRol(Requerimiento $record): string
    {
        $user = auth()->user();

        if ($user?->hasRole('acabados') && $record->puedeGestionarAcabados($user)) {
            return static::getUrl('preparar', ['record' => $record]);
        }

        if ($user?->hasRole('despacho') && $record->puedeGestionarDespacho($user)) {
            return static::getUrl('verificar', ['record' => $record]);
        }

        return static::getUrl('view', ['record' => $record]);
    }
}
