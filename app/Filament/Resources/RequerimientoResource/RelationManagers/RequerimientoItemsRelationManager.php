<?php

namespace App\Filament\Resources\RequerimientoResource\RelationManagers;

use App\Enums\TipoRequerimiento;
use App\Models\Requerimiento;
use App\Models\RequerimientoItem;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class RequerimientoItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $title = 'Materiales pedidos';

    public function isReadOnly(): bool
    {
        return false;
    }

    public function table(Table $table): Table
    {
        /** @var Requerimiento $requerimiento */
        $requerimiento = $this->getOwnerRecord();
        $esSenaletica = $requerimiento->tipo === TipoRequerimiento::Señaletica;
        $puedeEditarObservaciones = $requerimiento->puedeGestionarAlmacen(auth()->user());
        $puedeVinilero = $requerimiento->puedeGestionarVinilero(auth()->user());
        $puedeDespacho = $requerimiento->puedeGestionarDespacho(auth()->user());

        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\ImageColumn::make('material.foto')
                    ->label('')
                    ->circular()
                    ->defaultImageUrl(fn (RequerimientoItem $record) => $record->fotoReferenciaUrl()),
                Tables\Columns\TextColumn::make('nombre')
                    ->label('Material')
                    ->getStateUsing(fn (RequerimientoItem $record) => $record->nombreParaMostrar())
                    ->description(fn (RequerimientoItem $record) => ! $record->esCatalogado() ? 'No catalogado' : null),
                Tables\Columns\TextColumn::make('cantidad')
                    ->formatStateUsing(fn (RequerimientoItem $record) => $record->cantidad.' '.($record->material?->unidad_medida ?? '')),
                Tables\Columns\TextColumn::make('dimensiones')
                    ->label('Medida')
                    ->getStateUsing(fn (RequerimientoItem $record) => $record->dimensionesEfectivas())
                    ->placeholder('—'),
                Tables\Columns\IconColumn::make('es_sugerido')
                    ->label('Sugerido')
                    ->boolean(),
                ...($esSenaletica ? [
                    Tables\Columns\IconColumn::make('preparado')
                        ->label('Preparado')
                        ->boolean(),
                    Tables\Columns\IconColumn::make('verificado_despacho')
                        ->label('Verificado')
                        ->boolean(),
                ] : []),
                $puedeEditarObservaciones
                    ? Tables\Columns\TextInputColumn::make('observaciones')
                        ->label('Observaciones de Almacén')
                        ->placeholder('Ej: no había stock, se entregó parcial')
                    : Tables\Columns\TextColumn::make('observaciones')
                        ->label('Observaciones')
                        ->placeholder('—')
                        ->wrap(),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('marcarPreparado')
                    ->label('Marcar preparado')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (RequerimientoItem $record) => $puedeVinilero && ! $record->preparado)
                    ->action(fn (RequerimientoItem $record) => $record->marcarPreparado()),
                Tables\Actions\Action::make('verificarDespacho')
                    ->label('Verificar')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn (RequerimientoItem $record) => $puedeDespacho && $record->preparado && ! $record->verificado_despacho)
                    ->action(fn (RequerimientoItem $record) => $record->verificarDespacho(auth()->user())),
                Tables\Actions\Action::make('rechazarDespacho')
                    ->label('Rechazar item')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->form([
                        Forms\Components\Textarea::make('motivo')
                            ->label('¿Qué está mal?')
                            ->required(),
                    ])
                    ->visible(fn (RequerimientoItem $record) => $puedeDespacho && $record->preparado && ! $record->verificado_despacho)
                    ->action(fn (RequerimientoItem $record, array $data) => $record->rechazarDespacho($data['motivo'])),
            ])
            ->bulkActions([
                //
            ]);
    }
}
