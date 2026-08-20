<?php

namespace App\Filament\Resources\RequerimientoResource\Pages;

use App\Filament\Resources\RequerimientoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRequerimientos extends ListRecords
{
    protected static string $resource = RequerimientoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pedirSenaletica')
                ->label('Pedir señalética')
                ->icon('heroicon-o-plus')
                ->url(fn () => RequerimientoResource::getUrl('create', ['tipo' => 'señaletica']))
                ->visible(fn () => auth()->user()?->hasAnyRole(['administrador', 'jefe_proyectos', 'jefe_ssoma']) ?? false),
        ];
    }
}
