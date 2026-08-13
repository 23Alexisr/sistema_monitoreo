<?php

namespace App\Filament\Resources\ObraResource\RelationManagers;

use App\Models\Documento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Grouping\Group;
use Filament\Tables\Table;

class DocumentosRelationManager extends RelationManager
{
    protected static string $relationship = 'documentos';

    protected static ?string $title = 'Documentos y fotos';

    protected static ?string $modelLabel = 'documento';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('tipo')
                    ->label('Tipo')
                    ->options(Documento::TIPOS)
                    ->required(),
                Forms\Components\FileUpload::make('url')
                    ->label('Archivos')
                    ->helperText('Puedes seleccionar varios archivos del mismo tipo a la vez.')
                    ->multiple()
                    ->disk('public')
                    ->directory('documentos-obra')
                    ->acceptedFileTypes(['image/*', 'application/pdf'])
                    ->reorderable()
                    ->appendFiles()
                    ->required()
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('tipo')
            ->contentGrid([
                'default' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\ViewColumn::make('preview')
                    ->label('')
                    ->view('filament.tables.columns.documento-preview'),
                Tables\Columns\TextColumn::make('tipo')
                    ->badge()
                    ->formatStateUsing(fn (Documento $record) => $record->tipoLabel()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Subido')
                    ->dateTime('d/m/Y H:i')
                    ->color('gray')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->groups([
                Group::make('tipo')
                    ->label('Tipo')
                    ->getTitleFromRecordUsing(fn (Documento $record) => $record->tipoLabel())
                    ->collapsible(),
            ])
            ->defaultGroup('tipo')
            ->filters([
                Tables\Filters\SelectFilter::make('tipo')
                    ->options(Documento::TIPOS),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Subir documentos')
                    ->modalHeading('Subir documentos')
                    ->using(function (array $data, string $model) {
                        $rutas = (array) $data['url'];
                        $primero = null;

                        foreach ($rutas as $ruta) {
                            $registro = $model::create([
                                'obra_id' => $this->getOwnerRecord()->getKey(),
                                'tipo' => $data['tipo'],
                                'url' => $ruta,
                            ]);

                            $primero ??= $registro;
                        }

                        return $primero;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('ver')
                    ->label('Ver')
                    ->icon('heroicon-o-eye')
                    ->iconButton()
                    ->tooltip('Ver en una pestaña nueva')
                    ->url(fn (Documento $record) => $record->urlPublica())
                    ->openUrlInNewTab(),
                Tables\Actions\Action::make('descargar')
                    ->label('Descargar')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->iconButton()
                    ->tooltip('Descargar')
                    ->url(fn (Documento $record) => $record->urlPublica())
                    ->extraAttributes(['download' => true]),
                Tables\Actions\DeleteAction::make()
                    ->iconButton(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
