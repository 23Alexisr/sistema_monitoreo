<?php

namespace App\Filament\Resources\CategoriaTrabajoResource\Pages;

use App\Filament\Resources\CategoriaTrabajoResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListCategoriaTrabajos extends ListRecords
{
    protected static string $resource = CategoriaTrabajoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
