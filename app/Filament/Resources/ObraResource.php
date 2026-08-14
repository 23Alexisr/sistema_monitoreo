<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ObraResource\Pages;
use App\Filament\Resources\ObraResource\RelationManagers;
use App\Models\Obra;
use App\Models\OrdenTrabajo;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Pages\Page;
use Filament\Resources\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ObraResource extends Resource
{
    protected static ?string $model = Obra::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $modelLabel = 'Obra';

    protected static ?string $pluralModelLabel = 'Obras';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->hasRole('operario')) {
            $query->whereHas('programaciones.empleado', fn (Builder $q) => $q->where('user_id', auth()->id()));
        }

        return $query;
    }

    public static function canCreate(): bool
    {
        return ! auth()->user()?->hasRole('operario');
    }

    public static function canEdit(Model $record): bool
    {
        return ! auth()->user()?->hasRole('operario');
    }

    public static function canDelete(Model $record): bool
    {
        return ! auth()->user()?->hasRole('operario');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Tabs::make('Obra')
                    ->columnSpanFull()
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Datos generales')
                            ->schema([
                                Forms\Components\Select::make('cliente_id')
                                    ->relationship('cliente', 'nombre')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\TextInput::make('codigo')
                                    ->label('Código')
                                    ->disabled()
                                    ->placeholder('Se generará automáticamente'),
                                Forms\Components\TextInput::make('nombre')
                                    ->required(),
                                Forms\Components\TextInput::make('ubicacion')
                                    ->label('Ubicación')
                                    ->helperText('Referencia libre, ej: "Estación Javier Prado".'),
                                Forms\Components\TextInput::make('link_maps')
                                    ->label('Link de Maps')
                                    ->url()
                                    ->placeholder('https://maps.app.goo.gl/...')
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('verMaps')
                                            ->icon('heroicon-m-map-pin')
                                            ->url(fn (Forms\Get $get) => $get('link_maps'))
                                            ->openUrlInNewTab()
                                            ->visible(fn (Forms\Get $get) => filled($get('link_maps')))
                                    ),
                                Forms\Components\Select::make('estado')
                                    ->options([
                                        'pendiente' => 'Pendiente',
                                        'en_progreso' => 'En progreso',
                                        'finalizada' => 'Finalizada',
                                        'cancelada' => 'Cancelada',
                                    ])
                                    ->default('pendiente')
                                    ->required(),
                                Forms\Components\DatePicker::make('fecha_inicio'),
                                Forms\Components\DatePicker::make('fecha_fin_estimada'),
                                Forms\Components\Placeholder::make('avance_pct')
                                    ->label('Avance')
                                    ->content(fn (?Obra $record) => $record ? $record->avance_pct.'%' : 'Se calcula al crear el checklist')
                                    ->visible(fn (?Obra $record) => $record !== null),
                            ])
                            ->columns(2),
                        Forms\Components\Tabs\Tab::make('Orden de Trabajo')
                            ->schema([
                                Forms\Components\Section::make()
                                    ->relationship('ordenTrabajo')
                                    ->schema([
                                        Forms\Components\TextInput::make('numero_ot')
                                            ->label('Número de OT')
                                            ->required()
                                            ->unique(ignoreRecord: true),
                                        Forms\Components\Select::make('estado')
                                            ->options([
                                                'pendiente' => 'Pendiente',
                                                'en_proceso' => 'En proceso',
                                                'cerrada' => 'Cerrada',
                                            ])
                                            ->default('pendiente')
                                            ->required(),
                                        Forms\Components\DatePicker::make('fecha_emision'),
                                        Forms\Components\Textarea::make('alcance')
                                            ->columnSpanFull(),
                                        Forms\Components\Textarea::make('especificaciones')
                                            ->columnSpanFull(),
                                        Forms\Components\FileUpload::make('archivo_excel_original')
                                            ->label('Excel original (respaldo)')
                                            ->helperText('Se guarda tal cual como respaldo. No se procesa automáticamente.')
                                            ->disk('local')
                                            ->directory('ordenes-trabajo')
                                            ->acceptedFileTypes(['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'])
                                            ->hintAction(
                                                Forms\Components\Actions\Action::make('descargarExcel')
                                                    ->label('Descargar')
                                                    ->icon('heroicon-o-arrow-down-tray')
                                                    ->visible(fn (?OrdenTrabajo $record) => filled($record?->archivo_excel_original))
                                                    ->action(fn (?OrdenTrabajo $record) => Storage::disk('local')->download($record->archivo_excel_original))
                                            )
                                            ->columnSpanFull(),
                                    ])
                                    ->columns(2),
                            ]),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProgramacionesRelationManager::class,
            RelationManagers\DocumentosRelationManager::class,
        ];
    }

    public static function getRecordSubNavigation(Page $page): array
    {
        return $page->generateNavigationItems([
            Pages\EditObra::class,
            Pages\ManageChecklist::class,
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListObras::route('/'),
            'create' => Pages\CreateObra::route('/create'),
            'edit' => Pages\EditObra::route('/{record}/edit'),
            'checklist' => Pages\ManageChecklist::route('/{record}/checklist'),
        ];
    }
}
